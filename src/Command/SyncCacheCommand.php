<?php

namespace Vx\Sendifico\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Vx\Sendifico\Configuration\SendificoConnectionConfigurationProvider;
use Vx\Sendifico\Repository\ShopRepository;
use Vx\Sendifico\Sync\SenderAddressSyncService;
use Vx\Sendifico\Sync\TerritorySyncService;

final class SyncCacheCommand extends Command
{
    public function __construct(
        private readonly ShopRepository $shopRepository,
        private readonly SendificoConnectionConfigurationProvider $configurationProvider,
        private readonly TerritorySyncService $territorySyncService,
        private readonly SenderAddressSyncService $senderAddressSyncService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Sincroniza el cache local de territorios y remitentes de Sendifico.')
            ->addOption('shop-id', null, InputOption::VALUE_OPTIONAL, 'Sincroniza solo una tienda concreta.')
            ->addOption('only', null, InputOption::VALUE_OPTIONAL, 'territories, senders o all', 'all');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $only = (string) $input->getOption('only');
        $shopIdOption = $input->getOption('shop-id');
        $shopIds = $shopIdOption !== null ? [(int) $shopIdOption] : $this->shopRepository->getActiveShopIds();

        if (!in_array($only, ['territories', 'senders', 'all'], true)) {
            $io->error('La opcion --only debe ser territories, senders o all.');

            return 1;
        }

        if ($shopIds === []) {
            $io->warning('No se encontraron tiendas activas para sincronizar.');

            return 0;
        }

        $territoriesByCountry = [];
        $hasErrors = false;

        foreach ($shopIds as $shopId) {
            try {
                $connection = $this->configurationProvider->getShopConfiguration($shopId);
            } catch (\Throwable $exception) {
                $io->error(sprintf('Tienda #%d: %s', $shopId, $exception->getMessage()));
                $hasErrors = true;

                continue;
            }

            if ($only !== 'senders' && !isset($territoriesByCountry[$connection['country']])) {
                $result = $this->territorySyncService->syncCountry($connection);
                $io->text(sprintf('Territorios %s: %s (%d items)', $connection['country'], $result['status'], $result['item_count']));
                $territoriesByCountry[$connection['country']] = true;
                $hasErrors = $hasErrors || $result['status'] !== 'success';
            }

            if ($only !== 'territories') {
                $result = $this->senderAddressSyncService->syncShop($shopId, $connection);
                $io->text(sprintf('Remitentes tienda #%d: %s (%d items)', $shopId, $result['status'], $result['item_count']));
                $hasErrors = $hasErrors || $result['status'] !== 'success';
            }
        }

        if ($hasErrors) {
            $io->warning('La sincronizacion termino con errores recuperables. Revise el detalle anterior.');

            return 1;
        }

        $io->success('Sincronizacion completada correctamente.');

        return 0;
    }
}
