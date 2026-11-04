<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Maxcode\Core\Model\Catalog\Reader;

/**
 * Données du bandeau de présentation de l'éditeur, en tête de la section Maxcode > Core.
 *
 * « Maxcode » est le nom de développeur sous lequel les modules sont écrits ; eBusiness360 est l'éditeur qui les
 * vend. Le bandeau n'apparaît que sur la section Core : les autres modules gardent leur page sobre.
 */
class Publisher implements ArgumentInterface
{
    public const SECTION = 'maxcode_core';

    /**
     * @param RequestInterface $request Requête courante.
     * @param Reader           $catalog Catalogue embarqué (adresse du site de l'éditeur).
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Reader $catalog
    ) {
    }

    /**
     * Le bandeau à afficher, ou null hors de la section Core.
     *
     * @return array{links:list<array{label:string,url:string,primary:bool}>,points:string[]}|null
     */
    public function getBanner(): ?array
    {
        if ($this->request->getParam('section') !== self::SECTION) {
            return null;
        }
        $links = $this->catalog->getPublisherLinks();

        return [
            'links' => [
                ['label' => (string) __('Discover the modules on eBusiness360.fr'), 'url' => $links['modules'], 'primary' => true],
                ['label' => (string) __('Visit the website'), 'url' => $links['site'], 'primary' => false],
                ['label' => (string) __('Follow the Magento 2 tech news'), 'url' => $links['news'], 'primary' => false],
            ],
            'points' => [
                (string) __('Free and paid modules'),
                (string) __('Full source code included'),
                (string) __('Support and updates on paid modules'),
            ],
        ];
    }
}
