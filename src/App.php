<?php

namespace App;

use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggio\DatabaseConnection\DatabaseConnection;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class App
{
    public function run(string $path, ?string $queryParameters): void
    {
        if ($path === '/') {
            header('Location: /openapi');
            http_response_code(302);

            return;
        }

        if ($path === '/openapi') {
            header('Content-Type: text/html; charset=utf-8');
            echo $this->buildOpenApiPage();

            return;
        }

        $tld = strtolower(substr($path, 1));

        header('Content-Type: application/json');

        $validator = new TldFormatValidator();

        if (! $validator->isValid($tld)) {
            http_response_code(404);
            echo json_encode(['tld' => $tld, 'valid' => false]);

            return;
        }

        $repository = new TldRepository($this->buildFetcher());
        $exists = $repository->exists($tld);

        http_response_code($exists ? 200 : 404);
        echo json_encode(['tld' => $tld, 'valid' => $exists]);
    }

    protected function buildFetcher(): DatabaseFetcher
    {
        $currentDir = __DIR__ . DIRECTORY_SEPARATOR;
        $projectDir = $currentDir . '..' . DIRECTORY_SEPARATOR;
        $configProvider = new ConfigProvider($projectDir);
        $config = $configProvider->get();
        $dbConfig = $config['db'];

        return new DatabaseFetcher(new DatabaseConnection(
            $dbConfig['host'],
            $dbConfig['database'],
            $dbConfig['username'],
            $dbConfig['password']
        ));
    }

    protected function buildOpenApiPage(): string
    {
        return <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>tld-api &mdash; OpenAPI</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 40px auto; padding: 0 16px; line-height: 1.5; color: #222; }
        code, pre { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
        pre { padding: 12px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 16px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
    <h1>tld-api</h1>
    <p>Validates whether a TLD (Top-Level Domain) exists, against a locally cached copy of the
    <a href="https://data.iana.org/TLD/tlds-alpha-by-domain.txt">IANA TLD list</a>.</p>

    <h2>Endpoint</h2>
    <pre>GET /{tld}</pre>
    <p><code>{tld}</code> is the TLD to check, without a leading dot (e.g. <code>com</code>, <code>fr</code>, <code>xn--p1ai</code>).</p>

    <h2>Response</h2>
    <p>JSON body, in both success and failure cases:</p>
    <pre>{"tld": "com", "valid": true}</pre>

    <table>
        <tr><th>HTTP status</th><th>Meaning</th></tr>
        <tr><td>200</td><td>The TLD exists in the cached IANA list</td></tr>
        <tr><td>404</td><td>The TLD does not exist, or is not a validly formatted TLD</td></tr>
    </table>

    <h2>Examples</h2>
    <pre>GET /com
200 {"tld":"com","valid":true}

GET /notarealtld
404 {"tld":"notarealtld","valid":false}</pre>
</body>
</html>
HTML;
    }
}
