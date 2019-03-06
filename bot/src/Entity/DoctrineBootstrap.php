<?php

namespace App\Entity;

use App\Kernel;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Singleton that keeps a unique entity manager created and ready to use. Some
 * of the code bits have been taken from https://stackoverflow.com/questions/203336/creating-the-singleton-design-pattern-in-php5.
 */
final class DoctrineBootstrap
{
    /**
     * Entity Manager.
     *
     * @var EntityManager
     */
    private $em;

    /**
     * Make constructor private, so nobody can create a new object.
     */
    private function __construct()
    {
    }

    /**
     * Make clone magic method private, so nobody can clone the instance.
     */
    private function __clone()
    {
    }

    /**
     * Make sleep magic method private, so nobody can serialize the instance.
     */
    private function __sleep()
    {
    }

    /**
     * Make wakeup magic method private, so nobody can unserialize the instance.
     */
    private function __wakeup()
    {
    }

    /**
     * Return the entity manager.
     *
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        if (\is_null($this->em)) {
            $this->createEntityManager();
        }

        return $this->em;
    }

    public static function instance()
    {
        static $instance = false;
        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Pull an entity manager from the Symfony container.
     *
     * The kernel is booted and the container compiled, which takes into
     * account the parameters defined in the Symfony configuration files. When
     * an entity manager is pulled from the container, the instance will have
     * been configured as defined in the aforementioned parameters.
     *
     * One of the advantages is that the configuration will be consistent even
     * if for some reason, for example, the cache directory paths are changed.
     */
    private function createEntityManager(): void
    {
        $kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
        $kernel->boot();
        $container = $kernel->getContainer();
        $this->em = $container->get('doctrine.orm.default_entity_manager');
        $kernel->terminate(new Request(), new Response());
    }
}
