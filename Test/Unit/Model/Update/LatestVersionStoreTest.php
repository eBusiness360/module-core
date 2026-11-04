<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Update;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Maxcode\Core\Model\Update\LatestVersionStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LatestVersionStoreTest extends TestCase
{
    private ScopeConfigInterface&MockObject $scopeConfig;
    private WriterInterface&MockObject $writer;
    private TypeListInterface&MockObject $cacheTypes;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->writer = $this->createMock(WriterInterface::class);
        $this->cacheTypes = $this->createMock(TypeListInterface::class);
    }

    private function store(): LatestVersionStore
    {
        return new LatestVersionStore($this->scopeConfig, $this->writer, $this->cacheTypes, new Json());
    }

    private function stored(mixed $latest, mixed $checkedAt = null): void
    {
        $this->scopeConfig->method('getValue')->willReturnCallback(
            static fn (string $path): mixed => match ($path) {
                LatestVersionStore::PATH_LATEST => $latest,
                LatestVersionStore::PATH_CHECKED_AT => $checkedAt,
                default => null,
            }
        );
    }

    public function testNothingStoredByDefault(): void
    {
        $this->stored(null);

        $this->assertSame([], $this->store()->all());
        $this->assertNull($this->store()->getCheckedAt());
    }

    public function testReadsTheStoredVersions(): void
    {
        $this->stored('{"maxcode/module-a":"2.0.4","maxcode/module-b":"1.0.1"}', '2026-10-29T21:05:00+00:00');

        $this->assertSame(
            ['maxcode/module-a' => '2.0.4', 'maxcode/module-b' => '1.0.1'],
            $this->store()->all()
        );
        $this->assertSame('2026-10-29T21:05:00+00:00', $this->store()->getCheckedAt());
    }

    public function testDropsEntriesThatAreNotXyzVersions(): void
    {
        $this->stored('{"maxcode/module-a":"2.0.4","maxcode/module-b":"v1.0","maxcode/module-c":["x"],"maxcode/module-d":"dev-main"}');

        $this->assertSame(['maxcode/module-a' => '2.0.4'], $this->store()->all());
    }

    /**
     * @dataProvider corruptValues
     */
    public function testCorruptStorageIsTreatedAsEmpty(mixed $value): void
    {
        $this->stored($value);

        $this->assertSame([], $this->store()->all());
    }

    public static function corruptValues(): array
    {
        return [
            'not json' => ['{broken'],
            'json scalar' => ['"text"'],
            'empty' => [''],
            'not a string' => [['x']],
        ];
    }

    public function testSaveWritesBothValuesAndRefreshesTheConfigCache(): void
    {
        $written = [];
        $this->writer->expects($this->exactly(2))->method('save')->willReturnCallback(
            function (string $path, string $value) use (&$written): void {
                $written[$path] = $value;
            }
        );
        $this->cacheTypes->expects($this->once())->method('cleanType')->with('config');

        $this->store()->save(['maxcode/module-a' => '2.0.4']);

        $this->assertSame(
            ['maxcode/module-a' => '2.0.4'],
            json_decode($written[LatestVersionStore::PATH_LATEST], true)
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/',
            $written[LatestVersionStore::PATH_CHECKED_AT]
        );
    }
}
