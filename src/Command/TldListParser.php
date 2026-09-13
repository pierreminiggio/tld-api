<?php

namespace App\Command;

class TldListParser
{
    /**
     * The IANA file starts with a "# Version 2024..." comment line and
     * lists one TLD per line, in uppercase ASCII (punycode for IDN TLDs).
     *
     * @return string[]
     */
    public function parse(string $rawList): array
    {
        $lines = explode("\n", trim($rawList));
        $tlds = [];

        foreach ($lines as $line) {
            $line = strtolower(trim($line));

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $tlds[] = $line;
        }

        return $tlds;
    }
}
