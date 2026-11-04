<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Promo;

/**
 * Ce qu'un module gratuit déclare pour promouvoir son extension payante.
 *
 * Les textes sont des chaînes anglaises : la traduction est appliquée à l'affichage.
 */
final class Promotion
{
    /**
     * @param string      $id         Identifiant de la déclaration.
     * @param string      $paidModule Nom Magento du module payant promu.
     * @param string      $text       Texte du bandeau.
     * @param string|null $title      Titre ; null = « Allez plus loin avec <module payant> ».
     * @param string|null $url        Adresse HTTPS ; null = fiche du catalogue.
     * @param string|null $section    Section de configuration où le bandeau s'affiche d'office.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $paidModule,
        public readonly string $text,
        public readonly ?string $title = null,
        public readonly ?string $url = null,
        public readonly ?string $section = null
    ) {
    }
}
