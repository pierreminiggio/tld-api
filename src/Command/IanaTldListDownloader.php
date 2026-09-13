<?php

namespace App\Command;

use RuntimeException;

class IanaTldListDownloader
{
    private const IANA_LIST_URL = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt';

    public function download(): string
    {
        $curl = curl_init(self::IANA_LIST_URL);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, 'tld-api-updater');
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($result === false || $result === '') {
            throw new RuntimeException('Unable to download the IANA TLD list' . ($error ? " : $error" : ''));
        }

        return $result;
    }
}
