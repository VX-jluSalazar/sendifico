# Discovery de la implementacion de sendifico 
## Proceso de envío
Un envío avanza mediante llamadas explícitas de propósito único para que TÚ decidas exactamente cuándo quieres seleccionar una transportadora y pagar un envío:

1. POST /quotation
2. POST /shipment
3. PATCH /shipment/purchase/{id}
4. PATCH /shipment/generateTrackingNumber/{id}
5. POST /shipment/generateLabelUrl/{id}

### 1. POST /quotation 
Devuelve una lista de cotizaciones/tarifas de todas las transportadoras, según una ruta origen-destino. Este paso es opcional y NO conlleva la creación de ningún objeto de envío, ni ninguna transacción de billetera. Úsalo solo para comparar precios antes de crear un envío real.


```json
{
  "senderAddress": {
    "territoryBaseId": "EC|:|GUAYAS|:|GUAYAQUIL|:|GUAYAQUIL", -> Obtener de sendifico GET /territory
    "country": "EC" -> Siempre EC
  },
  "recipientAddress": {
    "territoryBaseId": "EC|:|PICHINCHA|:|QUITO|:|QUITO",-> Obtener de sendifico GET /territory
    "country": "EC" -> Siempre EC
  },
  "parcel": { -> de producto
    "weight": 1.5,
    "length": 30,
    "width": 20,
    "height": 10
  },
  "goodsCollection": 50, -> Dinero que el transportista debe cobrar al cliente al entregar (contra entrega)
  "goodsInsured": 50, -> Valor de la mercancía que se declara para cobertura de seguro
  "goodsCurrency": "USD" -> Siempre USD
}
```

>Puede no ser usada en la tienda, o puede ser usado como un cotizador dentro de la tienda. OPCIONAL
### 2. POST /shipment 
Crea un borrador de envío y obtén la lista de rates de todas las transportadoras. En este punto, no se selecciona ninguna transportadora ni se realiza aún ninguna transacción de billetera.
```json
{
  "extId": "my-shop-order-9876", -> (PRESTASHOP) order Id
  "senderAddressId": 2001234, -> id direccion sendifico, consultar GET /address
  "recipientAddress": { -> puede ser obtenido de sendifico, usar recipientAddressId
    "fullName": "María García", -> (PRESTASHOP) Customer-name
    "company": "Acme S.A.", -> (PRESTASHOP) Address-company
    "email": "maria.garcia@example.com", -> (PRESTASHOP) Customer-mail
    "streetLine1": "Av. Amazonas N24-03 y Colón", -> (PRESTASHOP) Address - address1 & address2
    "reference": "Frente al parque", -> (PRESTASHOP) Address - other
    "territoryBaseId": "EC|:|PICHINCHA|:|QUITO|:|QUITO",-> Obtener de sendifico GET /territory
    "country": "EC", -> Siempre EC
    "zip": "170135", -> (PRESTASHOP) Address - postcode
    "lat": -0.1807,  -> Obtener con js de la ubicacion del cliente
    "lng": -78.4678, -> Obtener con js de la ubicacion del cliente
    "phone": "+593987654321" -> (PRESTASHOP) Address - phone
  },
  "parcel": { -> (PRESTASHOP) required de todo el paquete 
    "weight": 1.5,  | Analizar como calcular el volumen de todo el paquete
    "length": 30,   | si los couriers necesitan el volumen o dimensiones
    "width": 20,    | es lo mismo 2x4x5 = 40cm3  que 2x2x10 = 40cm3
    "height": 10
  },
  "contents": [ -> Atributo contents de cada producto o de categoría
    "clothes"
  ],
  "goodsCollection": 50, -> Dinero que el transportista debe cobrar al cliente al entregar (contra entrega)
  "goodsInsured": 50, -> Valor de la mercancía que se declara para cobertura de seguro
  "goodsCurrency": "USD" -> Siempre USD
}
```

#### List of contents values
- agriculturalSupply
- audioAndVideoAccessory
- babyProduct
- beverage
- book
- camera
- cellularPhone
- clothes
- clothingAccessory
- computer
- decoration
- documents
- dron
- electromenor
- food
- householdAppliance
- jewelryAndWatch
- kitchenware
- medicalEquipment
- medicalSupply
- musicalInstrument
- party
- personalCareProduct
- petAccessory
- petFood
- printer
- shoeAndFootwear
- spareparts
- sportingArticle
- stationery
- television
- toy
- vehicleAccessory
- videoGame

> Consultar endpoint luego de seleccionar/crear la dirección mostrar dinamicamente los couiriers que devuelve sendifico en rates.

### 3. PATCH /shipment/purchase/{id} 
Elige una rate de tu transportadora preferida y págala desde tu billetera. Antes de poder pagar una etiqueta de envío, debes recargar tu billetera en https://app.sendifico.com/wallet.
 El {id} es el shipmentId de POST /shipment del punto `2`.
 ```json
 {
  "preferredRateObjectId": 7001234, -> el rateId de la tarifa elegida,
  "purchaseWith": "walletTokenized" -> puede ser uno de los dos walletTokenized o walletAvailable
                                    | Preguntar la diferencia entre walletTokenized y walletAvailable
}
```

> Definir un estado para poder enviar desde el backoffice cuando se confirme el pago y se quiera 

### 4. PATCH /shipment/generateTrackingNumber/{id} 
- Contacta los sistemas de la transportadora para generar un envío y obtener un trackingNumber válido. Idempotente — generado una vez, las llamadas repetidas devuelven el mismo número.

- Momento — crea esto cerca del despacho. Idealmente llámalo el mismo día (o el día antes) en que el envío se entrega a la transportadora (recogida o entrega en una sucursal). Algunas transportadoras (p. ej. Tramaco) anulan automáticamente un número de seguimiento tras ~1-3 días de inactividad — si eso ocurre debes crear uno nuevo. No crees números de seguimiento con mucha anticipación al despacho real.
> Usar un estado de order para poder generar el tracking number

- Esta llamada puede fallar si la API de la transportadora está caída — eso está fuera del control de Sendifico, por lo que tu integración DEBE verificar que un trackingNumber fue efectivamente devuelto antes de continuar.

```
No tiene body
```

5. POST /shipment/generateLabelUrl/{id} — genera una URL de corta duración para descargar el PDF de la etiqueta. La URL expira (7 días máximo); vuelve a llamar para obtener una nueva.
