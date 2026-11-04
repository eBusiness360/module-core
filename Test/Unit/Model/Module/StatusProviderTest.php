<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Module;

use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Module\ModuleListInterface;
use Maxcode\Core\Model\Catalog\Entry;
use Maxcode\Core\Model\Module\Status;
use Maxcode\Core\Model\Module\StatusProvider;
use Maxcode\Core\Model\Module\VersionResolver;
use Maxcode\Core\Model\Update\LatestVersions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatusProviderTest extends TestCase
{
    private ComponentRegistrarInterface&MockObject $registrar;
    private ModuleListInterface&MockObject $moduleList;
    private VersionResolver&MockObject $versions;
    private LatestVersions&MockObject $latestVersions;

    protected function setUp(): void
    {
        $this->registrar = $this->createMock(ComponentRegistrarInterface::class);
        $this->moduleList = $this->createMock(ModuleListInterface::class);
        $this->versions = $this->createMock(VersionResolver::class);
        $this->latestVersions = $this->createMock(LatestVersions::class);
    }

    private function provider(?string $path, bool $enabled): StatusProvider
    {
        $this->registrar->method('getPath')->willReturn($path);
        $this->moduleList->method('has')->willReturn($enabled);

        return new StatusProvider($this->registrar, $this->moduleList, $this->versions, $this->latestVersions);
    }

    public function testLatestVersionComesFromTheLatestVersionsService(): void
    {
        $this->versions->method('resolve')->willReturn('1.0.0');
        $this->latestVersions->method('forEntry')->willReturn('1.1.0');

        $installed = $this->provider('/path', true)->getStatus($this->entry());

        $this->assertSame('1.1.0', $installed->latestVersion);
        $this->assertTrue($installed->hasUpdate());
    }

    public function testLatestVersionIsAlsoKnownForAModuleThatIsNotInstalled(): void
    {
        $this->latestVersions->method('forEntry')->willReturn('3.0.0');

        $status = $this->provider(null, false)->getStatus($this->entry());

        $this->assertSame('3.0.0', $status->latestVersion);
        $this->assertFalse($status->hasUpdate());
    }

    private function entry(string $availability = Entry::AVAILABILITY_AVAILABLE): Entry
    {
        return new Entry('Vendor_Mod', 'vendor/module-mod', Entry::TYPE_PAID, 'Mod', 'Desc.', null, $availability);
    }

    public function testEnabledModuleIsInstalledWithItsVersion(): void
    {
        $this->versions->method('resolve')->with('vendor/module-mod', '/path')->willReturn('1.2.3');

        $status = $this->provider('/path', true)->getStatus($this->entry());

        $this->assertSame(Status::INSTALLED, $status->state);
        $this->assertSame('1.2.3', $status->version);
        $this->assertTrue($status->isPresent());
    }

    public function testPresentButDisabledModuleIsReportedAsDisabled(): void
    {
        $status = $this->provider('/path', false)->getStatus($this->entry());

        $this->assertSame(Status::DISABLED, $status->state);
        $this->assertTrue($status->isPresent());
    }

    public function testAbsentModuleIsNotInstalled(): void
    {
        $status = $this->provider(null, false)->getStatus($this->entry());

        $this->assertSame(Status::NOT_INSTALLED, $status->state);
        $this->assertNull($status->version);
        $this->assertFalse($status->isPresent());
    }

    public function testAnnouncedModuleIsComingSoon(): void
    {
        $status = $this->provider(null, false)->getStatus($this->entry(Entry::AVAILABILITY_SOON));

        $this->assertSame(Status::COMING_SOON, $status->state);
    }

    public function testIsPresentIgnoresWhetherTheModuleIsEnabled(): void
    {
        $this->assertTrue($this->provider('/path', false)->isPresent('Vendor_Mod'));
    }
}
