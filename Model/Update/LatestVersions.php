<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Update;

use Maxcode\Core\Model\Catalog\Entry;

/**
 * Dernière version connue d'un module : la plus récente entre celle du catalogue livré avec Core et celle relevée
 * lors de la dernière vérification demandée par l'administrateur.
 */
class LatestVersions
{
    /**
     * @param LatestVersionStore $store Versions relevées lors de la dernière vérification.
     */
    public function __construct(private readonly LatestVersionStore $store)
    {
    }

    /**
     * @param Entry $entry Entrée du catalogue.
     * @return string|null
     */
    public function forEntry(Entry $entry): ?string
    {
        $best = $entry->latestVersion;
        $checked = $this->store->all()[$entry->package] ?? null;
        if ($checked !== null && ($best === null || version_compare($checked, $best, '>'))) {
            $best = $checked;
        }

        return $best;
    }
}
