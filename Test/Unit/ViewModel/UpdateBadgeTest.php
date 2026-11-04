<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\ViewModel;

use Maxcode\Core\Model\Module\UpdateCounter;
use Maxcode\Core\ViewModel\UpdateBadge;
use PHPUnit\Framework\TestCase;

class UpdateBadgeTest extends TestCase
{
    private function viewModel(int $count): UpdateBadge
    {
        $counter = $this->createMock(UpdateCounter::class);
        $counter->method('count')->willReturn($count);

        return new UpdateBadge($counter);
    }

    public function testConfigCarriesTheCountAndAnAccessibleLabel(): void
    {
        $this->assertSame(
            ['count' => 2, 'label' => 'Maxcode modules to update: 2'],
            $this->viewModel(2)->getConfig()
        );
    }

    public function testNoBadgeWhenEverythingIsUpToDate(): void
    {
        $this->assertNull($this->viewModel(0)->getConfig());
    }
}
