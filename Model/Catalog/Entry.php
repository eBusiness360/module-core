<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Catalog;

/**
 * Une ligne du catalogue embarqué des modules Maxcode (etc/catalog.json).
 */
final class Entry
{
    public const TYPE_FREE = 'free';
    public const TYPE_PAID = 'paid';

    public const AVAILABILITY_AVAILABLE = 'available';
    public const AVAILABILITY_SOON = 'soon';

    /**
     * @param string      $module       Nom Magento du module (Maxcode_Xxx).
     * @param string      $package      Nom du paquet Composer.
     * @param string      $type         TYPE_FREE ou TYPE_PAID.
     * @param string      $name         Nom court, affiché tel quel.
     * @param string      $description  Description courte, en anglais (clé de traduction).
     * @param string|null $url          Adresse HTTPS de la fiche, si elle existe.
     * @param string      $availability AVAILABILITY_AVAILABLE ou AVAILABILITY_SOON.
     * @param string|null $docsUrl      Adresse HTTPS de la documentation complète (README public), si elle existe.
     * @param string|null $latestVersion Dernière version connue à la publication de ce catalogue (x.y.z).
     */
    public function __construct(
        public readonly string $module,
        public readonly string $package,
        public readonly string $type,
        public readonly string $name,
        public readonly string $description,
        public readonly ?string $url = null,
        public readonly string $availability = self::AVAILABILITY_AVAILABLE,
        public readonly ?string $docsUrl = null,
        public readonly ?string $latestVersion = null
    ) {
    }

    /**
     * Le module est-il payant ?
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->type === self::TYPE_PAID;
    }

    /**
     * Le module est-il annoncé mais pas encore disponible ?
     *
     * @return bool
     */
    public function isComingSoon(): bool
    {
        return $this->availability === self::AVAILABILITY_SOON;
    }
}
