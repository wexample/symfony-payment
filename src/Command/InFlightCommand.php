<?php

namespace Wexample\SymfonyPayment\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wexample\SymfonyHelpers\Command\AbstractBundleCommand;
use Wexample\SymfonyHelpers\Service\BundleService;
use Wexample\SymfonyPayment\Repository\PaymentRepository;
use Wexample\SymfonyPayment\WexampleSymfonyPaymentBundle;

/**
 * Fails while payments may still be confirmed by a provider, so a deployment
 * script can wait instead of cutting a webhook short.
 */
class InFlightCommand extends AbstractBundleCommand
{
    public function __construct(
        BundleService $bundleService,
        private readonly PaymentRepository $paymentRepository,
    ) {
        parent::__construct($bundleService);
    }

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyPaymentBundle::class;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Fails when pending or processing payments were updated recently.')
            ->addOption('minutes', null, InputOption::VALUE_REQUIRED, 'How recent counts as in flight.', '30');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $count = $this->paymentRepository->countInFlight((int) $input->getOption('minutes'));
        $output->writeln(sprintf('%d payment(s) in flight.', $count));

        return $count > 0 ? self::FAILURE : self::SUCCESS;
    }
}
