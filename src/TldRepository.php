<?php

namespace App;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class TldRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {
    }

    public function exists(string $tld): bool
    {
        $rows = $this->fetcher->query(
            $this->fetcher
                ->createQuery('tld_list')
                ->select('id')
                ->where('tld = :tld')
            ,
            ['tld' => $tld]
        );

        return (bool) $rows;
    }
}
