<?php

namespace App\Test;

use App\TldFormatValidator;
use PHPUnit\Framework\TestCase;

class TldFormatValidatorTest extends TestCase
{
    private TldFormatValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new TldFormatValidator();
    }

    /**
     * @dataProvider validTldProvider
     */
    public function testValidTlds(string $tld): void
    {
        $this->assertTrue($this->validator->isValid($tld));
    }

    public static function validTldProvider(): array
    {
        return [
            'simple' => ['com'],
            'short' => ['fr'],
            'with digit' => ['xn--p1ai'],
            'with hyphen in the middle' => ['xn--3e0b707e'],
            'single char' => ['a'],
        ];
    }

    /**
     * @dataProvider invalidTldProvider
     */
    public function testInvalidTlds(string $tld): void
    {
        $this->assertFalse($this->validator->isValid($tld));
    }

    public static function invalidTldProvider(): array
    {
        return [
            'empty' => [''],
            'leading hyphen' => ['-com'],
            'trailing hyphen' => ['com-'],
            'leading dot' => ['.com'],
            'contains space' => ['co m'],
            'contains slash' => ['co/m'],
        ];
    }
}
