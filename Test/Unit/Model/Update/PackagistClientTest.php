<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Update;

use Magento\Framework\Serialize\Serializer\Json;
use Maxcode\Core\Model\Update\Fetcher;
use Maxcode\Core\Model\Update\PackagistClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PackagistClientTest extends TestCase
{
    private Fetcher&MockObject $fetcher;

    protected function setUp(): void
    {
        $this->fetcher = $this->createMock(Fetcher::class);
    }

    private function client(): PackagistClient
    {
        return new PackagistClient($this->fetcher, new Json());
    }

    private function respond(int $status, string $body): void
    {
        $this->fetcher->method('get')->willReturn(['status' => $status, 'body' => $body]);
    }

    private function releases(array $versions): string
    {
        return json_encode(['packages' => ['maxcode/module-x' => array_map(
            static fn (string $version): array => ['version' => $version],
            $versions
        )]]);
    }

    public function testPicksTheHighestStableVersionWhateverTheOrder(): void
    {
        $this->respond(200, $this->releases(['v2.0.1', 'v2.0.4', 'v2.0.3', 'v2.0.10', 'v1.9.9']));

        $this->assertSame('2.0.10', $this->client()->latestStableVersion('maxcode/module-x'));
    }

    public function testAcceptsVersionsWithoutTheLeadingV(): void
    {
        $this->respond(200, $this->releases(['1.0.0', '1.0.1']));

        $this->assertSame('1.0.1', $this->client()->latestStableVersion('maxcode/module-x'));
    }

    public function testIgnoresDevelopmentAndPreReleaseVersions(): void
    {
        $this->respond(200, $this->releases(['dev-main', '3.0.0-beta1', '3.0.0-RC2', '2.5.0', 'v2.6.0-alpha']));

        $this->assertSame('2.5.0', $this->client()->latestStableVersion('maxcode/module-x'));
    }

    public function testNullWhenThePackageHasNoStableVersion(): void
    {
        $this->respond(200, $this->releases(['dev-main', '1.0.0-beta1']));

        $this->assertNull($this->client()->latestStableVersion('maxcode/module-x'));
    }

    public function testNullWhenThePackageIsNotPublished(): void
    {
        $this->respond(404, '{"status":"error"}');

        $this->assertNull($this->client()->latestStableVersion('maxcode/module-x'));
    }

    /**
     * @dataProvider failures
     */
    public function testFailuresAreReported(int $status, string $body): void
    {
        $this->respond($status, $body);

        $this->expectException(\RuntimeException::class);
        $this->client()->latestStableVersion('maxcode/module-x');
    }

    public static function failures(): array
    {
        return [
            'server error' => [500, ''],
            'rate limited' => [429, ''],
            'unreadable answer' => [200, '{not json'],
        ];
    }

    public function testNetworkFailureFromTheFetcherPropagates(): void
    {
        $this->fetcher->method('get')->willThrowException(new \RuntimeException('Packagist est injoignable.'));

        $this->expectException(\RuntimeException::class);
        $this->client()->latestStableVersion('maxcode/module-x');
    }

    /**
     * @dataProvider unsafePackageNames
     */
    public function testRefusesAnythingButAMaxcodeModulePackageWithoutCallingTheNetwork(string $package): void
    {
        $this->fetcher->expects($this->never())->method('get');

        $this->assertNull($this->client()->latestStableVersion($package));
    }

    public static function unsafePackageNames(): array
    {
        return [
            'another vendor' => ['magento/module-catalog'],
            'path traversal' => ['maxcode/../../etc/passwd'],
            'query string' => ['maxcode/module-x?x=1'],
            'empty' => [''],
            'upper case' => ['maxcode/Module-X'],
        ];
    }

    public function testBuildsTheUrlFromThePackageNameOnly(): void
    {
        $this->fetcher->expects($this->once())->method('get')
            ->with('https://repo.packagist.org/p2/maxcode/module-x.json')
            ->willReturn(['status' => 404, 'body' => '']);

        $this->client()->latestStableVersion('maxcode/module-x');
    }
}
