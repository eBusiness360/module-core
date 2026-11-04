<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Update;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Maxcode\Core\Model\Update\Fetcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FetcherTest extends TestCase
{
    private Curl&MockObject $curl;
    private CurlFactory&MockObject $factory;

    protected function setUp(): void
    {
        $this->curl = $this->createMock(Curl::class);
        $this->factory = $this->createMock(CurlFactory::class);
        $this->factory->method('create')->willReturn($this->curl);
    }

    /**
     * @dataProvider refusedAddresses
     */
    public function testNeverContactsAnythingButPackagist(string $url): void
    {
        $this->factory->expects($this->never())->method('create');

        $this->expectException(\RuntimeException::class);
        (new Fetcher($this->factory))->get($url);
    }

    public static function refusedAddresses(): array
    {
        return [
            'another host' => ['https://example.test/p2/maxcode/module-core.json'],
            'plain http' => ['http://repo.packagist.org/p2/maxcode/module-core.json'],
            'lookalike host' => ['https://repo.packagist.org.evil.test/p2/x.json'],
            'userinfo trick' => ['https://repo.packagist.org@evil.test/p2/x.json'],
            'other scheme' => ['file:///etc/passwd'],
        ];
    }

    public function testReturnsStatusAndBody(): void
    {
        $this->curl->expects($this->once())->method('setTimeout');
        $this->curl->expects($this->once())->method('get')->with('https://repo.packagist.org/p2/maxcode/module-core.json');
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"packages":{}}');

        $this->assertSame(
            ['status' => 200, 'body' => '{"packages":{}}'],
            (new Fetcher($this->factory))->get('https://repo.packagist.org/p2/maxcode/module-core.json')
        );
    }

    public function testNetworkFailureBecomesARuntimeException(): void
    {
        $this->curl->method('get')->willThrowException(new \Exception('Could not resolve host'));

        $this->expectException(\RuntimeException::class);
        (new Fetcher($this->factory))->get('https://repo.packagist.org/p2/maxcode/module-core.json');
    }

    public function testStatusZeroMeansNoAnswer(): void
    {
        $this->curl->method('getStatus')->willReturn(0);

        $this->expectException(\RuntimeException::class);
        (new Fetcher($this->factory))->get('https://repo.packagist.org/p2/maxcode/module-core.json');
    }
}
