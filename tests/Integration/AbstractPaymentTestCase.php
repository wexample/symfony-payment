<?php

namespace Wexample\SymfonyPayment\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Wexample\SymfonyPayment\Service\PaymentService;
use Wexample\SymfonyPayment\Tests\Fixtures\Payable\EventRecorder;
use Wexample\SymfonyRemotePayment\Class\FakePaymentProvider;

abstract class AbstractPaymentTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = $this->getEntityManager();
        (new SchemaTool($entityManager))->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function getPaymentService(): PaymentService
    {
        return static::getContainer()->get(PaymentService::class);
    }

    protected function getProvider(): FakePaymentProvider
    {
        return static::getContainer()->get(FakePaymentProvider::class);
    }

    protected function getEvents(): EventRecorder
    {
        return static::getContainer()->get(EventRecorder::class);
    }
}
