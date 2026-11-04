<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Maxcode\Core\ViewModel\Modules as ModulesViewModel;

/**
 * Rendu du catalogue des modules Maxcode dans la section de configuration « Core ».
 *
 * Le champ « note » du system.xml n'est qu'un point d'ancrage : le rendu remplace toute la ligne du formulaire.
 */
class Modules extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Maxcode_Core::modules.phtml';

    /**
     * @param Context          $context   Contexte du bloc d'administration.
     * @param ModulesViewModel $viewModel Données du catalogue.
     * @param array            $data      Données du bloc.
     */
    public function __construct(
        Context $context,
        ModulesViewModel $viewModel,
        array $data = []
    ) {
        parent::__construct($context, $data + ['view_model' => $viewModel]);
    }

    /**
     * Une seule cellule pleine largeur, sans libellé ni case « utiliser la valeur par défaut ».
     *
     * @param AbstractElement $element Élément du formulaire.
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        return '<tr id="row_' . $this->escapeHtmlAttr($element->getHtmlId()) . '"><td colspan="100">'
            . $this->toHtml() . '</td></tr>';
    }
}
