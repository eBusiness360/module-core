<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Controller\Adminhtml\Docs;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Maxcode\Core\Model\Catalog\Reader;
use Maxcode\Core\Model\Docs\DocumentReader;
use Maxcode\Core\Model\Docs\Link;

/**
 * Page « Documentation » d'un module Maxcode : son README embarqué, rendu dans l'administration.
 */
class View extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = Link::ACL_RESOURCE;

    /**
     * @param Context        $context           Contexte du contrôleur d'administration.
     * @param PageFactory    $resultPageFactory Fabrique de pages.
     * @param Reader         $catalog           Catalogue embarqué.
     * @param DocumentReader $documents         Lecteur de documentation.
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly Reader $catalog,
        private readonly DocumentReader $documents
    ) {
        parent::__construct($context);
    }

    /**
     * @return Page|Forward
     */
    public function execute(): Page|Forward
    {
        $module = (string) $this->getRequest()->getParam('module');
        $entry = $this->catalog->getByModule($module);
        if ($entry === null || !$this->documents->hasDocument($module)) {
            /** @var Forward $forward */
            $forward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);

            return $forward->forward('noroute');
        }
        /** @var Page $page */
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('Magento_Config::system_config');
        $page->getConfig()->getTitle()->prepend(__('Documentation — %1', $entry->name));

        return $page;
    }
}
