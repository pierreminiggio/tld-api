<?php

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use App\Command\IanaTldListDownloader;
use App\Command\TldListParser;
use App\Command\TldListUpdater;
use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggio\DatabaseConnection\DatabaseConnection;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

$projectDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

$configProvider = new ConfigProvider($projectDir);
$config = $configProvider->get();
$dbConfig = $config['db'];

$fetcher = new DatabaseFetcher(new DatabaseConnection(
    $dbConfig['host'],
    $dbConfig['database'],
    $dbConfig['username'],
    $dbConfig['password']
));

$updater = new TldListUpdater(
    new IanaTldListDownloader(),
    new TldListParser(),
    $fetcher
);

$count = $updater->update();

echo $count . ' nouveau(x) TLD ajouté(s).' . PHP_EOL;
