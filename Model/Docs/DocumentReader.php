<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Docs;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Lit la documentation livrée DANS le paquet d'un module (README.md, README.fr.md à la racine du module).
 *
 * Le dépôt d'un module payant est privé : la documentation voyage donc avec le code installé. Le chemin vient du
 * registre des composants Magento, jamais d'une valeur saisie : seuls les README des modules Maxcode installés
 * peuvent être lus.
 */
class DocumentReader
{
    private const MAX_BYTES = 524288;

    /**
     * @param ComponentRegistrarInterface $registrar     Registre des composants.
     * @param File                        $file          Lecture de fichier.
     * @param ResolverInterface           $localeResolver Langue de l'interface d'administration.
     * @param MarkdownRenderer            $renderer      Rendu Markdown.
     */
    public function __construct(
        private readonly ComponentRegistrarInterface $registrar,
        private readonly File $file,
        private readonly ResolverInterface $localeResolver,
        private readonly MarkdownRenderer $renderer
    ) {
    }

    /**
     * Le module livre-t-il une documentation lisible ?
     *
     * @param string $module Nom Magento du module.
     * @return bool
     */
    public function hasDocument(string $module): bool
    {
        return $this->findFile($module) !== null;
    }

    /**
     * La documentation rendue en HTML, dans la langue de l'administrateur si elle existe (sinon l'anglais).
     *
     * @param string $module Nom Magento du module.
     * @return string|null
     */
    public function getHtml(string $module): ?string
    {
        $path = $this->findFile($module);
        if ($path === null) {
            return null;
        }
        try {
            return $this->renderer->render($this->file->fileGetContents($path));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param string $module Nom Magento du module.
     * @return string|null Chemin du README à lire.
     */
    private function findFile(string $module): ?string
    {
        if (preg_match('/^Maxcode_[A-Za-z0-9]+$/', $module) !== 1) {
            return null;
        }
        $dir = $this->registrar->getPath(ComponentRegistrar::MODULE, $module);
        if ($dir === null) {
            return null;
        }
        $language = strtolower(substr((string) $this->localeResolver->getLocale(), 0, 2));
        $candidates = $language !== '' && $language !== 'en'
            ? ['README.' . $language . '.md', 'README.md']
            : ['README.md'];
        foreach ($candidates as $name) {
            $path = rtrim($dir, '/') . '/' . $name;
            try {
                if ($this->file->isFile($path) && $this->file->stat($path)['size'] <= self::MAX_BYTES) {
                    return $path;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
