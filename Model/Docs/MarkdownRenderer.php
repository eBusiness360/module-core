<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Docs;

use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Transforme le README d'un module (Markdown) en HTML affichable dans l'administration.
 *
 * Le HTML brut est supprimé, les liens dangereux aussi, et la sortie est nettoyée une seconde fois : seuls les liens
 * HTTPS survivent (ouverts dans un nouvel onglet). Les images sont retirées (leurs chemins relatifs n'existent
 * pas dans l'administration) ainsi que les liens vers les autres README du dépôt.
 * Sans la bibliothèque league/commonmark, le texte est affiché tel quel, échappé.
 */
class MarkdownRenderer
{
    /**
     * @param string $markdown Contenu Markdown.
     * @return string HTML sûr.
     */
    public function render(string $markdown): string
    {
        $markdown = $this->removeLanguageSwitch($markdown);
        if (!class_exists(GithubFlavoredMarkdownConverter::class)) {
            return '<pre class="maxcode-core-doc__plain">'
                . htmlspecialchars($markdown, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
        }
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);

        return $this->clean((string) $converter->convert($markdown));
    }

    /**
     * Retire la ligne « English · Français » des README, qui renvoie vers les fichiers du dépôt.
     *
     * @param string $markdown Contenu Markdown.
     * @return string
     */
    private function removeLanguageSwitch(string $markdown): string
    {
        return (string) preg_replace('/^.*\]\(README(?:\.[a-z]{2})?\.md\).*\R?/m', '', $markdown);
    }

    /**
     * @param string $html HTML produit par le convertisseur.
     * @return string
     */
    private function clean(string $html): string
    {
        $html = (string) preg_replace('#<img\b[^>]*>#i', '', $html);
        $html = (string) preg_replace_callback(
            '#<a href="([^"]*)"[^>]*>(.*?)</a>#is',
            static fn (array $match): string => str_starts_with($match[1], 'https://')
                ? '<a href="' . $match[1] . '" target="_blank" rel="noopener noreferrer">' . $match[2] . '</a>'
                : $match[2],
            $html
        );

        return (string) preg_replace('#<p>\s*</p>\s*#', '', $html);
    }
}
