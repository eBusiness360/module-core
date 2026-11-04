<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\ViewModel;

use Magento\Framework\App\RequestInterface;
use Maxcode\Core\Model\Catalog\Entry;
use Maxcode\Core\Model\Catalog\Reader;
use Maxcode\Core\Model\Docs\Link;
use Maxcode\Core\Model\Intro\IntroPool;
use Maxcode\Core\ViewModel\ModuleIntro;
use PHPUnit\Framework\TestCase;

class ModuleIntroTest extends TestCase
{
    private ?string $section = 'vendor_mod';
    private ?string $embeddedDocs = null;

    private function viewModel(array $declaration, ?Entry $entry): ModuleIntro
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            fn (string $name): ?string => $name === 'section' ? $this->section : null
        );
        $catalog = $this->createMock(Reader::class);
        $catalog->method('getByModule')->willReturn($entry);
        $docsLink = $this->createMock(Link::class);
        $docsLink->method('forModule')->willReturnCallback(fn (): ?string => $this->embeddedDocs);

        return new ModuleIntro($request, new IntroPool(['mod' => $declaration]), $catalog, $docsLink);
    }

    private function entry(?string $docsUrl = 'https://github.com/example/module-mod'): Entry
    {
        return new Entry(
            'Vendor_Mod',
            'vendor/module-mod',
            Entry::TYPE_FREE,
            'Mod',
            'Catalogue description.',
            null,
            Entry::AVAILABILITY_AVAILABLE,
            $docsUrl
        );
    }

    private function declaration(array $extra = []): array
    {
        return ['section' => 'vendor_mod', 'module' => 'Vendor_Mod'] + $extra;
    }

    public function testUsesTheCatalogueForNameAndDescription(): void
    {
        $intro = $this->viewModel($this->declaration(), $this->entry())->getIntro();

        $this->assertSame('Mod', $intro['name']);
        $this->assertSame('Catalogue description.', $intro['summary']);
        $this->assertSame([], $intro['points']);
    }

    public function testDeclaredSummaryAndPointsOverrideTheCatalogueDescription(): void
    {
        $intro = $this->viewModel(
            $this->declaration(['summary' => 'Own summary.', 'points' => ['A.', 'B.']]),
            $this->entry()
        )->getIntro();

        $this->assertSame('Own summary.', $intro['summary']);
        $this->assertSame(['A.', 'B.'], $intro['points']);
    }

    public function testEmbeddedDocumentationComesFirstAndTheRepositoryIsASecondLink(): void
    {
        $this->embeddedDocs = 'admin/maxcode/docs/view';

        $links = $this->viewModel($this->declaration(), $this->entry())->getIntro()['links'];

        $this->assertSame([
            ['label' => 'Full documentation', 'url' => 'admin/maxcode/docs/view', 'external' => false],
            ['label' => 'View on GitHub', 'url' => 'https://github.com/example/module-mod', 'external' => true],
        ], $links);
    }

    public function testRepositoryIsTheFullDocumentationWhenNothingIsEmbedded(): void
    {
        $links = $this->viewModel($this->declaration(), $this->entry())->getIntro()['links'];

        $this->assertSame(
            [['label' => 'Full documentation', 'url' => 'https://github.com/example/module-mod', 'external' => true]],
            $links
        );
    }

    public function testPaidModuleWithEmbeddedDocsAndNoRepository(): void
    {
        $this->embeddedDocs = 'admin/maxcode/docs/view';

        $links = $this->viewModel($this->declaration(), $this->entry(null))->getIntro()['links'];

        $this->assertCount(1, $links);
        $this->assertFalse($links[0]['external']);
    }

    public function testNoLinkAtAll(): void
    {
        $this->assertSame([], $this->viewModel($this->declaration(), $this->entry(null))->getIntro()['links']);
    }

    public function testHiddenOnAnotherSection(): void
    {
        $this->section = 'another_section';

        $this->assertNull($this->viewModel($this->declaration(), $this->entry())->getIntro());
    }

    public function testCanBeRequestedByIdOutsideTheConfigPage(): void
    {
        $this->section = null;
        $viewModel = $this->viewModel(['module' => 'Vendor_Mod'], $this->entry());

        $this->assertNull($viewModel->getIntro());
        $this->assertNotNull($viewModel->getIntro('mod'));
        $this->assertNull($viewModel->getIntro('unknown'));
    }

    public function testHiddenWhenTheModuleIsNotInTheCatalogue(): void
    {
        $this->assertNull($this->viewModel($this->declaration(), null)->getIntro());
    }
}
