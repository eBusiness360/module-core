<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Module;

use Maxcode\Core\Model\Catalog\Entry;
use Maxcode\Core\Model\Catalog\Reader;
use Maxcode\Core\Model\Module\Status;
use Maxcode\Core\Model\Module\StatusProvider;
use Maxcode\Core\Model\Module\UpdateCounter;
use PHPUnit\Framework\TestCase;

class UpdateCounterTest extends TestCase
{
    private function counter(array $statuses): UpdateCounter
    {
        $entries = array_map(
            static fn (string $module): Entry => new Entry($module, 'v/' . $module, Entry::TYPE_FREE, $module, 'Desc.'),
            array_keys($statuses)
        );
        $catalog = $this->createMock(Reader::class);
        $catalog->method('getEntries')->willReturn($entries);
        $provider = $this->createMock(StatusProvider::class);
        $provider->method('getStatus')->willReturnCallback(
            static fn (Entry $entry): Status => $statuses[$entry->module]
        );

        return new UpdateCounter($catalog, $provider);
    }

    public function testCountsOnlyInstalledModulesWithANewerVersion(): void
    {
        $counter = $this->counter([
            'A' => new Status(Status::INSTALLED, '2.0.3', '2.0.4'),
            'B' => new Status(Status::INSTALLED, '1.0.0', '1.0.1'),
            'C' => new Status(Status::INSTALLED, '1.0.2', '1.0.2'),
            'D' => new Status(Status::NOT_INSTALLED, null, '9.0.0'),
        ]);

        $this->assertSame(2, $counter->count());
    }

    public function testZeroWhenEverythingIsUpToDate(): void
    {
        $this->assertSame(0, $this->counter(['A' => new Status(Status::INSTALLED, '1.0.0', '1.0.0')])->count());
        $this->assertSame(0, $this->counter([])->count());
    }
}
