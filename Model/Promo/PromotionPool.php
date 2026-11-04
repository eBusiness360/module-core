<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Promo;

/**
 * Registre des promotions déclarées en DI par les modules gratuits (argument « promotions »).
 *
 * Une déclaration sans module payant ou sans texte est ignorée, plutôt que de casser une page d'administration.
 */
class PromotionPool
{
    /** @var Promotion[] */
    private array $promotions = [];

    /**
     * @param array $promotions Déclarations indexées par identifiant.
     */
    public function __construct(array $promotions = [])
    {
        foreach ($promotions as $id => $row) {
            $promotion = is_array($row) ? $this->build((string) $id, $row) : null;
            if ($promotion !== null) {
                $this->promotions[$promotion->id] = $promotion;
            }
        }
    }

    /**
     * @param string $id Identifiant de la déclaration.
     * @return Promotion|null
     */
    public function get(string $id): ?Promotion
    {
        return $this->promotions[$id] ?? null;
    }

    /**
     * Première promotion rattachée à une section de configuration.
     *
     * @param string $section Identifiant de section (maxcode_xxx).
     * @return Promotion|null
     */
    public function findBySection(string $section): ?Promotion
    {
        foreach ($this->promotions as $promotion) {
            if ($promotion->section !== null && $promotion->section === $section) {
                return $promotion;
            }
        }

        return null;
    }

    /**
     * @param string $id  Identifiant.
     * @param array  $row Déclaration brute.
     * @return Promotion|null
     */
    private function build(string $id, array $row): ?Promotion
    {
        $module = $this->text($row['paid_module'] ?? null);
        $text = $this->text($row['text'] ?? null);
        if ($module === null || $text === null) {
            return null;
        }
        $url = $this->text($row['url'] ?? null);

        return new Promotion(
            $id,
            $module,
            $text,
            $this->text($row['title'] ?? null),
            $url !== null && str_starts_with($url, 'https://') ? $url : null,
            $this->text($row['section'] ?? null)
        );
    }

    /**
     * @param mixed $value Valeur brute.
     * @return string|null Chaîne non vide, sinon null.
     */
    private function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
