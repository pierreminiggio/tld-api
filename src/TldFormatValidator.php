<?php

namespace App;

class TldFormatValidator
{
    /**
     * DNS label syntax : lettres/chiffres, tirets autorisés à l'intérieur,
     * pas de tiret en première ou dernière position, 63 caractères max
     * (les TLD IANA punycode, ex. xn--p1ai, respectent ce format).
     */
    private const PATTERN = '/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/i';

    public function isValid(string $tld): bool
    {
        return (bool) preg_match(self::PATTERN, $tld);
    }
}
