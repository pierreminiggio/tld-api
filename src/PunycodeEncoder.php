<?php

namespace App;

class PunycodeEncoder
{
    private const BASE = 36;
    private const TMIN = 1;
    private const TMAX = 26;
    private const SKEW = 38;
    private const DAMP = 700;
    private const INITIAL_BIAS = 72;
    private const INITIAL_N = 128;

    /**
     * Encode a single Unicode label (e.g. "澳門") into its punycode form,
     * WITHOUT the "xn--" prefix (e.g. "mix891f").
     */
    public function encode(string $label): string
    {
        $codePoints = $this->toCodePoints($label);

        $n = self::INITIAL_N;
        $delta = 0;
        $bias = self::INITIAL_BIAS;
        $output = '';

        $basicCodePoints = array_filter($codePoints, fn (int $c) => $c < 0x80);
        foreach ($basicCodePoints as $c) {
            $output .= chr($c);
        }

        $h = $b = count($basicCodePoints);

        if ($b > 0) {
            $output .= '-';
        }

        $length = count($codePoints);

        while ($h < $length) {
            $m = min(array_filter($codePoints, fn (int $c) => $c >= $n));

            $delta += ($m - $n) * ($h + 1);
            $n = $m;

            foreach ($codePoints as $c) {
                if ($c < $n) {
                    $delta++;
                }

                if ($c === $n) {
                    $q = $delta;

                    for ($k = self::BASE; ; $k += self::BASE) {
                        $t = $this->clamp($k - $bias, self::TMIN, self::TMAX);

                        if ($q < $t) {
                            break;
                        }

                        $output .= $this->encodeDigit($t + ($q - $t) % (self::BASE - $t));
                        $q = intdiv($q - $t, self::BASE - $t);
                    }

                    $output .= $this->encodeDigit($q);
                    $bias = $this->adapt($delta, $h + 1, $h === $b);
                    $delta = 0;
                    $h++;
                }
            }

            $delta++;
            $n++;
        }

        return $output;
    }

    /**
     * @return int[] Unicode code points, decoded from UTF-8 without requiring mbstring/intl.
     */
    private function toCodePoints(string $utf8): array
    {
        preg_match_all('/./us', $utf8, $matches);

        return array_map(function (string $char): int {
            $bytes = array_map('ord', str_split($char));
            $count = count($bytes);

            return match ($count) {
                1 => $bytes[0],
                2 => (($bytes[0] & 0x1F) << 6) | ($bytes[1] & 0x3F),
                3 => (($bytes[0] & 0x0F) << 12) | (($bytes[1] & 0x3F) << 6) | ($bytes[2] & 0x3F),
                default => (($bytes[0] & 0x07) << 18) | (($bytes[1] & 0x3F) << 12)
                    | (($bytes[2] & 0x3F) << 6) | ($bytes[3] & 0x3F),
            };
        }, $matches[0]);
    }

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($value, $max));
    }

    private function encodeDigit(int $d): string
    {
        return $d < 26 ? chr($d + 97) : chr($d - 26 + 48);
    }

    private function adapt(int $delta, int $numPoints, bool $firstTime): int
    {
        $delta = $firstTime ? intdiv($delta, self::DAMP) : intdiv($delta, 2);
        $delta += intdiv($delta, $numPoints);

        $k = 0;
        while ($delta > intdiv((self::BASE - self::TMIN) * self::TMAX, 2)) {
            $delta = intdiv($delta, self::BASE - self::TMIN);
            $k += self::BASE;
        }

        return $k + intdiv((self::BASE - self::TMIN + 1) * $delta, $delta + self::SKEW);
    }
}
