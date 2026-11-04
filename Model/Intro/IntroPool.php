<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Intro;

/**
 * Registre des mini-documentations déclarées en DI par les modules (argument « intros »).
 *
 * Une déclaration sans module est ignorée plutôt que de casser une page d'administration.
 */
class IntroPool
{
    private const MAX_POINTS = 6;

    /** @var Intro[] */
    private array $intros = [];

    /**
     * @param array $intros Déclarations indexées par identifiant.
     */
    public function __construct(array $intros = [])
    {
        foreach ($intros as $id => $row) {
            $intro = is_array($row) ? $this->build((string) $id, $row) : null;
            if ($intro !== null) {
                $this->intros[$intro->id] = $intro;
            }
        }
    }

    /**
     * @param string $id Identifiant de la déclaration.
     * @return Intro|null
     */
    public function get(string $id): ?Intro
    {
        return $this->intros[$id] ?? null;
    }

    /**
     * Première déclaration rattachée à une section de configuration.
     *
     * @param string $section Identifiant de section.
     * @return Intro|null
     */
    public function findBySection(string $section): ?Intro
    {
        foreach ($this->intros as $intro) {
            if ($intro->section !== null && $intro->section === $section) {
                return $intro;
            }
        }

        return null;
    }

    /**
     * @param string $id  Identifiant.
     * @param array  $row Déclaration brute.
     * @return Intro|null
     */
    private function build(string $id, array $row): ?Intro
    {
        $module = $this->text($row['module'] ?? null);
        if ($module === null) {
            return null;
        }
        $points = [];
        foreach ((array) ($row['points'] ?? []) as $point) {
            $point = $this->text($point);
            if ($point !== null) {
                $points[] = $point;
            }
        }

        return new Intro(
            $id,
            $module,
            $this->text($row['section'] ?? null),
            $this->text($row['summary'] ?? null),
            array_slice($points, 0, self::MAX_POINTS)
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
