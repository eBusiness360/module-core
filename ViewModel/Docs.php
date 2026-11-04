<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\ViewModel;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Maxcode\Core\Model\Catalog\Reader;
use Maxcode\Core\Model\Docs\DocumentReader;

/**
 * Données de la page « Documentation » d'un module.
 */
class Docs implements ArgumentInterface
{
    /**
     * @param Reader         $catalog   Catalogue embarqué.
     * @param DocumentReader $documents Lecteur de documentation.
     * @param UrlInterface   $urlBuilder Constructeur d'adresses d'administration.
     */
    public function __construct(
        private readonly Reader $catalog,
        private readonly DocumentReader $documents,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * La documentation d'un module du catalogue, ou null s'il n'en livre pas.
     *
     * @param string $module Nom Magento du module.
     * @return array{name:string,html:string}|null
     */
    public function getDocument(string $module): ?array
    {
        $entry = $this->catalog->getByModule($module);
        $html = $entry !== null ? $this->documents->getHtml($module) : null;

        return $entry !== null && $html !== null ? ['name' => $entry->name, 'html' => $html] : null;
    }

    /**
     * Adresse de la section Configuration > Maxcode > Core, où vit la liste des modules.
     *
     * @return string
     */
    public function getModulesUrl(): string
    {
        return $this->urlBuilder->getUrl('adminhtml/system_config/edit', ['section' => 'maxcode_core']);
    }
}
