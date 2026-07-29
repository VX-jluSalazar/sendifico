<?php

namespace Vx\Sendifico\Checkout;

use Address;
use FormField;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vx\Sendifico\Repository\AddressMetadataRepository;
use Vx\Sendifico\Repository\CountryRepository;
use Vx\Sendifico\Repository\StateRepository;
use Vx\Sendifico\Repository\TerritoryRepository;

final class AddressTerritoryFormService
{
    public const FIELD_CANTON = 'sendifico_canton';
    public const FIELD_TERRITORY_BASE_ID = 'sendifico_territory_base_id';
    public const FORM_FIELD_CANTON = 'vx_sendifico_' . self::FIELD_CANTON;
    public const FORM_FIELD_TERRITORY_BASE_ID = 'vx_sendifico_' . self::FIELD_TERRITORY_BASE_ID;

    public function __construct(
        private readonly CheckoutConfigurationProvider $checkoutConfigurationProvider,
        private readonly TerritoryRepository $territoryRepository,
        private readonly AddressMetadataRepository $addressMetadataRepository,
        private readonly CountryRepository $countryRepository,
        private readonly StateRepository $stateRepository,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @return array<int, FormField>
     */
    public function buildAdditionalFields(int $shopId, ?int $addressId = null): array
    {
        $metadata = $addressId !== null && $addressId > 0 ? $this->addressMetadataRepository->findByAddressId($addressId) : null;

        return [
            (new FormField())
                ->setName(self::FIELD_CANTON)
                ->setType('select')
                ->setLabel($this->translator->trans('Canton', [], 'Modules.Vxsendifico.Shop'))
                ->setRequired(false)
                ->setAvailableValues(['' => $this->translator->trans('Select canton', [], 'Modules.Vxsendifico.Shop')])
                ->setValue(is_array($metadata) ? (string) ($metadata['territory2_name'] ?? '') : ''),
            (new FormField())
                ->setName(self::FIELD_TERRITORY_BASE_ID)
                ->setType('hidden')
                ->setRequired(false)
                ->setValue(is_array($metadata) ? (string) ($metadata['territory_base_id'] ?? '') : ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFrontendConfiguration(int $shopId): array
    {
        $configuration = $this->getSafeShopConfiguration($shopId);
        if ($configuration === null) {
            return [
                'enabled' => false,
                'fieldCanton' => self::FORM_FIELD_CANTON,
                'fieldTerritoryBaseId' => self::FORM_FIELD_TERRITORY_BASE_ID,
                'territories' => [],
            ];
        }

        $territories = $this->territoryRepository->findByCountry((string) $configuration['country']);
        if ($territories === []) {
            return [
                'enabled' => false,
                'fieldCanton' => self::FORM_FIELD_CANTON,
                'fieldTerritoryBaseId' => self::FORM_FIELD_TERRITORY_BASE_ID,
                'territories' => [],
            ];
        }

        return [
            'enabled' => true,
            'countryIso' => (string) $configuration['country'],
            'configuredCountryId' => $this->countryRepository->findIdByIsoCode((string) $configuration['country']),
            'fieldCanton' => self::FORM_FIELD_CANTON,
            'fieldTerritoryBaseId' => self::FORM_FIELD_TERRITORY_BASE_ID,
            'territories' => $this->buildTerritoryTree($territories),
        ];
    }

    public function validateAddressForm(mixed $form, array $submittedValues, int $shopId): bool
    {
        $configuration = $this->getSafeShopConfiguration($shopId);
        if ($configuration === null) {
            return true;
        }

        $countryId = isset($submittedValues['id_country']) ? (int) $submittedValues['id_country'] : 0;
        $countryIso = $countryId > 0 ? $this->countryRepository->findIsoCodeById($countryId) : null;
        if ($countryIso === null || strtoupper($countryIso) !== strtoupper((string) $configuration['country'])) {
            return true;
        }

        $stateId = isset($submittedValues['id_state']) ? (int) $submittedValues['id_state'] : 0;
        $canton = trim((string) ($submittedValues[self::FORM_FIELD_CANTON] ?? $submittedValues[self::FIELD_CANTON] ?? ''));
        $city = trim((string) ($submittedValues['city'] ?? ''));
        $territoryBaseId = trim((string) ($submittedValues[self::FORM_FIELD_TERRITORY_BASE_ID] ?? $submittedValues[self::FIELD_TERRITORY_BASE_ID] ?? ''));

        $isValid = true;
        $stateName = $stateId > 0 ? $this->stateRepository->findNameById($stateId) : null;
        if ($stateName === null) {
            $stateField = $form->getField('id_state');
            if ($stateField !== null) {
                $stateField->addError($this->translator->trans('Select a valid state for Sendifico.', [], 'Modules.Vxsendifico.Shop'));
            }
            $isValid = false;
        }

        if ($canton === '') {
            $cantonField = $form->getField(self::FORM_FIELD_CANTON);
            if ($cantonField !== null) {
                $cantonField->addError($this->translator->trans('Select a canton for the delivery address.', [], 'Modules.Vxsendifico.Shop'));
            }
            $isValid = false;
        }

        if ($city === '') {
            $cityField = $form->getField('city');
            if ($cityField !== null) {
                $cityField->addError($this->translator->trans('Select a city for the delivery address.', [], 'Modules.Vxsendifico.Shop'));
            }
            $isValid = false;
        }

        if ($territoryBaseId === '') {
            $cityField = $form->getField('city');
            if ($cityField !== null) {
                $cityField->addError($this->translator->trans('The selected state, canton and city do not resolve to a valid Sendifico territory.', [], 'Modules.Vxsendifico.Shop'));
            }

            return false;
        }

        $territory = $this->territoryRepository->findOneByBaseId((string) $configuration['country'], $territoryBaseId);
        if ($territory === null) {
            $cityField = $form->getField('city');
            if ($cityField !== null) {
                $cityField->addError($this->translator->trans('The selected territory does not exist in the local Sendifico cache.', [], 'Modules.Vxsendifico.Shop'));
            }

            return false;
        }

        if (
            !$this->matchesValue((string) ($territory['territory1_name'] ?? ''), (string) $stateName)
            || !$this->matchesValue((string) ($territory['territory2_name'] ?? ''), $canton)
            || !$this->matchesValue((string) ($territory['territory3_name'] ?? ''), $city)
        ) {
            $cityField = $form->getField('city');
            if ($cityField !== null) {
                $cityField->addError($this->translator->trans('The selected address values do not match the Sendifico territory hierarchy.', [], 'Modules.Vxsendifico.Shop'));
            }

            return false;
        }

        return $isValid;
    }

    public function persistAddressMetadata(Address $address, array $submittedValues, int $shopId): void
    {
        if ((int) $address->id <= 0) {
            return;
        }

        $configuration = $this->getSafeShopConfiguration($shopId);
        $countryIso = (int) $address->id_country > 0 ? $this->countryRepository->findIsoCodeById((int) $address->id_country) : null;

        if ($configuration === null || $countryIso === null || strtoupper($countryIso) !== strtoupper((string) $configuration['country'])) {
            $this->addressMetadataRepository->deleteByAddressId((int) $address->id);

            return;
        }

        $territoryBaseId = trim((string) ($submittedValues[self::FORM_FIELD_TERRITORY_BASE_ID] ?? $submittedValues[self::FIELD_TERRITORY_BASE_ID] ?? ''));
        if ($territoryBaseId === '') {
            $this->addressMetadataRepository->deleteByAddressId((int) $address->id);

            return;
        }

        $territory = $this->territoryRepository->findOneByBaseId((string) $configuration['country'], $territoryBaseId);
        if ($territory === null) {
            $this->addressMetadataRepository->deleteByAddressId((int) $address->id);

            return;
        }

        $this->addressMetadataRepository->upsert([
            'id_address' => (int) $address->id,
            'id_shop' => $shopId,
            'country_code' => (string) $configuration['country'],
            'territory_base_id' => (string) $territory['territory_base_id'],
            'territory1_name' => (string) $territory['territory1_name'],
            'territory2_name' => (string) $territory['territory2_name'],
            'territory3_name' => (string) $territory['territory3_name'],
        ]);
    }

    public function deleteAddressMetadata(int $addressId): void
    {
        $this->addressMetadataRepository->deleteByAddressId($addressId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAddressMetadata(int $addressId): ?array
    {
        return $this->addressMetadataRepository->findByAddressId($addressId);
    }

    /**
     * @param array<int, array<string, mixed>> $territories
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildTerritoryTree(array $territories): array
    {
        $tree = [];

        foreach ($territories as $territory) {
            $stateLabel = trim((string) $territory['territory1_name']);
            $cantonLabel = trim((string) $territory['territory2_name']);
            $cityLabel = trim((string) $territory['territory3_name']);
            $stateKey = $this->normalizeKey($stateLabel);
            $cantonKey = $this->normalizeKey($cantonLabel);
            $cityKey = $this->normalizeKey($cityLabel);

            if (!isset($tree[$stateKey])) {
                $tree[$stateKey] = [
                    'label' => $stateLabel,
                    'cantons' => [],
                ];
            }

            if (!isset($tree[$stateKey]['cantons'][$cantonKey])) {
                $tree[$stateKey]['cantons'][$cantonKey] = [
                    'label' => $cantonLabel,
                    'cities' => [],
                ];
            }

            $tree[$stateKey]['cantons'][$cantonKey]['cities'][$cityKey] = [
                'label' => $cityLabel,
                'territoryBaseId' => (string) $territory['territory_base_id'],
            ];
        }

        return $tree;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getSafeShopConfiguration(int $shopId): ?array
    {
        try {
            return $this->checkoutConfigurationProvider->getAddressConfiguration($shopId);
        } catch (\Throwable) {
            return null;
        }
    }

    private function matchesValue(string $expected, string $actual): bool
    {
        return $this->normalizeKey($expected) === $this->normalizeKey($actual);
    }

    private function normalizeKey(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return '';
        }

        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        if (is_string($transliterated) && $transliterated !== '') {
            $normalized = $transliterated;
        }

        $normalized = preg_replace('/[^A-Za-z0-9]+/', '_', strtoupper($normalized));

        return trim((string) $normalized, '_');
    }
}
