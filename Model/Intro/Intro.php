<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Intro;

/**
 * Mini-documentation qu'un module déclare pour sa page de configuration.
 *
 * Les textes sont des chaînes anglaises : la traduction est appliquée à l'affichage.
 */
final class Intro
{
    /**
     * @param string        $id      Identifiant de la déclaration.
     * @param string        $module  Nom Magento du module décrit (doit figurer au catalogue).
     * @param string|null   $section Section de configuration où le bloc s'affiche d'office.
     * @param string|null   $summary Résumé ; null = description courte du catalogue.
     * @param string[]      $points  Quelques points clés, courts.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $module,
        public readonly ?string $section = null,
        public readonly ?string $summary = null,
        public readonly array $points = []
    ) {
    }
}
