<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Docs;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;

/**
 * Adresse de la page d'administration qui affiche la documentation embarquée d'un module.
 *
 * Retourne null quand le module n'en livre pas, ou quand l'administrateur n'a pas le droit de la lire : un lien
 * ne doit jamais mener à une page refusée.
 */
class Link
{
    public const ACL_RESOURCE = 'Maxcode_Core::docs';

    /**
     * @param AuthorizationInterface $authorization Droits de l'administrateur connecté.
     * @param UrlInterface           $urlBuilder    Constructeur d'adresses d'administration.
     * @param DocumentReader         $reader        Lecteur de documentation.
     */
    public function __construct(
        private readonly AuthorizationInterface $authorization,
        private readonly UrlInterface $urlBuilder,
        private readonly DocumentReader $reader
    ) {
    }

    /**
     * @param string $module Nom Magento du module.
     * @return string|null
     */
    public function forModule(string $module): ?string
    {
        if (!$this->authorization->isAllowed(self::ACL_RESOURCE) || !$this->reader->hasDocument($module)) {
            return null;
        }

        // Chemin avec l'identifiant de ROUTE (maxcode_core), pas le frontName : la clé secrète de l'URL est calculée
        // dessus, et c'est lui que Magento compare à la réception.
        return $this->urlBuilder->getUrl('maxcode_core/docs/view', ['module' => $module]);
    }
}
