<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Module;

use Maxcode\Core\Model\Module\Status;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    /**
     * @dataProvider updates
     */
    public function testHasUpdate(string $state, ?string $installed, ?string $latest, bool $expected): void
    {
        $this->assertSame($expected, (new Status($state, $installed, $latest))->hasUpdate());
    }

    public static function updates(): array
    {
        return [
            'older patch' => [Status::INSTALLED, '2.0.3', '2.0.4', true],
            'older minor' => [Status::INSTALLED, '1.9.9', '1.10.0', true],
            'older major' => [Status::DISABLED, '1.4.0', '2.0.0', true],
            'same version' => [Status::INSTALLED, '1.0.2', '1.0.2', false],
            'newer than the catalogue' => [Status::INSTALLED, '2.1.0', '2.0.4', false],
            'latest unknown' => [Status::INSTALLED, '1.0.0', null, false],
            'installed version unknown' => [Status::INSTALLED, null, '1.0.0', false],
            'not installed' => [Status::NOT_INSTALLED, null, '1.0.0', false],
            'coming soon' => [Status::COMING_SOON, null, '1.0.0', false],
            'development version' => [Status::INSTALLED, 'dev-main', '2.0.4', false],
            'unordered latest' => [Status::INSTALLED, '1.0.0', 'next', false],
        ];
    }
}
