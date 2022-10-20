<?php
namespace osim\craft\tenon\helpers;

use Craft;

trait TenonApiTrait
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = Craft::parseEnv($apiKey);
    }

    private function request(string $path, string $method = 'GET', string|array $data = []): ?array
    {
        // Test API 404's unless trailing slash (Others are fine.)
        $url = 'https://tenon.io/api/' . ltrim($path, '/');
        $method = strtoupper($method);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method === 'POST' || $method === 'PUT') {
            if (is_array($data)) {
                $data = http_build_query($data);
            } else {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($ch);

        curl_close($ch);

        if (!$response) {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->logStatus($httpStatusCode);

            return [
                'status' => $httpStatusCode
            ];
        }

        $response = json_decode($response, true);

        if (isset($response['status'])) {
            $this->logStatus($response['status']);
        }

        return $response;
    }

    private function logStatus(int $httpStatusCode)
    {
        if ($httpStatusCode === 401) {
            Craft::error('Tenon API Key is invalid.', __METHOD__);
        } elseif ($httpStatusCode === 402) {
            Craft::warning('Tenon payment required.', __METHOD__);
        }
    }
}
