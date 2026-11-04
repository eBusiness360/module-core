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
use Maxcode\Core\Model\Module\StatusProvider;
use Maxcode\Core\Model\Promo\Promotion;
use Maxcode\Core\Model\Promo\PromotionPool;

/**
 * Données du bandeau « Allez plus loin avec <module payant> ».
 *
 * Le bandeau n'existe que si une promotion est déclarée et que le module payant, connu du catalogue, n'est PAS
 * installé. Il n'est ni fermable ni désactivable : il disparaît seulement quand le module payant est installé.
 */
class PromoBanner implements ArgumentInterface
{
    /**
     * @param RequestInterface $request        Requête courante.
     * @param PromotionPool    $promotionPool  Promotions déclarées par les modules.
     * @param Reader           $catalog        Catalogue embarqué.
     * @param StatusProvider   $statusProvider Présence des modules.
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly PromotionPool $promotionPool,
        private readonly Reader $catalog,
        private readonly StatusProvider $statusProvider
    ) {
    }

    /**
     * Le bandeau à afficher, ou null s'il n'y en a pas.
     *
     * Sans identifiant, la promotion est celle rattachée à la section de configuration en cours.
     *
     * @param string|null $promotionId Identifiant d'une promotion déclarée.
     * @return array{module:string,product:string,title:string,text:string,url:string,link_label:string}|null
     */
    public function getBanner(?string $promotionId = null): ?array
    {
        $promotion = $promotionId !== null && $promotionId !== ''
            ? $this->promotionPool->get($promotionId)
            : $this->promotionForCurrentSection();
        if ($promotion === null) {
            return null;
        }
        $entry = $this->catalog->getByModule($promotion->paidModule);
        if ($entry === null || !$entry->isPaid() || $this->statusProvider->isPresent($entry->module)) {
            return null;
        }
        $url = $promotion->url ?? $entry->url;

        return [
            'module' => $entry->module,
            'product' => $entry->name,
            'title' => $promotion->title !== null
                ? (string) __($promotion->title)
                : (string) __('Go further with %1', $entry->name),
            'text' => (string) __($promotion->text),
            'url' => $url ?? $this->catalog->getContactUrl(),
            'link_label' => $url !== null ? (string) __('Learn more') : (string) __('Contact us'),
        ];
    }

    /**
     * @return Promotion|null
     */
    private function promotionForCurrentSection(): ?Promotion
    {
        $section = $this->request->getParam('section');

        return is_string($section) && $section !== '' ? $this->promotionPool->findBySection($section) : null;
    }
}
