<?php

namespace AvalancheDevelopment\SwaggerRouter;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

class RouterTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsLoggerAwareInterface()
    {
        $router = new Router([]);

        $this->assertInstanceOf(LoggerAwareInterface::class, $router);
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

    public function testMatchPathPassesMatchedNonVariablePath()
    {
        $this->markTestIncomplete();
    }

    public function testMatchPathFailsUnmatchedNonVariablePath()
    {
        $this->markTestIncomplete();
    }

    public function testMatchPathPassesMatchedVariablePath()
    {
        $this->markTestIncomplete();
    }

    public function testMatchPathFailsUnmatchedVariablePath()
    {
        $this->markTestIncomplete();
    }
}
