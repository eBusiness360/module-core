<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Update;

use Maxcode\Core\Model\Catalog\Entry;
use Maxcode\Core\Model\Catalog\Reader;
use Maxcode\Core\Model\Update\Checker;
use Maxcode\Core\Model\Update\LatestVersionStore;
use Maxcode\Core\Model\Update\PackagistClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckerTest extends TestCase
{
    private PackagistClient&MockObject $client;
    private LatestVersionStore&MockObject $store;
    private Reader&MockObject $catalog;

    protected function setUp(): void
    {
        $this->client = $this->createMock(PackagistClient::class);
        $this->store = $this->createMock(LatestVersionStore::class);
        $this->store->method('all')->willReturn([]);
        $this->catalog = $this->createMock(Reader::class);
        $this->catalog->method('getEntries')->willReturn([
            new Entry('V_A', 'maxcode/module-a', Entry::TYPE_FREE, 'A', 'Desc.'),
            new Entry('V_B', 'maxcode/module-b', Entry::TYPE_FREE, 'B', 'Desc.'),
            new Entry('V_P', 'maxcode/module-paid', Entry::TYPE_PAID, 'Paid', 'Desc.'),
        ]);
    }

    private function checker(): Checker
    {
        return new Checker($this->catalog, $this->client, $this->store);
    }

    public function testQueriesFreeModulesOnlyAndNeverAPaidOne(): void
    {
        $queried = [];
        $this->client->method('latestStableVersion')->willReturnCallback(
            function (string $package) use (&$queried): ?string {
                $queried[] = $package;

                return '1.0.0';
            }
        );

        $this->checker()->check();

        $this->assertSame(['maxcode/module-a', 'maxcode/module-b'], $queried);
    }

    public function testSavesTheVersionsFoundAndReportsTheCounts(): void
    {
        $this->client->method('latestStableVersion')->willReturnMap([
            ['maxcode/module-a', '2.0.4'],
            ['maxcode/module-b', null],
        ]);
        $this->store->expects($this->once())->method('save')->with(['maxcode/module-a' => '2.0.4']);

        $result = $this->checker()->check();

        $this->assertSame(2, $result->queried);
        $this->assertSame(1, $result->found);
        $this->assertSame(0, $result->failed);
    }

    public function testAPartialFailureKeepsThePreviousVersionOfThePackageThatFailed(): void
    {
        $store = $this->createMock(LatestVersionStore::class);
        $store->method('all')->willReturn(['maxcode/module-b' => '1.4.0']);
        $client = $this->createMock(PackagistClient::class);
        $client->method('latestStableVersion')->willReturnCallback(
            static function (string $package): ?string {
                if ($package === 'maxcode/module-b') {
                    throw new \RuntimeException('Packagist a répondu 500.');
                }

                return '2.0.4';
            }
        );
        $store->expects($this->once())->method('save')->with([
            'maxcode/module-b' => '1.4.0',
            'maxcode/module-a' => '2.0.4',
        ]);

        $result = (new Checker($this->catalog, $client, $store))->check();

        $this->assertSame(1, $result->failed);
        $this->assertSame(1, $result->found);
    }

    public function testNothingIsSavedWhenPackagistCannotBeReachedAtAll(): void
    {
        $this->client->method('latestStableVersion')->willThrowException(new \RuntimeException('injoignable'));
        $this->store->expects($this->never())->method('save');

        $this->expectException(\RuntimeException::class);
        $this->checker()->check();
    }

    public function testAPackageThatDisappearedFromPackagistIsForgotten(): void
    {
        $store = $this->createMock(LatestVersionStore::class);
        $store->method('all')->willReturn(['maxcode/module-a' => '9.9.9']);
        $client = $this->createMock(PackagistClient::class);
        $client->method('latestStableVersion')->willReturn(null);
        $store->expects($this->once())->method('save')->with([]);

        (new Checker($this->catalog, $client, $store))->check();
    }
}
