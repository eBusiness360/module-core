<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Module;

use Composer\InstalledVersions;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Retrouve la version d'un module Maxcode, sans réseau.
 *
 * Ordre : métadonnées Composer (module installé par Composer), champ « version » du composer.json du module,
 * puis première version du CHANGELOG.md (module posé à la main dans app/code, sans version déclarée).
 */
class VersionResolver
{
    /**
     * @param File $file Lecture de fichier.
     * @param Json $json Sérialiseur JSON.
     */
    public function __construct(
        private readonly File $file,
        private readonly Json $json
    ) {
    }

    /**
     * @param string      $package    Nom du paquet Composer.
     * @param string|null $modulePath Dossier du module sur le disque.
     * @return string|null
     */
    public function resolve(string $package, ?string $modulePath): ?string
    {
        return $this->fromComposerRuntime($package)
            ?? $this->fromModuleComposerJson($modulePath)
            ?? $this->fromChangelog($modulePath);
    }

    /**
     * @param string $package Nom du paquet Composer.
     * @return string|null
     */
    private function fromComposerRuntime(string $package): ?string
    {
        if (!class_exists(InstalledVersions::class) || !InstalledVersions::isInstalled($package)) {
            return null;
        }

        return $this->clean(InstalledVersions::getPrettyVersion($package));
    }

    /**
     * @param string|null $modulePath Dossier du module.
     * @return string|null
     */
    private function fromModuleComposerJson(?string $modulePath): ?string
    {
        $content = $this->read($modulePath, 'composer.json');
        if ($content === null) {
            return null;
        }
        try {
            $data = $this->json->unserialize($content);
        } catch (\Throwable) {
            return null;
        }

        return is_array($data) ? $this->clean($data['version'] ?? null) : null;
    }

    /**
     * @param string|null $modulePath Dossier du module.
     * @return string|null
     */
    private function fromChangelog(?string $modulePath): ?string
    {
        $content = $this->read($modulePath, 'CHANGELOG.md');
        if ($content !== null && preg_match('/^##\s*\[(\d+\.\d+\.\d+[^\]]*)\]/m', $content, $match) === 1) {
            return $match[1];
        }

        return null;
    }

    /**
     * @param string|null $modulePath Dossier du module.
     * @param string      $name       Nom du fichier.
     * @return string|null
     */
    private function read(?string $modulePath, string $name): ?string
    {
        if ($modulePath === null || $modulePath === '') {
            return null;
        }
        $path = rtrim($modulePath, '/') . '/' . $name;
        try {
            return $this->file->isExists($path) ? $this->file->fileGetContents($path) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Retire le « v » initial et écarte les valeurs vides.
     *
     * @param mixed $version Version brute.
     * @return string|null
     */
    private function clean(mixed $version): ?string
    {
        if (!is_string($version) || trim($version) === '') {
            return null;
        }

        return ltrim(trim($version), 'vV');
    }
}
