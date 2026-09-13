<?php

namespace App;

class TldPunycodeConverter
{
    /**
     * Renvoie la forme ASCII canonique (punycode, minuscules) d'un TLD,
     * qu'il soit déjà en ASCII (ex: "COM", "xn--p1ai") ou dans son
     * script d'origine (ex: "澳門", "рф", "한국").
     *
     * Renvoie null si le TLD ne peut pas être converti (script invalide,
     * ou extension intl absente du serveur).
     */
    public function toPunycode(string $tld): ?string
    {
        if ($tld === '') {
            return null;
        }

        if ($this->isAscii($tld)) {
            return strtolower($tld);
        }

        if (! function_exists('idn_to_ascii')) {
            return null;
        }

        $converted = idn_to_ascii($tld, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

        return $converted !== false ? strtolower($converted) : null;
    }

    private function isAscii(string $value): bool
    {
        return (bool) preg_match('/^[\x00-\x7F]*$/', $value);
    }
}
