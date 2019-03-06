<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\DoctrineBootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\DoctrineBootstrap
 *
 * @internal
 */
final class DoctrineBootstrapTest extends TestCase
{
    /**
     * Doctrine Bootstrap instance.
     *
     * @var DoctrineBootstrap
     */
    private $doctrineBootstrap;

    public function setUp()
    {
        $this->doctrineBootstrap = DoctrineBootstrap::instance();
    }

    /**
     * @covers \App\Entity\DoctrineBootstrap::instance()
     */
    public function testNoNewInstanceIsCreated()
    {
        $newInstance = DoctrineBootstrap::instance();
        $anotherInstance = DoctrineBootstrap::instance();

        $this->assertSame($this->doctrineBootstrap, $newInstance);
        $this->assertSame($this->doctrineBootstrap, $anotherInstance);
    }

    /**
     * @covers \App\Entity\DoctrineBootstrap::getEntityManager()
     */
    public function testNoNewEntityManagerIsCreated()
    {
        $entityManager = $this->doctrineBootstrap->getEntityManager();
        $anotherEntityManager = $this->doctrineBootstrap->getEntityManager();
        $yetAnotherEntityManager = $this->doctrineBootstrap->getEntityManager();

        $this->assertSame($entityManager, $anotherEntityManager);
        $this->assertSame($anotherEntityManager, $yetAnotherEntityManager);
    }
}
