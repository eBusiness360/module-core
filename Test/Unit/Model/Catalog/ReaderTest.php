<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Catalog;

use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;
use Maxcode\Core\Model\Catalog\Entry;
use Maxcode\Core\Model\Catalog\Reader;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/maxcode_core_' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
    }

    private function reader(?string $content, ?string $registeredPath = null): Reader
    {
        $path = $this->dir . '/catalog.json';
        if ($content !== null) {
            file_put_contents($path, $content);
        }
        $registrar = $this->createMock(ComponentRegistrarInterface::class);
        $registrar->method('getPath')->willReturn($registeredPath);

        return new Reader(new Json(), new File(), $registrar, $registeredPath === null ? $path : '');
    }

    private function row(array $override = []): array
    {
        return array_merge([
            'module' => 'Vendor_Free',
            'package' => 'vendor/module-free',
            'type' => 'free',
            'name' => 'Free',
            'description' => 'A free module.',
            'url' => 'https://example.test/free',
        ], $override);
    }

    public function testReadsValidEntriesInFileOrder(): void
    {
        $reader = $this->reader(json_encode(['modules' => [
            $this->row(),
            $this->row(['module' => 'Vendor_Paid', 'type' => 'paid', 'availability' => 'soon', 'url' => null]),
        ]]));

        $entries = $reader->getEntries();

        $this->assertCount(2, $entries);
        $this->assertSame('Vendor_Free', $entries[0]->module);
        $this->assertFalse($entries[0]->isPaid());
        $this->assertTrue($entries[1]->isPaid());
        $this->assertTrue($entries[1]->isComingSoon());
        $this->assertNull($entries[1]->url);
        $this->assertSame($entries[1], $reader->getByModule('Vendor_Paid'));
        $this->assertNull($reader->getByModule('Vendor_Unknown'));
    }

    /**
     * @dataProvider invalidRows
     */
    public function testSkipsInvalidEntries(array $override): void
    {
        $reader = $this->reader(json_encode(['modules' => [$this->row($override)]]));

        $this->assertSame([], $reader->getEntries());
    }

    public static function invalidRows(): array
    {
        return [
            'no module' => [['module' => '']],
            'unknown type' => [['type' => 'trial']],
            'unknown availability' => [['availability' => 'later']],
            'description is not a string' => [['description' => ['x']]],
        ];
    }

    /**
     * @dataProvider unsafeUrls
     */
    public function testDropsUrlsThatAreNotHttps(string $url): void
    {
        $reader = $this->reader(json_encode(['modules' => [$this->row(['url' => $url])]]));

        $this->assertCount(1, $reader->getEntries());
        $this->assertNull($reader->getEntries()[0]->url);
    }

    public static function unsafeUrls(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'http' => ['http://example.test/'],
            'relative' => ['/promo'],
            'with a quote' => ['https://example.test/"onmouseover="x'],
        ];
    }

    public function testReadsTheDocumentationLinkAndDropsUnsafeOnes(): void
    {
        $reader = $this->reader(json_encode(['modules' => [
            $this->row(['docs_url' => 'https://github.com/example/module-free']),
            $this->row(['module' => 'Vendor_Other', 'docs_url' => 'javascript:alert(1)']),
            $this->row(['module' => 'Vendor_None']),
        ]]));

        $this->assertSame('https://github.com/example/module-free', $reader->getByModule('Vendor_Free')->docsUrl);
        $this->assertNull($reader->getByModule('Vendor_Other')->docsUrl);
        $this->assertNull($reader->getByModule('Vendor_None')->docsUrl);
    }

    public function testPaidModulesNeverLinkToAPrivateRepository(): void
    {
        $shipped = dirname(__DIR__, 4) . '/etc/catalog.json';
        $entries = (new Reader(
            new Json(),
            new File(),
            $this->createMock(ComponentRegistrarInterface::class),
            $shipped
        ))->getEntries();

        foreach ($entries as $entry) {
            if ($entry->isPaid()) {
                $this->assertNull($entry->docsUrl, $entry->module . ' : le dépôt d\'un module payant est privé.');
            } elseif ($entry->docsUrl !== null) {
                $this->assertStringStartsWith('https://github.com/eBusiness360/', $entry->docsUrl);
            }
        }
    }

    public function testReadsLatestVersionsAndDropsAnythingThatIsNotXyz(): void
    {
        $reader = $this->reader(json_encode(['modules' => [
            $this->row(['latest_version' => '2.0.4']),
            $this->row(['module' => 'Vendor_Prefixed', 'latest_version' => 'v2.0.4']),
            $this->row(['module' => 'Vendor_Two', 'latest_version' => '2.0']),
            $this->row(['module' => 'Vendor_Dev', 'latest_version' => 'dev-main']),
            $this->row(['module' => 'Vendor_None']),
        ]]));

        $this->assertSame('2.0.4', $reader->getByModule('Vendor_Free')->latestVersion);
        $this->assertNull($reader->getByModule('Vendor_Prefixed')->latestVersion);
        $this->assertNull($reader->getByModule('Vendor_Two')->latestVersion);
        $this->assertNull($reader->getByModule('Vendor_Dev')->latestVersion);
        $this->assertNull($reader->getByModule('Vendor_None')->latestVersion);
    }

    public function testPublisherLinksHaveSafeDefaults(): void
    {
        $this->assertSame([
            'site' => 'https://www.ebusiness360.fr/',
            'modules' => 'https://www.ebusiness360.fr/modules/',
            'news' => 'https://www.ebusiness360.fr/actus-tech-magento2/',
        ], $this->reader(null)->getPublisherLinks());
    }

    public function testPublisherLinksCanBeOverriddenOneByOneAndStayHttps(): void
    {
        $links = $this->reader(json_encode(['publisher' => [
            'modules' => 'https://publisher.example.test/modules/',
            'news' => 'javascript:alert(1)',
        ], 'modules' => []]))->getPublisherLinks();

        $this->assertSame('https://publisher.example.test/modules/', $links['modules']);
        $this->assertSame('https://www.ebusiness360.fr/actus-tech-magento2/', $links['news']);
        $this->assertSame('https://www.ebusiness360.fr/', $links['site']);
    }

    public function testShippedLatestVersionsAreCoherent(): void
    {
        $shipped = dirname(__DIR__, 4) . '/etc/catalog.json';
        $entries = (new Reader(
            new Json(),
            new File(),
            $this->createMock(ComponentRegistrarInterface::class),
            $shipped
        ))->getEntries();

        foreach ($entries as $entry) {
            if ($entry->isComingSoon()) {
                $this->assertNull($entry->latestVersion, $entry->module . ' n\'est pas encore sorti.');
            } else {
                $this->assertNotNull($entry->latestVersion, $entry->module . ' : dernière version à renseigner.');
            }
        }
    }

    public function testIgnoresDuplicateModules(): void
    {
        $reader = $this->reader(json_encode(['modules' => [$this->row(), $this->row(['name' => 'Other'])]]));

        $this->assertCount(1, $reader->getEntries());
        $this->assertSame('Free', $reader->getEntries()[0]->name);
    }

    public function testMissingOrBrokenFileGivesEmptyCatalogue(): void
    {
        $this->assertSame([], $this->reader(null)->getEntries());
        $this->assertSame([], $this->reader('{not json')->getEntries());
        $this->assertSame([], $this->reader('"text"')->getEntries());
    }

    public function testContactUrlHasASafeDefaultAndCanBeOverridden(): void
    {
        $this->assertStringStartsWith('https://', $this->reader(null)->getContactUrl());
        $this->assertSame(
            'https://example.test/contact',
            $this->reader(json_encode(['contact_url' => 'https://example.test/contact', 'modules' => []]))->getContactUrl()
        );
        $this->assertStringStartsWith(
            'https://',
            $this->reader(json_encode(['contact_url' => 'javascript:x', 'modules' => []]))->getContactUrl()
        );
    }

    public function testDefaultPathComesFromTheRegisteredModuleDirectory(): void
    {
        file_put_contents($this->dir . '/catalog.json', json_encode(['modules' => [$this->row()]]));
        mkdir($this->dir . '/etc');
        copy($this->dir . '/catalog.json', $this->dir . '/etc/catalog.json');

        $reader = $this->reader(null, $this->dir);

        $this->assertCount(1, $reader->getEntries());

        unlink($this->dir . '/etc/catalog.json');
        rmdir($this->dir . '/etc');
    }

    public function testShippedCatalogueIsCompleteAndSafe(): void
    {
        $shipped = dirname(__DIR__, 4) . '/etc/catalog.json';
        $raw = json_decode((string) file_get_contents($shipped), true, 512, JSON_THROW_ON_ERROR);
        $registrar = $this->createMock(ComponentRegistrarInterface::class);
        $reader = new Reader(new Json(), new File(), $registrar, $shipped);

        $entries = $reader->getEntries();

        $this->assertCount(count($raw['modules']), $entries, 'Aucune entrée du catalogue livré ne doit être rejetée.');
        $modules = array_map(static fn (Entry $entry): string => $entry->module, $entries);
        $this->assertSame($modules, array_unique($modules));
        $this->assertContains('Maxcode_Core', $modules);
        foreach ($entries as $entry) {
            $this->assertMatchesRegularExpression('/^Maxcode_[A-Za-z0-9]+$/', $entry->module);
            $this->assertMatchesRegularExpression('#^maxcode/module-[a-z0-9-]+$#', $entry->package);
        }
    }

    public function testEveryShippedDescriptionHasAFrenchTranslation(): void
    {
        $base = dirname(__DIR__, 4);
        $csv = [];
        $handle = fopen($base . '/i18n/fr_FR.csv', 'rb');
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $csv[$row[0]] = $row[1] ?? '';
        }
        fclose($handle);
        $raw = json_decode((string) file_get_contents($base . '/etc/catalog.json'), true, 512, JSON_THROW_ON_ERROR);

        foreach ($raw['modules'] as $row) {
            $this->assertNotEmpty($csv[$row['description']] ?? '', 'Traduction manquante : ' . $row['description']);
        }
    }
}
