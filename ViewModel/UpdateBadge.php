<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Maxcode\Core\Model\Module\UpdateCounter;

/**
 * Compteur de mises à jour affiché sur l'onglet Maxcode du menu de gauche de la configuration.
 */
class UpdateBadge implements ArgumentInterface
{
    /**
     * @param UpdateCounter $counter Nombre de modules à mettre à jour.
     */
    public function __construct(private readonly UpdateCounter $counter)
    {
    }

    /**
     * Configuration du script du badge, ou null quand tout est à jour.
     *
     * @return array{count:int,label:string}|null
     */
    public function getConfig(): ?array
    {
        $count = $this->counter->count();
        if ($count === 0) {
            return null;
        }

        return [
            'count' => $count,
            'label' => (string) __('Maxcode modules to update: %1', $count),
        ];
    }
}
