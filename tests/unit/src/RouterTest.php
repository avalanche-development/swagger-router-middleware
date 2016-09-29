<?php

namespace AvalancheDevelopment\SwaggerRouter;

use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

class RouterTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsLoggerAwareInterface()
    {
        $router = new Router([]);

        $this->assertInstanceOf(LoggerAwareInterface::class, $router);
    }

    /**
     * @expectedException TypeError
     */
    public function testConstructErrorsWithoutSwagger()
    {
        $router = new Router;
    }

    public function testConstructSetsSwaggerParameter()
    {
        $swagger = [
            'swagger' => '2.0',
        ];

        $router = new Router($swagger);

        $this->assertAttributeSame($swagger, 'swagger', $router);
    }

    public function testConstructSetsNullLogger()
    {
        $logger = new NullLogger;

        $router = new Router([]);

        $this->assertAttributeEquals($logger, 'logger', $router);
    }
}
