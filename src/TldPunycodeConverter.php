<?php

namespace App;

class TldPunycodeConverter
{
    /**
     * Renvoie la forme ASCII canonique (punycode, minuscules) d'un TLD,
     * qu'il soit déjà en ASCII (ex: "COM", "xn--p1ai") ou dans son
     * script d'origine (ex: "澳門", "рф", "한국").
     *
     * Renvoie null si le TLD ne peut pas être converti (chaîne vide).
     */
    public function toPunycode(string $tld): ?string
    {
        if ($tld === '') {
            return null;
        }

        if ($this->isAscii($tld)) {
            return strtolower($tld);
        }

        $encoded = (new PunycodeEncoder())->encode(strtolower($tld));

        return $encoded === '' ? null : 'xn--' . $encoded;
    }

    private function isAscii(string $value): bool
    {
        return (bool) preg_match('/^[\x00-\x7F]*$/', $value);
    }
}
