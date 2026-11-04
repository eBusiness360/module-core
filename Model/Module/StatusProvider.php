<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Module;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Module\ModuleListInterface;
use Maxcode\Core\Model\Catalog\Entry;
use Maxcode\Core\Model\Update\LatestVersions;

/**
 * Dit si un module du catalogue est installé, actif ou absent.
 *
 * « Installé » se lit dans le registre des composants Magento : peu importe que le module vienne de Packagist,
 * d'un dépôt privé, d'une place de marché ou de app/code.
 */
class StatusProvider
{
    /**
     * @param ComponentRegistrarInterface $registrar       Registre des composants.
     * @param ModuleListInterface         $moduleList      Modules actifs.
     * @param VersionResolver             $versionResolver Lecture de version.
     * @param LatestVersions              $latestVersions  Dernière version connue (catalogue ou dernière vérification).
     */
    public function __construct(
        private readonly ComponentRegistrarInterface $registrar,
        private readonly ModuleListInterface $moduleList,
        private readonly VersionResolver $versionResolver,
        private readonly LatestVersions $latestVersions
    ) {
    }

    /**
     * @param Entry $entry Entrée du catalogue.
     * @return Status
     */
    public function getStatus(Entry $entry): Status
    {
        $path = $this->registrar->getPath(ComponentRegistrar::MODULE, $entry->module);
        if ($path === null) {
            return new Status(
                $entry->isComingSoon() ? Status::COMING_SOON : Status::NOT_INSTALLED,
                null,
                $this->latestVersions->forEntry($entry)
            );
        }

        return new Status(
            $this->moduleList->has($entry->module) ? Status::INSTALLED : Status::DISABLED,
            $this->versionResolver->resolve($entry->package, $path),
            $this->latestVersions->forEntry($entry)
        );
    }

    /**
     * Le module est-il présent sur le disque, actif ou non ?
     *
     * @param string $module Nom Magento du module.
     * @return bool
     */
    public function isPresent(string $module): bool
    {
        return $this->registrar->getPath(ComponentRegistrar::MODULE, $module) !== null;
    }
}
