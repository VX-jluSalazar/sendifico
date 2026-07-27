<?php

namespace Vx\Sendifico\Api;

final class SendificoApiClient
{
    private const BASE_URL = 'https://api.sendifico.com/api/public';

    /**
     * @param array{api_key:string, api_version:string, country:string} $connection
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTerritories(array $connection): array
    {
        $response = $this->request('GET', '/territory', $connection);

        return $response['payload']['data'] ?? [];
    }

    /**
     * @param array{api_key:string, api_version:string, country:string} $connection
     *
     * @return array{count:int, data:array<int, array<string, mixed>>, page:int, pageCount:int, total:int}
     */
    public function getAddresses(array $connection, int $page = 1, int $limit = 100): array
    {
        $response = $this->request('GET', sprintf('/address?limit=%d&page=%d', $limit, $page), $connection);
        $payload = $response['payload'] ?? [];

        return [
            'count' => (int) ($payload['count'] ?? 0),
            'data' => $payload['data'] ?? [],
            'page' => (int) ($payload['page'] ?? $page),
            'pageCount' => (int) ($payload['pageCount'] ?? 1),
            'total' => (int) ($payload['total'] ?? 0),
        ];
    }

    /**
     * @param array{api_key:string, api_version:string, country:string} $connection
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $connection): array
    {
        $curl = curl_init();
        if ($curl === false) {
            throw new SendificoApiException('No fue posible inicializar cURL para Sendifico.');
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => self::BASE_URL . $path,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'x-api-key: ' . $connection['api_key'],
                'x-sendifico-api-version: ' . $connection['api_version'],
                'x-sendifico-country: ' . $connection['country'],
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if (!is_string($body)) {
            throw new SendificoApiException('La respuesta de Sendifico no pudo leerse correctamente.');
        }

        if ($error !== '') {
            throw new SendificoApiException('Error de red al llamar a Sendifico: ' . $error);
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new SendificoApiException('Sendifico devolvio una respuesta no JSON o invalida.');
        }

        if ($httpCode >= 400) {
            $message = (string) ($decoded['message'] ?? $decoded['payload']['message'] ?? 'Error remoto desconocido.');

            throw new SendificoApiException(sprintf('Sendifico respondio HTTP %d: %s', $httpCode, $message), $httpCode);
        }

        return $decoded;
    }
}
