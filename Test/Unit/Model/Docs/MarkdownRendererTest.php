<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Docs;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Maxcode\Core\Model\Docs\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

class MarkdownRendererTest extends TestCase
{
    private function renderer(): MarkdownRenderer
    {
        if (!class_exists(GithubFlavoredMarkdownConverter::class)) {
            $this->markTestSkipped('league/commonmark n\'est pas installé.');
        }

        return new MarkdownRenderer();
    }

    public function testRendersHeadingsListsAndCode(): void
    {
        $html = $this->renderer()->render("# Title\n\n- one\n- two\n\n`code`\n");

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<li>one</li>', $html);
        $this->assertStringContainsString('<code>code</code>', $html);
    }

    public function testRendersGithubTables(): void
    {
        $html = $this->renderer()->render("| A | B |\n|---|---|\n| 1 | 2 |\n");

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>A</th>', $html);
    }

    /**
     * @dataProvider dangerousMarkdown
     */
    public function testNeverLetsScriptsOrDangerousLinksThrough(string $markdown): void
    {
        $html = strtolower($this->renderer()->render($markdown));

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    public static function dangerousMarkdown(): array
    {
        return [
            'script tag' => ["Hello\n\n<script>alert(1)</script>\n"],
            'javascript link' => ["[click](javascript:alert(1))\n"],
            'html image handler' => ["<img src=x onerror=alert(1)>\n"],
            'iframe' => ["<iframe src=\"https://example.test\"></iframe>\n"],
            'data link' => ["[x](data:text/html;base64,PHNjcmlwdD4=)\n"],
        ];
    }

    public function testKeepsOnlyHttpsLinksAndOpensThemInANewTab(): void
    {
        $html = $this->renderer()->render(
            "[ok](https://example.test/page) [plain](http://example.test) [local](docs/guide.md) [anchor](#usage)\n"
        );

        $this->assertStringContainsString(
            '<a href="https://example.test/page" target="_blank" rel="noopener noreferrer">ok</a>',
            $html
        );
        $this->assertStringNotContainsString('http://example.test', $html);
        $this->assertStringNotContainsString('docs/guide.md', $html);
        $this->assertStringContainsString('local', $html);
        $this->assertStringNotContainsString('href="#usage"', $html);
        $this->assertStringContainsString('plain', $html);
        $this->assertStringContainsString('anchor', $html);
    }

    public function testDropsImagesAndTheLanguageSwitchLine(): void
    {
        $html = $this->renderer()->render(
            "**English** · [Français](README.fr.md)\n\n# Title\n\n![badge](docs/images/badge.png)\n\nText\n"
        );

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('Français', $html);
        $this->assertStringNotContainsString('<p></p>', $html);
        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<p>Text</p>', $html);
    }

    public function testStripsRawHtmlWrappersOfTheReadmeHeader(): void
    {
        $html = $this->renderer()->render("<div align=\"center\">\n\n# Title\n\n</div>\n");

        $this->assertStringNotContainsString('<div', $html);
        $this->assertStringContainsString('<h1>Title</h1>', $html);
    }
}
