<?php

namespace App\Command;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class TldListUpdater
{
    /**
     * Nombre de lignes insérées par requête, pour ne pas dépasser
     * max_allowed_packet / le nombre de paramètres liés PDO.
     */
    private const INSERT_CHUNK_SIZE = 200;

    public function __construct(
        private IanaTldListDownloader $downloader,
        private TldListParser $parser,
        private DatabaseFetcher $fetcher
    ) {
    }

    /**
     * @return int Nombre de nouveaux TLD ajoutés (les TLD déjà en base ne sont jamais retirés)
     */
    public function update(): int
    {
        $tlds = $this->parser->parse($this->downloader->download());
        $existingTlds = $this->fetchExistingTlds();

        $newTlds = array_values(array_diff($tlds, $existingTlds));

        foreach (array_chunk($newTlds, self::INSERT_CHUNK_SIZE) as $chunk) {
            $this->insertChunk($chunk);
        }

        return count($newTlds);
    }

    /**
     * @return string[]
     */
    private function fetchExistingTlds(): array
    {
        $rows = $this->fetcher->query(
            $this->fetcher->createQuery('tld_list')->select('tld')
        );

        return array_column($rows, 'tld');
    }

    private function insertChunk(array $tlds): void
    {
        $placeholders = [];
        $parameters = [];

        foreach (array_values($tlds) as $index => $tld) {
            $placeholders[] = ':tld' . $index;
            $parameters['tld' . $index] = $tld;
        }

        $this->fetcher->exec(
            $this->fetcher
                ->createQuery('tld_list')
                ->insertInto('tld', ...$placeholders)
            ,
            $parameters
        );
    }
}
