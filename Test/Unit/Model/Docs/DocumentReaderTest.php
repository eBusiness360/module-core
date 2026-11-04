<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Docs;

use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Locale\ResolverInterface;
use Maxcode\Core\Model\Docs\DocumentReader;
use Maxcode\Core\Model\Docs\MarkdownRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DocumentReaderTest extends TestCase
{
    private string $dir;
    private ?string $registeredPath;
    private ResolverInterface&MockObject $locale;
    private MarkdownRenderer&MockObject $renderer;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/maxcode_core_docs_' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        $this->registeredPath = $this->dir;
        $this->locale = $this->createMock(ResolverInterface::class);
        $this->renderer = $this->createMock(MarkdownRenderer::class);
        $this->renderer->method('render')->willReturnCallback(static fn (string $md): string => '<p>' . $md . '</p>');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
    }

    private function reader(string $locale = 'en_US'): DocumentReader
    {
        $this->locale->method('getLocale')->willReturn($locale);
        $registrar = $this->createMock(ComponentRegistrarInterface::class);
        $registrar->method('getPath')->willReturnCallback(fn (): ?string => $this->registeredPath);

        return new DocumentReader($registrar, new File(), $this->locale, $this->renderer);
    }

    public function testReadsTheEnglishReadmeByDefault(): void
    {
        file_put_contents($this->dir . '/README.md', 'English');
        file_put_contents($this->dir . '/README.fr.md', 'Français');

        $this->assertTrue($this->reader('en_US')->hasDocument('Maxcode_Mod'));
        $this->assertSame('<p>English</p>', $this->reader('en_US')->getHtml('Maxcode_Mod'));
    }

    public function testPrefersTheAdministratorsLanguage(): void
    {
        file_put_contents($this->dir . '/README.md', 'English');
        file_put_contents($this->dir . '/README.fr.md', 'Français');

        $this->assertSame('<p>Français</p>', $this->reader('fr_FR')->getHtml('Maxcode_Mod'));
    }

    public function testFallsBackToEnglishWhenTheLanguageHasNoReadme(): void
    {
        file_put_contents($this->dir . '/README.md', 'English');

        $this->assertSame('<p>English</p>', $this->reader('de_DE')->getHtml('Maxcode_Mod'));
    }

    public function testNoDocumentWhenTheModuleShipsNoReadme(): void
    {
        $this->assertFalse($this->reader()->hasDocument('Maxcode_Mod'));
        $this->assertNull($this->reader()->getHtml('Maxcode_Mod'));
    }

    public function testNoDocumentForAnUnknownModule(): void
    {
        $this->registeredPath = null;

        $this->assertFalse($this->reader()->hasDocument('Maxcode_Mod'));
    }

    /**
     * @dataProvider unsafeModuleNames
     */
    public function testRefusesAnythingButAMaxcodeModuleName(string $module): void
    {
        file_put_contents($this->dir . '/README.md', 'English');

        $this->assertFalse($this->reader()->hasDocument($module));
        $this->assertNull($this->reader()->getHtml($module));
    }

    public static function unsafeModuleNames(): array
    {
        return [
            'path traversal' => ['../../etc/passwd'],
            'another vendor' => ['Magento_Backend'],
            'empty' => [''],
            'with a slash' => ['Maxcode_Mod/../../x'],
        ];
    }

    public function testIgnoresAnOversizedReadme(): void
    {
        file_put_contents($this->dir . '/README.md', str_repeat('a', 524289));

        $this->assertFalse($this->reader()->hasDocument('Maxcode_Mod'));
    }
}
