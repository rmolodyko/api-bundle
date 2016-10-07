<?php declare(strict_types=1);

namespace Satori\Api\Tests\Lib\Serializer;

use Satori\Api\Lib\Serializer\Serializer;
use Satori\Api\Tests\Assets\Entity\User;
use Satori\Api\Tests\WebTestCase;

class SerializerTest extends WebTestCase
{
    public function testOnKernelException()
    {
        $user = new User();
        $user->setEmail('molodyko13@gmail.com');

        $serializer = new Serializer(
            $this->get('serializer'),
            $this->get('doctrine.orm.entity_manager')
        );

        $data = $serializer->toArray($user, ['test']);

        static::assertEquals(['id' => null, 'email' => 'molodyko13@gmail.com'], $data);
    }
}