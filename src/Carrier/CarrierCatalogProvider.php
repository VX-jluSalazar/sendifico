<?php

namespace Vx\Sendifico\Carrier;

final class CarrierCatalogProvider
{
    /**
     * @return array<string, array{token:string, carrier_name:string, display_name:string, delay:string}>
     */
    public function getCatalog(): array
    {
        return [
            'ec_laar' => [
                'token' => 'ec_laar',
                'carrier_name' => 'Sendifico - LAAR',
                'display_name' => 'LAAR',
                'delay' => 'Envio gestionado por Sendifico (LAAR).',
            ],
            'ec_tramaco' => [
                'token' => 'ec_tramaco',
                'carrier_name' => 'Sendifico - Tramaco',
                'display_name' => 'Tramaco',
                'delay' => 'Envio gestionado por Sendifico (Tramaco).',
            ],
            'servientrega' => [
                'token' => 'servientrega',
                'carrier_name' => 'Sendifico - Servientrega',
                'display_name' => 'Servientrega',
                'delay' => 'Envio gestionado por Sendifico (Servientrega).',
            ],
            'ec_delivereo' => [
                'token' => 'ec_delivereo',
                'carrier_name' => 'Sendifico - Delivereo',
                'display_name' => 'Delivereo',
                'delay' => 'Envio gestionado por Sendifico (Delivereo).',
            ],
            'ec_yobel' => [
                'token' => 'ec_yobel',
                'carrier_name' => 'Sendifico - Yobel',
                'display_name' => 'Yobel',
                'delay' => 'Envio gestionado por Sendifico (Yobel).',
            ],
            'ec_urbano' => [
                'token' => 'ec_urbano',
                'carrier_name' => 'Sendifico - Urbano',
                'display_name' => 'Urbano',
                'delay' => 'Envio gestionado por Sendifico (Urbano).',
            ],
            'ec_gintracom' => [
                'token' => 'ec_gintracom',
                'carrier_name' => 'Sendifico - Gintracom',
                'display_name' => 'Gintracom',
                'delay' => 'Envio gestionado por Sendifico (Gintracom).',
            ],
        ];
    }
}
