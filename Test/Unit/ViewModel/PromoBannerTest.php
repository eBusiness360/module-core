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
use Maxcode\Core\Model\Module\StatusProvider;
use Maxcode\Core\Model\Promo\PromotionPool;
use Maxcode\Core\ViewModel\PromoBanner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PromoBannerTest extends TestCase
{
    private RequestInterface&MockObject $request;
    private Reader&MockObject $catalog;
    private StatusProvider&MockObject $statusProvider;
    private ?string $section = 'vendor_free';
    private array $declaration;

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->request->method('getParam')->willReturnCallback(
            fn (string $name): ?string => $name === 'section' ? $this->section : null
        );
        $this->catalog = $this->createMock(Reader::class);
        $this->catalog->method('getContactUrl')->willReturn('https://example.test/contact');
        $this->statusProvider = $this->createMock(StatusProvider::class);
        $this->declaration = [
            'section' => 'vendor_free',
            'paid_module' => 'Vendor_Paid',
            'text' => 'Do more.',
        ];
    }

    private function entry(string $type = Entry::TYPE_PAID, ?string $url = 'https://example.test/paid'): Entry
    {
        return new Entry('Vendor_Paid', 'vendor/module-paid', $type, 'Paid Pro', 'Desc.', $url);
    }

    private function viewModel(?Entry $entry): PromoBanner
    {
        $this->catalog->method('getByModule')->willReturn($entry);

        return new PromoBanner(
            $this->request,
            new PromotionPool(['vendor_free' => $this->declaration]),
            $this->catalog,
            $this->statusProvider
        );
    }

    public function testBuildsTheBannerForTheCurrentSection(): void
    {
        $banner = $this->viewModel($this->entry())->getBanner();

        $this->assertSame('Go further with Paid Pro', $banner['title']);
        $this->assertSame('Do more.', $banner['text']);
        $this->assertSame('https://example.test/paid', $banner['url']);
        $this->assertSame('Learn more', $banner['link_label']);
        $this->assertSame('Vendor_Paid', $banner['module']);
        $this->assertArrayNotHasKey('dismiss_url', $banner, 'Le bandeau n\'est pas fermable.');
    }

    public function testExplicitTitleAndUrlWin(): void
    {
        $this->declaration += ['title' => 'Custom title', 'url' => 'https://example.test/custom'];

        $banner = $this->viewModel($this->entry())->getBanner();

        $this->assertSame('Custom title', $banner['title']);
        $this->assertSame('https://example.test/custom', $banner['url']);
    }

    public function testFallsBackToTheContactPageWhenThereIsNoDetailsPage(): void
    {
        $banner = $this->viewModel($this->entry(Entry::TYPE_PAID, null))->getBanner();

        $this->assertSame('https://example.test/contact', $banner['url']);
        $this->assertSame('Contact us', $banner['link_label']);
    }

    public function testCanBeRequestedByPromotionIdOutsideTheConfigPage(): void
    {
        $this->section = null;

        $this->assertNull($this->viewModel($this->entry())->getBanner());
        $this->assertNotNull($this->viewModel($this->entry())->getBanner('vendor_free'));
        $this->assertNull($this->viewModel($this->entry())->getBanner('unknown'));
    }

    public function testHiddenOnAnotherSection(): void
    {
        $this->section = 'another_section';

        $this->assertNull($this->viewModel($this->entry())->getBanner());
    }

    public function testHiddenWhenThePaidModuleIsInstalled(): void
    {
        $this->statusProvider->method('isPresent')->with('Vendor_Paid')->willReturn(true);

        $this->assertNull($this->viewModel($this->entry())->getBanner());
    }

    public function testHiddenWhenThePromotedModuleIsUnknown(): void
    {
        $this->assertNull($this->viewModel(null)->getBanner());
    }

    public function testNeverPromotesAFreeModule(): void
    {
        $this->assertNull($this->viewModel($this->entry(Entry::TYPE_FREE))->getBanner());
    }
}
