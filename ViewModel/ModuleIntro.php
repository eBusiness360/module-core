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
use Maxcode\Core\Model\Docs\Link;
use Maxcode\Core\Model\Intro\Intro;
use Maxcode\Core\Model\Intro\IntroPool;

/**
 * Données de la mini-documentation affichée en haut de la page de configuration d'un module.
 *
 * Le nom, la description courte et les liens viennent du catalogue embarqué et du paquet du module : un module ne
 * déclare que ce qui lui est propre (résumé et points clés, facultatifs). La documentation complète est son README
 * livré dans le paquet (lisible même quand le dépôt est privé) ; le dépôt public GitHub, s'il existe, n'est qu'un
 * second lien.
 */
class ModuleIntro implements ArgumentInterface
{
    /**
     * @param RequestInterface $request   Requête courante.
     * @param IntroPool        $introPool Mini-documentations déclarées par les modules.
     * @param Reader           $catalog   Catalogue embarqué.
     * @param Link             $docsLink  Adresse de la documentation embarquée d'un module.
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly IntroPool $introPool,
        private readonly Reader $catalog,
        private readonly Link $docsLink
    ) {
    }

    /**
     * Le bloc à afficher, ou null s'il n'y en a pas.
     *
     * Sans identifiant, la déclaration est celle rattachée à la section de configuration en cours.
     *
     * @param string|null $introId Identifiant d'une déclaration.
     * @return array{name:string,summary:string,points:string[],links:list<array{label:string,url:string,external:bool}>}|null
     */
    public function getIntro(?string $introId = null): ?array
    {
        $intro = $introId !== null && $introId !== ''
            ? $this->introPool->get($introId)
            : $this->introForCurrentSection();
        if ($intro === null) {
            return null;
        }
        $entry = $this->catalog->getByModule($intro->module);
        if ($entry === null) {
            return null;
        }

        return [
            'name' => $entry->name,
            'summary' => (string) __($intro->summary ?? $entry->description),
            'points' => array_map(static fn (string $point): string => (string) __($point), $intro->points),
            'links' => $this->links($intro->module, $entry->docsUrl),
        ];
    }

    /**
     * « Documentation complète » : la page embarquée si le module en livre une, sinon le dépôt public.
     * « Voir sur GitHub » vient en second quand les deux existent.
     *
     * @param string      $module  Nom Magento du module.
     * @param string|null $repoUrl Adresse du dépôt public.
     * @return list<array{label:string,url:string,external:bool}>
     */
    private function links(string $module, ?string $repoUrl): array
    {
        $links = [];
        $embedded = $this->docsLink->forModule($module);
        if ($embedded !== null) {
            $links[] = ['label' => (string) __('Full documentation'), 'url' => $embedded, 'external' => false];
        }
        if ($repoUrl !== null) {
            $links[] = [
                'label' => $embedded !== null ? (string) __('View on GitHub') : (string) __('Full documentation'),
                'url' => $repoUrl,
                'external' => true,
            ];
        }

        return $links;
    }

    /**
     * @return Intro|null
     */
    private function introForCurrentSection(): ?Intro
    {
        $section = $this->request->getParam('section');

        return is_string($section) && $section !== '' ? $this->introPool->findBySection($section) : null;
    }
}
