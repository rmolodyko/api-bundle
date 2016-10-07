<?php declare(strict_types=1);

namespace Satori\Api\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WebTestCase extends SymfonyWebTestCase
{
    /** @var  ContainerInterface */
    public static $container;

    /**
     * Get service
     *
     * @param $serviceName
     * @return mixed
     */
    public function get($serviceName)
    {
        return self::$container->get($serviceName);
    }

    /**
     * Set upt the kernel
     */
    public static function setUpBeforeClass()
    {
        //start the symfony kernel
        $kernel = static::createKernel();
        $kernel->boot();

        // Get the DI container
        self::$container = $kernel->getContainer();
    }
}