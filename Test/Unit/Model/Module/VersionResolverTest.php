<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Module;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;
use Maxcode\Core\Model\Module\VersionResolver;
use PHPUnit\Framework\TestCase;

class VersionResolverTest extends TestCase
{
    private const UNKNOWN_PACKAGE = 'maxcode/module-not-installed-by-composer';

    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/maxcode_core_v_' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
    }

    private function resolver(): VersionResolver
    {
        return new VersionResolver(new File(), new Json());
    }

    public function testReadsTheVersionFieldOfTheModuleComposerJson(): void
    {
        file_put_contents($this->dir . '/composer.json', '{"version": "2.4.2"}');
        file_put_contents($this->dir . '/CHANGELOG.md', "## [9.9.9] - 2026-01-01\n");

        $this->assertSame('2.4.2', $this->resolver()->resolve(self::UNKNOWN_PACKAGE, $this->dir));
    }

    public function testFallsBackToTheFirstVersionOfTheChangelog(): void
    {
        file_put_contents($this->dir . '/composer.json', '{"name": "maxcode/x"}');
        file_put_contents(
            $this->dir . '/CHANGELOG.md',
            "# Changelog\n\n## [Non publié]\n\n## [1.3.0] — 2026-10-06 — Titre\n\n## [1.2.0] — 2026-09-01\n"
        );

        $this->assertSame('1.3.0', $this->resolver()->resolve(self::UNKNOWN_PACKAGE, $this->dir));
    }

    public function testStripsTheLeadingV(): void
    {
        file_put_contents($this->dir . '/composer.json', '{"version": "v2.0.4"}');

        $this->assertSame('2.0.4', $this->resolver()->resolve(self::UNKNOWN_PACKAGE, $this->dir));
    }

    public function testReturnsNullWhenNothingGivesAVersion(): void
    {
        file_put_contents($this->dir . '/composer.json', '{not json');

        $this->assertNull($this->resolver()->resolve(self::UNKNOWN_PACKAGE, $this->dir));
        $this->assertNull($this->resolver()->resolve(self::UNKNOWN_PACKAGE, null));
        $this->assertNull($this->resolver()->resolve(self::UNKNOWN_PACKAGE, $this->dir . '/absent'));
    }
}
