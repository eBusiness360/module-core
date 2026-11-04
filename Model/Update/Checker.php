<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Update;

use Maxcode\Core\Model\Catalog\Reader;

/**
 * Vérification des mises à jour, lancée UNIQUEMENT par l'administrateur (bouton de la liste des modules).
 *
 * Seuls les modules gratuits du catalogue sont interrogés : ce sont les paquets publics de Packagist. Les modules
 * payants ne le sont jamais (leurs dépôts sont privés, et leur nom ne doit pas quitter le site).
 */
class Checker
{
    /**
     * @param Reader             $catalog Catalogue embarqué.
     * @param PackagistClient    $client  Lecture des versions sur Packagist.
     * @param LatestVersionStore $store   Stockage du résultat.
     */
    public function __construct(
        private readonly Reader $catalog,
        private readonly PackagistClient $client,
        private readonly LatestVersionStore $store
    ) {
    }

    /**
     * @return CheckResult
     * @throws \RuntimeException Packagist n'a répondu pour aucun paquet : rien n'est enregistré.
     */
    public function check(): CheckResult
    {
        $versions = $this->store->all();
        $queried = 0;
        $found = 0;
        $failed = 0;
        foreach ($this->catalog->getEntries() as $entry) {
            if ($entry->isPaid()) {
                continue;
            }
            $queried++;
            try {
                $version = $this->client->latestStableVersion($entry->package);
            } catch (\RuntimeException) {
                $failed++;
                continue;
            }
            if ($version !== null) {
                $versions[$entry->package] = $version;
                $found++;
            } else {
                unset($versions[$entry->package]);
            }
        }
        if ($queried > 0 && $failed === $queried) {
            throw new \RuntimeException('Packagist est injoignable.');
        }
        $this->store->save($versions);

        return new CheckResult($queried, $found, $failed);
    }
}
