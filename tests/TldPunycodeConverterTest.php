<?php

namespace App\Test;

use App\TldPunycodeConverter;
use PHPUnit\Framework\TestCase;

class TldPunycodeConverterTest extends TestCase
{
    private TldPunycodeConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new TldPunycodeConverter();
    }

    public function testAsciiTldIsLowercasedAndReturnedAsIs(): void
    {
        $this->assertSame('com', $this->converter->toPunycode('com'));
        $this->assertSame('com', $this->converter->toPunycode('COM'));
    }

    public function testAlreadyPunycodeTldIsLowercasedAndReturnedAsIs(): void
    {
        $this->assertSame('xn--p1ai', $this->converter->toPunycode('xn--p1ai'));
        $this->assertSame('xn--p1ai', $this->converter->toPunycode('XN--P1AI'));
    }

    /**
     * @dataProvider nativeScriptTldProvider
     */
    public function testNativeScriptTldIsConvertedToPunycode(string $native, string $expectedPunycode): void
    {
        if (! function_exists('idn_to_ascii')) {
            $this->markTestSkipped('ext-intl is not available.');
        }

        $this->assertSame($expectedPunycode, $this->converter->toPunycode($native));
    }

    public static function nativeScriptTldProvider(): array
    {
        return [
            'traditional chinese (Macao)' => ['澳門', 'xn--mix891f'],
            'russian cyrillic' => ['рф', 'xn--p1ai'],
            'korean' => ['한국', 'xn--3e0b707e'],
        ];
    }

    public function testEmptyStringIsNotValid(): void
    {
        $this->assertNull($this->converter->toPunycode(''));
    }
}
