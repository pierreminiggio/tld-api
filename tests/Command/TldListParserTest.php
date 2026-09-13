<?php

namespace App\Test\Command;

use App\Command\TldListParser;
use PHPUnit\Framework\TestCase;

class TldListParserTest extends TestCase
{
    public function testParsesLinesAndSkipsCommentAndBlank(): void
    {
        $raw = "# Version 2026091300, Last Updated Sun Sep 13 07:07:01 2026 UTC\n"
            . "COM\n"
            . "FR\n"
            . "\n"
            . "XN--P1AI\n";

        $this->assertSame(
            ['com', 'fr', 'xn--p1ai'],
            (new TldListParser())->parse($raw)
        );
    }

    public function testTrimsWhitespaceAroundLines(): void
    {
        $raw = "# header\n  COM  \r\nFR\r\n";

        $this->assertSame(['com', 'fr'], (new TldListParser())->parse($raw));
    }

    public function testEmptyListReturnsEmptyArray(): void
    {
        $this->assertSame([], (new TldListParser())->parse("# header only\n"));
    }
}
