<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Module;

/**
 * État d'un module Maxcode sur cette installation.
 */
final class Status
{
    public const INSTALLED = 'installed';
    public const DISABLED = 'disabled';
    public const NOT_INSTALLED = 'not_installed';
    public const COMING_SOON = 'coming_soon';

    private const NUMERIC_VERSION = '/^\d+(\.\d+)*$/';

    /**
     * @param string      $state         Une des constantes de la classe.
     * @param string|null $version       Version installée, si le module est présent et qu'elle est connue.
     * @param string|null $latestVersion Dernière version connue du catalogue.
     */
    public function __construct(
        public readonly string $state,
        public readonly ?string $version = null,
        public readonly ?string $latestVersion = null
    ) {
    }

    /**
     * Le module est-il présent sur le disque (actif ou non) ?
     *
     * @return bool
     */
    public function isPresent(): bool
    {
        return $this->state === self::INSTALLED || $this->state === self::DISABLED;
    }

    /**
     * Une version plus récente que celle installée existe-t-elle ?
     *
     * Faux dès qu'une des deux versions n'est pas un numéro (« dev-main » par exemple) : on ne compare pas ce
     * qu'on ne sait pas ordonner.
     *
     * @return bool
     */
    public function hasUpdate(): bool
    {
        return $this->isPresent()
            && $this->version !== null
            && $this->latestVersion !== null
            && preg_match(self::NUMERIC_VERSION, $this->version) === 1
            && preg_match(self::NUMERIC_VERSION, $this->latestVersion) === 1
            && version_compare($this->version, $this->latestVersion, '<');
    }
}
