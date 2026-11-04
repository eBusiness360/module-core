<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\ViewModel;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Maxcode\Core\Model\Catalog\Entry;
use Maxcode\Core\Model\Catalog\Reader;
use Maxcode\Core\Model\Docs\Link;
use Maxcode\Core\Model\Module\Status;
use Maxcode\Core\Model\Module\StatusProvider;
use Maxcode\Core\Model\Update\LatestVersionStore;
use Maxcode\Core\ViewModel\Modules;
use PHPUnit\Framework\TestCase;

class ModulesTest extends TestCase
{
    private function viewModel(array $entries, array $statuses): Modules
    {
        $catalog = $this->createMock(Reader::class);
        $catalog->method('getEntries')->willReturn($entries);
        $catalog->method('getContactUrl')->willReturn('https://example.test/contact');
        $provider = $this->createMock(StatusProvider::class);
        $provider->method('getStatus')->willReturnCallback(
            static fn (Entry $entry): Status => $statuses[$entry->module]
        );
        $docsLink = $this->createMock(Link::class);
        $docsLink->method('forModule')->willReturnCallback(
            static fn (string $module): ?string => $module === 'V_Free' ? 'admin/maxcode/docs/view' : null
        );

        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturnCallback(static fn (string $route): string => 'admin/' . $route);
        $store = $this->createMock(LatestVersionStore::class);
        $store->method('getCheckedAt')->willReturn($this->checkedAt);
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('formatDateTime')->willReturnCallback(
            static fn (\DateTimeInterface $date): string => 'local ' . $date->format('Y-m-d H:i')
        );

        return new Modules($catalog, $provider, $docsLink, $urlBuilder, $store, $timezone);
    }

    private ?string $checkedAt = null;

    public function testCheckUrlUsesTheRouteIdSoTheSecretKeyIsValid(): void
    {
        $this->assertSame('admin/maxcode_core/updates/check', $this->viewModel([], [])->getCheckUrl());
    }

    public function testLastCheckIsAbsentUntilAnAdministratorHasChecked(): void
    {
        $this->assertNull($this->viewModel([], [])->getLastCheckLabel());
    }

    public function testLastCheckIsShownInLocalTime(): void
    {
        $this->checkedAt = '2026-10-29T21:05:00+00:00';

        $this->assertSame('local 2026-10-29 21:05', $this->viewModel([], [])->getLastCheckLabel());
    }

    public function testAnUnreadableCheckDateIsIgnored(): void
    {
        $this->checkedAt = 'not a date';

        $this->assertNull($this->viewModel([], [])->getLastCheckLabel());
    }

    public function testSplitsFreeAndPaidModules(): void
    {
        $groups = $this->viewModel(
            [
                new Entry('V_Free', 'v/free', Entry::TYPE_FREE, 'Free', 'Free desc.', 'https://example.test/free'),
                new Entry('V_Paid', 'v/paid', Entry::TYPE_PAID, 'Paid', 'Paid desc.'),
            ],
            ['V_Free' => new Status(Status::INSTALLED, '1.0.0'), 'V_Paid' => new Status(Status::NOT_INSTALLED)]
        )->getGroups();

        $this->assertCount(1, $groups['free']);
        $this->assertCount(1, $groups['paid']);
        $this->assertSame('Free of charge', $groups['free'][0]['type_label']);
        $this->assertSame('Paid extension', $groups['paid'][0]['type_label']);
        $this->assertSame('Installed', $groups['free'][0]['status_label']);
        $this->assertSame('1.0.0', $groups['free'][0]['version']);
        $this->assertSame('Not installed', $groups['paid'][0]['status_label']);
        $this->assertNull($groups['paid'][0]['version']);
    }

    public function testLinksToTheDetailsPageWhenThereIsOne(): void
    {
        $row = $this->viewModel(
            [new Entry('V_Free', 'v/free', Entry::TYPE_FREE, 'Free', 'Desc.', 'https://example.test/free')],
            ['V_Free' => new Status(Status::INSTALLED)]
        )->getGroups()['free'][0];

        $this->assertSame('https://example.test/free', $row['url']);
        $this->assertSame('View details', $row['link_label']);
        $this->assertSame('Installed', $row['status_label']);
    }

    public function testMissingPaidModuleWithoutPageLinksToContact(): void
    {
        $row = $this->viewModel(
            [new Entry('V_Paid', 'v/paid', Entry::TYPE_PAID, 'Paid', 'Desc.')],
            ['V_Paid' => new Status(Status::COMING_SOON)]
        )->getGroups()['paid'][0];

        $this->assertSame('https://example.test/contact', $row['url']);
        $this->assertSame('Contact us', $row['link_label']);
        $this->assertSame('Coming soon', $row['status_label']);
    }

    public function testInstalledModuleWithoutPageHasNoLink(): void
    {
        $groups = $this->viewModel(
            [
                new Entry('V_Free', 'v/free', Entry::TYPE_FREE, 'Free', 'Desc.'),
                new Entry('V_Paid', 'v/paid', Entry::TYPE_PAID, 'Paid', 'Desc.'),
            ],
            ['V_Free' => new Status(Status::INSTALLED), 'V_Paid' => new Status(Status::DISABLED, '2.0.0')]
        )->getGroups();

        $this->assertNull($groups['free'][0]['url']);
        $this->assertNull($groups['paid'][0]['url']);
        $this->assertSame('Installed but disabled', $groups['paid'][0]['status_label']);
        $this->assertSame('2.0.0', $groups['paid'][0]['version']);
    }

    public function testFlagsModulesThatHaveANewerVersionAndCountsThem(): void
    {
        $viewModel = $this->viewModel(
            [
                new Entry('V_Old', 'v/old', Entry::TYPE_FREE, 'Old', 'Desc.'),
                new Entry('V_Current', 'v/current', Entry::TYPE_FREE, 'Current', 'Desc.'),
                new Entry('V_Missing', 'v/missing', Entry::TYPE_PAID, 'Missing', 'Desc.'),
            ],
            [
                'V_Old' => new Status(Status::INSTALLED, '2.0.3', '2.0.4'),
                'V_Current' => new Status(Status::INSTALLED, '1.0.2', '1.0.2'),
                'V_Missing' => new Status(Status::NOT_INSTALLED, null, '3.0.0'),
            ]
        );
        $groups = $viewModel->getGroups();

        $this->assertTrue($groups['free'][0]['has_update']);
        $this->assertSame('2.0.4', $groups['free'][0]['latest_version']);
        $this->assertFalse($groups['free'][1]['has_update']);
        $this->assertFalse($groups['paid'][0]['has_update'], 'Un module non installé n\'est pas « à mettre à jour ».');
        $this->assertSame('3.0.0', $groups['paid'][0]['latest_version']);
        $this->assertSame(1, $viewModel->getUpdateCount());
    }

    public function testDocumentationLinkOnlyForInstalledModulesThatShipOne(): void
    {
        $groups = $this->viewModel(
            [
                new Entry('V_Free', 'v/free', Entry::TYPE_FREE, 'Free', 'Desc.'),
                new Entry('V_Paid', 'v/paid', Entry::TYPE_PAID, 'Paid', 'Desc.'),
            ],
            ['V_Free' => new Status(Status::INSTALLED), 'V_Paid' => new Status(Status::NOT_INSTALLED)]
        )->getGroups();

        $this->assertSame('admin/maxcode/docs/view', $groups['free'][0]['docs_url']);
        $this->assertNull($groups['paid'][0]['docs_url']);
    }
}
