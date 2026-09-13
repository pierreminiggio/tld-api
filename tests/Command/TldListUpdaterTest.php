<?php

namespace App\Test\Command;

use App\Command\IanaTldListDownloader;
use App\Command\TldListParser;
use App\Command\TldListUpdater;
use NeutronStars\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class TldListUpdaterTest extends TestCase
{
    public function testUpdateOnlyInsertsNewTldsAndReturnsTheirCount(): void
    {
        $downloader = $this->createMock(IanaTldListDownloader::class);
        $downloader->method('download')->willReturn('raw-content');

        $parser = $this->createMock(TldListParser::class);
        $parser->method('parse')->with('raw-content')->willReturn(['com', 'fr', 'org', 'xyz']);

        $fetcher = $this->mockFetcher();
        $fetcher->method('query')->willReturn([
            ['tld' => 'com'],
            ['tld' => 'org'],
        ]);

        $executedQueries = [];
        $fetcher->method('exec')
            ->willReturnCallback(function (QueryBuilder $query, array $parameters = []) use (&$executedQueries) {
                $executedQueries[] = [$query->build(), $parameters];
            });

        $updater = new TldListUpdater($downloader, $parser, $fetcher);

        $count = $updater->update();

        $this->assertSame(2, $count);
        $this->assertCount(1, $executedQueries);

        [$insertSql, $insertParams] = $executedQueries[0];
        $this->assertSame(
            'INSERT INTO tld_list (tld) VALUES (:tld0),(:tld1)',
            $insertSql
        );
        $this->assertSame(
            ['tld0' => 'fr', 'tld1' => 'xyz'],
            $insertParams
        );
    }

    public function testNoNewTldsMeansNoInsertQuery(): void
    {
        $downloader = $this->createMock(IanaTldListDownloader::class);
        $downloader->method('download')->willReturn('raw-content');

        $parser = $this->createMock(TldListParser::class);
        $parser->method('parse')->willReturn(['com', 'fr']);

        $fetcher = $this->mockFetcher();
        $fetcher->method('query')->willReturn([
            ['tld' => 'com'],
            ['tld' => 'fr'],
        ]);
        $fetcher->expects($this->never())->method('exec');

        $updater = new TldListUpdater($downloader, $parser, $fetcher);

        $this->assertSame(0, $updater->update());
    }

    public function testChunksLargeInsertsAcrossMultipleQueries(): void
    {
        $tlds = array_map(fn (int $i) => 'tld' . $i, range(1, 250));

        $downloader = $this->createMock(IanaTldListDownloader::class);
        $downloader->method('download')->willReturn('raw-content');

        $parser = $this->createMock(TldListParser::class);
        $parser->method('parse')->willReturn($tlds);

        $fetcher = $this->mockFetcher();
        $fetcher->method('query')->willReturn([]);

        $insertCount = 0;
        $fetcher->method('exec')->willReturnCallback(function () use (&$insertCount) {
            $insertCount++;
        });

        $updater = new TldListUpdater($downloader, $parser, $fetcher);
        $count = $updater->update();

        $this->assertSame(250, $count);
        // 200 puis 50 : deux requêtes d'insertion pour respecter le chunk size
        $this->assertSame(2, $insertCount);
    }

    private function mockFetcher(): DatabaseFetcher
    {
        return $this->getMockBuilder(DatabaseFetcher::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['exec', 'query'])
            ->getMock();
    }
}
