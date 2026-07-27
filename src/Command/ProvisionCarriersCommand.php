<?php

namespace Vx\Sendifico\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Vx\Sendifico\Carrier\CarrierProvisionService;

final class ProvisionCarriersCommand extends Command
{
    public function __construct(
        private readonly CarrierProvisionService $carrierProvisionService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Provisiona los carriers persistentes de Sendifico y crea el mapeo local por tienda.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $results = $this->carrierProvisionService->provisionAll();
        $hasErrors = false;

        foreach ($results as $result) {
            if ($result['status'] === 'success') {
                $io->text(sprintf('%s: %s', $result['label'], $result['message']));
                continue;
            }

            $hasErrors = true;
            $io->error(sprintf('%s: %s', $result['label'], $result['message']));
        }

        if ($hasErrors) {
            $io->warning('La provision de carriers termino con errores.');

            return 1;
        }

        $io->success('Carriers de Sendifico provisionados correctamente.');

        return 0;
    }
}
