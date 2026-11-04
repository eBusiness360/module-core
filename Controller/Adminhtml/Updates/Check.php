<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Controller\Adminhtml\Updates;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Maxcode\Core\Model\Module\UpdateCounter;
use Maxcode\Core\Model\Update\Checker;

/**
 * Lance la vérification des mises à jour sur Packagist, à la demande expresse de l'administrateur.
 *
 * POST uniquement (clé de formulaire contrôlée par Magento) : aucune adresse ne peut déclencher la requête réseau
 * en un simple chargement de page.
 */
class Check extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Maxcode_Core::config';

    /**
     * @param Context       $context       Contexte du contrôleur d'administration.
     * @param Checker       $checker       Vérification des mises à jour.
     * @param UpdateCounter $updateCounter Modules à mettre à jour.
     */
    public function __construct(
        Context $context,
        private readonly Checker $checker,
        private readonly UpdateCounter $updateCounter
    ) {
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        try {
            $result = $this->checker->check();
            $updates = $this->updateCounter->count();
            $this->messageManager->addSuccessMessage(
                $updates > 0
                    ? __('Versions checked on packagist.org. Maxcode modules to update: %1.', $updates)
                    : __('Versions checked on packagist.org. All your Maxcode modules are up to date.')
            );
            if ($result->failed > 0) {
                $this->messageManager->addWarningMessage(
                    __('%1 package(s) could not be checked. Their known version was kept.', $result->failed)
                );
            }
        } catch (\RuntimeException) {
            $this->messageManager->addErrorMessage(
                __('packagist.org could not be reached. The versions already known are kept.')
            );
        }

        return $this->resultRedirectFactory->create()->setPath('adminhtml/system_config/edit', ['section' => 'maxcode_core']);
    }
}
