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
use Maxcode\Core\Model\Update\LatestVersions;
use Maxcode\Core\Model\Update\LatestVersionStore;
use PHPUnit\Framework\TestCase;

class LatestVersionsTest extends TestCase
{
    private function latest(array $stored, ?string $embedded): ?string
    {
        $store = $this->createMock(LatestVersionStore::class);
        $store->method('all')->willReturn($stored);
        $entry = new Entry(
            'V_Mod',
            'maxcode/module-mod',
            Entry::TYPE_FREE,
            'Mod',
            'Desc.',
            null,
            Entry::AVAILABILITY_AVAILABLE,
            null,
            $embedded
        );

        return (new LatestVersions($store))->forEntry($entry);
    }

    public function testTheMostRecentOfTheCatalogueAndTheCheckWins(): void
    {
        $this->assertSame('2.0.5', $this->latest(['maxcode/module-mod' => '2.0.5'], '2.0.4'));
        $this->assertSame('2.0.10', $this->latest(['maxcode/module-mod' => '2.0.4'], '2.0.10'));
        $this->assertSame('1.10.0', $this->latest(['maxcode/module-mod' => '1.10.0'], '1.9.9'));
    }

    public function testFallsBackToWhicheverExists(): void
    {
        $this->assertSame('2.0.4', $this->latest([], '2.0.4'));
        $this->assertSame('3.0.0', $this->latest(['maxcode/module-mod' => '3.0.0'], null));
        $this->assertNull($this->latest([], null));
    }

    public function testIgnoresChecksOfOtherPackages(): void
    {
        $this->assertSame('1.0.0', $this->latest(['maxcode/module-other' => '9.9.9'], '1.0.0'));
    }
}
