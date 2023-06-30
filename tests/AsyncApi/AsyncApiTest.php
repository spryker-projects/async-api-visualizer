<?php

namespace App\Tests\AsyncApi;

use App\AsyncApi\AsyncApi;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AsyncApiTest extends KernelTestCase
{
    /**
     * @throws \Exception
     */
    public function testFooBar()
    {
        $container = static::getContainer();
        /** @var AsyncApi $asyncApi */
        $asyncApi = $container->get(AsyncApi::class);

        $result = $asyncApi->getAsyncApiDetails();

        $this->assertIsArray($result, sprintf('Expected to get an array with AsyncAPI details but got "%s"', gettype($result)));
    }
}