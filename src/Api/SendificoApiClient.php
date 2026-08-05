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
     * @param array<string, mixed> $payload
     *
     * @return array{count:int, data:array<int, array<string, mixed>>, page:int, pageCount:int, total:int}
     */
    public function createQuotation(array $connection, array $payload): array
    {
        $response = $this->request('POST', '/quotation', $connection, $payload);
        $responsePayload = $response['payload'] ?? [];

        return [
            'count' => (int) ($responsePayload['count'] ?? 0),
            'data' => $responsePayload['data'] ?? [],
            'page' => (int) ($responsePayload['page'] ?? 1),
            'pageCount' => (int) ($responsePayload['pageCount'] ?? 1),
            'total' => (int) ($responsePayload['total'] ?? 0),
        ];
    }

    /**
     * @param array{api_key:string, api_version:string, country:string} $connection
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function createShipment(array $connection, array $payload): array
    {
        $response = $this->request('POST', '/shipment', $connection, $payload);

        return $response['payload'] ?? [];
    }

    /**
     * @param array{api_key:string, api_version:string, country:string} $connection
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function purchaseShipment(array $connection, int $shipmentId, array $payload): array
    {
        $response = $this->request('PATCH', sprintf('/shipment/purchase/%d', $shipmentId), $connection, $payload);

        return $response['payload'] ?? [];
    }

    /**
     * @param array{api_key:string, api_version:string, country:string} $connection
     *
     * @return array<string, mixed>
     */
    public function getShipment(array $connection, int $shipmentId): array
    {
        $response = $this->request('GET', sprintf('/shipment/%d', $shipmentId), $connection);

        return $response['payload'] ?? [];
    }

    /**
     * @param array{api_key:string, api_version:string, country:string} $connection
     *
     * @return array<string, mixed>
     */
    public function generateTrackingNumber(array $connection, int $shipmentId): array
    {
        $response = $this->request('PATCH', sprintf('/shipment/generateTrackingNumber/%d', $shipmentId), $connection);

        return $response['payload'] ?? [];
    }

    /**
     * @param array{api_key:string, api_version:string, country:string} $connection
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function generateLabelUrl(array $connection, int $shipmentId, array $payload): array
    {
        $response = $this->request('POST', sprintf('/shipment/generateLabelUrl/%d', $shipmentId), $connection, $payload);

        return $response['payload'] ?? [];
    }

    /**
     * @param array{api_key:string, api_version:string, country:string} $connection
     * @param array<string, mixed>|null $payload
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $connection, ?array $payload = null): array
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

        if ($payload !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if (!is_string($body)) {
            throw new SendificoApiException('La respuesta de Sendifico no pudo leerse correctamente.');
        }

        if ($error !== '') {
            throw new SendificoApiException('Error de red al llamar a Sendifico: ' . $error, $httpCode);
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new SendificoApiException('Sendifico devolvio una respuesta no JSON o invalida.', $httpCode);
        }

        if ($httpCode >= 400) {
            $remoteMessageCode = (string) ($decoded['message'] ?? $decoded['payload']['message'] ?? '');
            $message = $remoteMessageCode !== '' ? $remoteMessageCode : 'Error remoto desconocido.';

            throw new SendificoApiException(
                sprintf('Sendifico respondio HTTP %d: %s', $httpCode, $message),
                $httpCode,
                $remoteMessageCode !== '' ? $remoteMessageCode : null,
                $decoded
            );
        }

        return $decoded;
    }
}
