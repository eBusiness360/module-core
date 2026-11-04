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
use Maxcode\Core\Model\Catalog\Reader;
use Maxcode\Core\ViewModel\Publisher;
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase
{
    private function viewModel(?string $section): Publisher
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            static fn (string $name): ?string => $name === 'section' ? $section : null
        );
        $catalog = $this->createMock(Reader::class);
        $catalog->method('getPublisherLinks')->willReturn([
            'site' => 'https://publisher.example.test/',
            'modules' => 'https://publisher.example.test/modules/',
            'news' => 'https://publisher.example.test/news/',
        ]);

        return new Publisher($request, $catalog);
    }

    public function testShownOnTheCoreSection(): void
    {
        $banner = $this->viewModel('maxcode_core')->getBanner();

        $this->assertSame(
            [
                ['label' => 'Discover the modules on eBusiness360.fr', 'url' => 'https://publisher.example.test/modules/', 'primary' => true],
                ['label' => 'Visit the website', 'url' => 'https://publisher.example.test/', 'primary' => false],
                ['label' => 'Follow the Magento 2 tech news', 'url' => 'https://publisher.example.test/news/', 'primary' => false],
            ],
            $banner['links']
        );
        $this->assertCount(3, $banner['points']);
    }

    /**
     * @dataProvider otherPages
     */
    public function testHiddenEverywhereElse(?string $section): void
    {
        $this->assertNull($this->viewModel($section)->getBanner());
    }

    public static function otherPages(): array
    {
        return [
            'another Maxcode section' => ['maxcode_product_question'],
            'a Magento section' => ['web'],
            'no section' => [null],
        ];
    }
}
