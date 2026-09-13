<?php

namespace App\Test;

use App\PunycodeEncoder;
use PHPUnit\Framework\TestCase;

class PunycodeEncoderTest extends TestCase
{
    private PunycodeEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new PunycodeEncoder();
    }

    /**
     * @dataProvider labelProvider
     */
    public function testEncodesKnownLabels(string $label, string $expected): void
    {
        $this->assertSame($expected, $this->encoder->encode($label));
    }

    public static function labelProvider(): array
    {
        return [
            // TLD réels, valeurs vérifiées contre la vraie liste IANA (préfixe "xn--" retiré)
            'traditional chinese (Macao, 澳門)' => ['澳門', 'mix891f'],
            'russian cyrillic (рф)' => ['рф', 'p1ai'],
            'korean (한국)' => ['한국', '3e0b707e'],
        ];
    }
}
