<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Docs;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Maxcode\Core\Model\Docs\DocumentReader;
use Maxcode\Core\Model\Docs\Link;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    private function link(bool $allowed, bool $hasDocument): Link
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->with(Link::ACL_RESOURCE)->willReturn($allowed);
        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturnCallback(
            static fn (string $route, array $params): string => 'admin/' . $route . '/' . $params['module']
        );
        $reader = $this->createMock(DocumentReader::class);
        $reader->method('hasDocument')->willReturn($hasDocument);

        return new Link($authorization, $urlBuilder, $reader);
    }

    public function testBuildsTheLinkWhenTheModuleShipsDocsAndTheAdminMayReadThem(): void
    {
        $this->assertSame('admin/maxcode_core/docs/view/Maxcode_Mod', $this->link(true, true)->forModule('Maxcode_Mod'));
    }

    public function testNoLinkWithoutThePermission(): void
    {
        $this->assertNull($this->link(false, true)->forModule('Maxcode_Mod'));
    }

    public function testNoLinkWithoutDocumentation(): void
    {
        $this->assertNull($this->link(true, false)->forModule('Maxcode_Mod'));
    }
}
