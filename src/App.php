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

        $rawTld = rawurldecode(substr($path, 1));

        header('Content-Type: application/json');

        $converter = new TldPunycodeConverter();
        $tld = $converter->toPunycode($rawTld);

        if ($tld === null) {
            http_response_code(404);
            echo json_encode(['tld' => $rawTld, 'valid' => false], JSON_UNESCAPED_UNICODE);

            return;
        }

        $validator = new TldFormatValidator();

        if (! $validator->isValid($tld)) {
            http_response_code(404);
            echo json_encode($this->buildResponse($tld, $rawTld, false), JSON_UNESCAPED_UNICODE);

            return;
        }

        $repository = new TldRepository($this->buildFetcher());
        $exists = $repository->exists($tld);

        http_response_code($exists ? 200 : 404);
        echo json_encode($this->buildResponse($tld, $rawTld, $exists), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array{tld: string, valid: bool, input?: string}
     */
    protected function buildResponse(string $tld, string $rawTld, bool $valid): array
    {
        $response = ['tld' => $tld, 'valid' => $valid];

        if ($rawTld !== $tld) {
            $response['input'] = $rawTld;
        }

        return $response;
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
    <p><code>{tld}</code> is the TLD to check, without a leading dot. It can be given in ASCII
    (e.g. <code>com</code>, <code>xn--p1ai</code>) or in its original script
    (e.g. <code>рф</code>, <code>澳門</code>, <code>한국</code>) &mdash; it is automatically converted
    to its canonical punycode form before being checked.</p>

    <h2>Response</h2>
    <p>JSON body, in both success and failure cases. <code>tld</code> is always the canonical ASCII
    form. An <code>input</code> field is added when it differs from what was actually requested
    (e.g. a native-script TLD, or different casing):</p>
    <pre>{"tld": "com", "valid": true}
{"tld": "xn--mix891f", "valid": true, "input": "澳門"}</pre>

    <table>
        <tr><th>HTTP status</th><th>Meaning</th></tr>
        <tr><td>200</td><td>The TLD exists in the cached IANA list</td></tr>
        <tr><td>404</td><td>The TLD does not exist, or is not a validly formatted TLD</td></tr>
    </table>

    <h2>Examples</h2>
    <pre>GET /com
200 {"tld":"com","valid":true}

GET /澳門
200 {"tld":"xn--mix891f","valid":true,"input":"澳門"}

GET /notarealtld
404 {"tld":"notarealtld","valid":false}</pre>
</body>
</html>
HTML;
    }
}
