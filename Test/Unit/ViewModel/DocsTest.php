<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\ViewModel;

use Magento\Framework\UrlInterface;
use Maxcode\Core\Model\Catalog\Entry;
use Maxcode\Core\Model\Catalog\Reader;
use Maxcode\Core\Model\Docs\DocumentReader;
use Maxcode\Core\ViewModel\Docs;
use PHPUnit\Framework\TestCase;

class DocsTest extends TestCase
{
    private function viewModel(?Entry $entry, ?string $html): Docs
    {
        $catalog = $this->createMock(Reader::class);
        $catalog->method('getByModule')->willReturn($entry);
        $documents = $this->createMock(DocumentReader::class);
        $documents->method('getHtml')->willReturn($html);
        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturnCallback(
            static fn (string $route, array $params = []): string => 'admin/' . $route . '/' . ($params['section'] ?? '')
        );

        return new Docs($catalog, $documents, $urlBuilder);
    }

    private function entry(): Entry
    {
        return new Entry('Maxcode_Mod', 'maxcode/module-mod', Entry::TYPE_PAID, 'Mod', 'Desc.');
    }

    public function testReturnsTheNameAndTheRenderedDocument(): void
    {
        $this->assertSame(
            ['name' => 'Mod', 'html' => '<p>Doc</p>'],
            $this->viewModel($this->entry(), '<p>Doc</p>')->getDocument('Maxcode_Mod')
        );
    }

    public function testNothingForAModuleOutsideTheCatalogue(): void
    {
        $this->assertNull($this->viewModel(null, '<p>Doc</p>')->getDocument('Maxcode_Mod'));
    }

    public function testNothingWhenTheModuleShipsNoDocument(): void
    {
        $this->assertNull($this->viewModel($this->entry(), null)->getDocument('Maxcode_Mod'));
    }

    public function testModulesUrl(): void
    {
        $this->assertSame(
            'admin/adminhtml/system_config/edit/maxcode_core',
            $this->viewModel(null, null)->getModulesUrl()
        );
    }
}
