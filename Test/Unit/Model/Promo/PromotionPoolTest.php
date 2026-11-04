<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Test\Unit\Model\Promo;

use Maxcode\Core\Model\Promo\PromotionPool;
use PHPUnit\Framework\TestCase;

class PromotionPoolTest extends TestCase
{
    public function testBuildsPromotionsFromDeclarations(): void
    {
        $pool = new PromotionPool([
            'free_one' => [
                'section' => 'vendor_free_one',
                'paid_module' => 'Vendor_Paid',
                'title' => 'A title',
                'text' => 'A text.',
                'url' => 'https://example.test/paid',
            ],
        ]);

        $promotion = $pool->get('free_one');

        $this->assertNotNull($promotion);
        $this->assertSame('Vendor_Paid', $promotion->paidModule);
        $this->assertSame('A title', $promotion->title);
        $this->assertSame('https://example.test/paid', $promotion->url);
        $this->assertSame($promotion, $pool->findBySection('vendor_free_one'));
        $this->assertNull($pool->findBySection('another_section'));
    }

    public function testOptionalFieldsDefaultToNull(): void
    {
        $promotion = (new PromotionPool(['p' => ['paid_module' => 'Vendor_Paid', 'text' => 'Text.']]))->get('p');

        $this->assertNull($promotion->title);
        $this->assertNull($promotion->url);
        $this->assertNull($promotion->section);
    }

    /**
     * @dataProvider invalidDeclarations
     */
    public function testIgnoresIncompleteDeclarations(mixed $declaration): void
    {
        $pool = new PromotionPool(['broken' => $declaration]);

        $this->assertNull($pool->get('broken'));
    }

    public static function invalidDeclarations(): array
    {
        return [
            'no paid module' => [['text' => 'Text.']],
            'no text' => [['paid_module' => 'Vendor_Paid']],
            'blank text' => [['paid_module' => 'Vendor_Paid', 'text' => '  ']],
            'not an array' => ['Vendor_Paid'],
        ];
    }

    public function testDropsNonHttpsUrls(): void
    {
        $promotion = (new PromotionPool(['p' => [
            'paid_module' => 'Vendor_Paid',
            'text' => 'Text.',
            'url' => 'javascript:alert(1)',
        ]]))->get('p');

        $this->assertNull($promotion->url);
    }

    public function testSectionLookupIgnoresPromotionsWithoutSection(): void
    {
        $pool = new PromotionPool(['p' => ['paid_module' => 'Vendor_Paid', 'text' => 'Text.']]);

        $this->assertNull($pool->findBySection(''));
    }
}
