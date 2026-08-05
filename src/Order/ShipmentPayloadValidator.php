<?php

namespace Vx\Sendifico\Order;

final class ShipmentPayloadValidator
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array<string, string>>
     */
    public function validateQuotationPayload(array $payload): array
    {
        $errors = [];
        $errors = array_merge($errors, $this->validateParcel($payload['parcel'] ?? null));
        $errors = array_merge($errors, $this->validateGoodsCurrency($payload['goodsCurrency'] ?? null));

        if (trim((string) ($payload['senderTerritoryBaseId'] ?? '')) === '') {
            $errors[] = $this->buildError('sender_territory_missing', 'senderTerritoryBaseId', 'Falta el territorio del remitente para cotizar.');
        }

        if (trim((string) ($payload['recipientTerritoryBaseId'] ?? '')) === '') {
            $errors[] = $this->buildError('recipient_territory_missing', 'recipientTerritoryBaseId', 'Falta el territorio del destinatario para cotizar.');
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array<string, string>>
     */
    public function validateShipmentPayload(array $payload): array
    {
        $errors = [];
        $errors = array_merge($errors, $this->validateQuotationPayload($payload));

        if ((int) ($payload['senderAddressId'] ?? 0) <= 0) {
            $errors[] = $this->buildError('sender_address_required', 'senderAddressId', 'El shipment requiere un senderAddressId valido.');
        }

        $recipientAddress = is_array($payload['recipientAddress'] ?? null) ? $payload['recipientAddress'] : [];
        foreach ([
            'fullName' => 'El nombre del destinatario es obligatorio.',
            'streetLine1' => 'La direccion principal del destinatario es obligatoria.',
            'territoryBaseId' => 'El territorio del destinatario es obligatorio.',
            'country' => 'El pais del destinatario es obligatorio.',
            'phone' => 'El telefono del destinatario es obligatorio.',
        ] as $field => $message) {
            if (trim((string) ($recipientAddress[$field] ?? '')) === '') {
                $errors[] = $this->buildError('recipient_' . strtolower($field) . '_required', 'recipientAddress.' . $field, $message);
            }
        }

        $contents = $payload['contents'] ?? null;
        if (!is_array($contents) || $contents === []) {
            $errors[] = $this->buildError('contents_required', 'contents', 'El shipment requiere un contents no vacio.');
        } elseif (count($contents) !== 1 || !ContentsCatalog::isSupported((string) reset($contents))) {
            $errors[] = $this->buildError('contents_invalid', 'contents', 'El shipment requiere exactamente un contents soportado por Sendifico.');
        }

        return $errors;
    }

    /**
     * @param array<string, mixed>|mixed $parcel
     *
     * @return array<int, array<string, string>>
     */
    private function validateParcel(mixed $parcel): array
    {
        if (!is_array($parcel)) {
            return [$this->buildError('parcel_required', 'parcel', 'El paquete es obligatorio.')];
        }

        $errors = [];
        if ((float) ($parcel['weight'] ?? 0) <= 0) {
            $errors[] = $this->buildError('parcel_weight_invalid', 'parcel.weight', 'El peso del paquete debe ser mayor que 0.');
        }

        foreach (['length', 'width', 'height'] as $field) {
            if ((float) ($parcel[$field] ?? 0) < 1) {
                $errors[] = $this->buildError(
                    'parcel_' . $field . '_invalid',
                    'parcel.' . $field,
                    sprintf('La dimension %s debe ser mayor o igual a 1.', $field)
                );
            }
        }

        return $errors;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function validateGoodsCurrency(mixed $value): array
    {
        $currency = strtoupper(trim((string) $value));
        if ($currency === '' || !preg_match('/^[A-Z]{3}$/', $currency)) {
            return [$this->buildError('goods_currency_invalid', 'goodsCurrency', 'La moneda operativa del shipment no es valida.')];
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    private function buildError(string $code, string $field, string $message): array
    {
        return [
            'code' => $code,
            'field' => $field,
            'message' => $message,
        ];
    }
}
