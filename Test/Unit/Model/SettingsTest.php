<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Maxcode\Core\Model\Settings;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    private function settings(mixed $stored, string $decrypted = ''): Settings
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->with(Settings::XML_PATH_CLAUDE_API_KEY)->willReturn($stored);
        $encryptor = $this->createMock(EncryptorInterface::class);
        $encryptor->method('decrypt')->with($stored)->willReturn($decrypted);

        return new Settings($scopeConfig, $encryptor);
    }

    public function testReturnsTheDecryptedKey(): void
    {
        $settings = $this->settings('0:3:encrypted', '  sk-test-key  ');

        $this->assertSame('sk-test-key', $settings->getClaudeApiKey());
        $this->assertTrue($settings->hasClaudeApiKey());
    }

    /**
     * @dataProvider emptyValues
     */
    public function testNoKeyWhenNothingIsStored(mixed $stored): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn($stored);
        $encryptor = $this->createMock(EncryptorInterface::class);
        $encryptor->expects($this->never())->method('decrypt');
        $settings = new Settings($scopeConfig, $encryptor);

        $this->assertNull($settings->getClaudeApiKey());
        $this->assertFalse($settings->hasClaudeApiKey());
    }

    public static function emptyValues(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'blank' => ['   '],
            'not a string' => [['x']],
        ];
    }

    public function testNoKeyWhenDecryptionGivesNothing(): void
    {
        $this->assertNull($this->settings('0:3:garbage', '')->getClaudeApiKey());
    }
}
