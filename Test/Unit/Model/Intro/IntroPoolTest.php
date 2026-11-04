<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Intro;

use Maxcode\Core\Model\Intro\IntroPool;
use PHPUnit\Framework\TestCase;

class IntroPoolTest extends TestCase
{
    public function testBuildsAnIntroFromADeclaration(): void
    {
        $pool = new IntroPool(['mod' => [
            'section' => 'vendor_mod',
            'module' => 'Vendor_Mod',
            'summary' => 'What it does.',
            'points' => ['First.', 'Second.'],
        ]]);

        $intro = $pool->get('mod');

        $this->assertNotNull($intro);
        $this->assertSame('Vendor_Mod', $intro->module);
        $this->assertSame('What it does.', $intro->summary);
        $this->assertSame(['First.', 'Second.'], $intro->points);
        $this->assertSame($intro, $pool->findBySection('vendor_mod'));
        $this->assertNull($pool->findBySection('other'));
    }

    public function testOnlyTheModuleIsRequired(): void
    {
        $intro = (new IntroPool(['mod' => ['module' => 'Vendor_Mod']]))->get('mod');

        $this->assertNull($intro->summary);
        $this->assertNull($intro->section);
        $this->assertSame([], $intro->points);
    }

    /**
     * @dataProvider invalidDeclarations
     */
    public function testIgnoresInvalidDeclarations(mixed $declaration): void
    {
        $this->assertNull((new IntroPool(['broken' => $declaration]))->get('broken'));
    }

    public static function invalidDeclarations(): array
    {
        return [
            'no module' => [['summary' => 'Text.']],
            'blank module' => [['module' => '  ']],
            'not an array' => ['Vendor_Mod'],
        ];
    }

    public function testDropsBlankPointsAndCapsTheirNumber(): void
    {
        $points = ['One.', '', '  ', 12, 'Two.', 'Three.', 'Four.', 'Five.', 'Six.', 'Seven.', 'Eight.'];

        $intro = (new IntroPool(['mod' => ['module' => 'Vendor_Mod', 'points' => $points]]))->get('mod');

        $this->assertSame(['One.', 'Two.', 'Three.', 'Four.', 'Five.', 'Six.'], $intro->points);
    }
}
