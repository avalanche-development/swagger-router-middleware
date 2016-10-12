<?php

namespace AvalancheDevelopment\SwaggerRouter;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

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

    /**
     * @expectedException AvalancheDevelopment\SwaggerRouter\Exception\NotFound
     */
    public function testInvokationBailsOnEmptyPath()
    {
        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

        $mockRequest = $this->createMock(ServerRequestInterface::class);

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'matchPath' ])
            ->getMock();
        $router->expects($this->never())
            ->method('matchPath');

        $reflectedSwagger->setValue($router, [ 'paths' => [] ]);

        $router($mockRequest);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerRouter\Exception\NotFound
     */
    public function testInvokationBailsOnUnmatchedPaths()
    {
        $route = '/test-path';

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

        $mockRequest = $this->createMock(ServerRequestInterface::class);

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'matchPath' ])
            ->getMock();
        $router->expects($this->once())
            ->method('matchPath')
            ->with($mockRequest, $route)
            ->willReturn(false);

        $reflectedSwagger->setValue($router, [ 'paths' => [ $route => [] ] ]);

        $router($mockRequest);
    }

    public function testInvokationBailsOnUnmatchedOperation()
    {
    }

    public function testInvokationReturnsMatchedOperation()
    {
        $path = [
            '/test-path' => [
                'get' => [
                    'description' => 'Some operation',
                    'responses' => [],
                ],
            ],
        ];

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');
        $mockRequest->expects($this->once())
            ->method('withAttribute')
            ->with('swagger', [
                'path' => current($path),
                'operation' => current($path)['get'],
                'params' => [],
            ])
            ->will($this->returnSelf());

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'matchPath' ])
            ->getMock();
        $router->expects($this->once())
            ->method('matchPath')
            ->with($mockRequest, key($path))
            ->willReturn(true);

        $reflectedSwagger->setValue($router, [ 'paths' => $path ]);

        $result = $router($mockRequest);

        $this->assertInstanceOf(ServerRequestInterface::class, $result);
    }

    public function testInvokationReturnsParameters()
    {
    }

    public function testMatchPathPassesMatchedNonVariablePath()
    {
        $testPath = '/test-path';

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/test-path');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedMatchPath = $reflectedRouter->getMethod('matchPath');
        $reflectedMatchPath->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedMatchPath->invokeArgs($router, [
            $mockRequest,
            $testPath,
        ]);

        $this->assertTrue($result);
    }

    public function testMatchPathFailsUnmatchedNonVariablePath()
    {
        $testPath = '/test-path';

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/not-test-path');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedMatchPath = $reflectedRouter->getMethod('matchPath');
        $reflectedMatchPath->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedMatchPath->invokeArgs($router, [
            $mockRequest,
            $testPath,
        ]);

        $this->assertFalse($result);
    }

    public function testMatchPathPassesMatchedVariablePath()
    {
        $testPath = '/resource/{resource_id}';

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/resource/123');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedMatchPath = $reflectedRouter->getMethod('matchPath');
        $reflectedMatchPath->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedMatchPath->invokeArgs($router, [
            $mockRequest,
            $testPath,
        ]);

        $this->assertTrue($result);
    }

    public function testMatchPathFailsUnmatchedVariablePath()
    {
        $testPath = '/resource/{resource_id}';

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/other-resource/123');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedMatchPath = $reflectedRouter->getMethod('matchPath');
        $reflectedMatchPath->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedMatchPath->invokeArgs($router, [
            $mockRequest,
            $testPath,
        ]);

        $this->assertFalse($result);
    }

    public function testGetParametersHandlesNoParameters()
    {
        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedGetParameters = $reflectedRouter->getMethod('getParameters');
        $reflectedGetParameters->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedGetParameters->invokeArgs($router, [ [], [] ]);

        $this->assertEquals([], $result);
    }

    public function testGetParametersHandlesPathParameters()
    {
        $parameters = [
            [
                'name' => 'some parameter',
            ],
        ];

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedGetParameters = $reflectedRouter->getMethod('getParameters');
        $reflectedGetParameters->setAccessible(true);

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'uniqueParameterKey' ])
            ->getMock();
        $router->expects($this->once())
            ->method('uniqueParameterKey')
            ->with(current($parameters))
            ->willReturn('unique value');

        $result = $reflectedGetParameters->invokeArgs($router, [ [ 'parameters' => $parameters ], [] ]);

        $this->assertEquals($parameters, $result);
    }

    public function testGetParametersHandlesOperationParameters()
    {
        $parameters = [
            [
                'name' => 'some parameter',
            ],
        ];

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedGetParameters = $reflectedRouter->getMethod('getParameters');
        $reflectedGetParameters->setAccessible(true);

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'uniqueParameterKey' ])
            ->getMock();
        $router->expects($this->once())
            ->method('uniqueParameterKey')
            ->with(current($parameters))
            ->willReturn('unique value');

        $result = $reflectedGetParameters->invokeArgs($router, [ [], [ 'parameters' => $parameters ] ]);

        $this->assertEquals($parameters, $result);
    }

    public function testGetParametersHandlesOverrides()
    {
        $parameters = [
            [
                'name' => 'some parameter',
            ],
        ];

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedGetParameters = $reflectedRouter->getMethod('getParameters');
        $reflectedGetParameters->setAccessible(true);

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'uniqueParameterKey' ])
            ->getMock();
        $router->expects($this->exactly(2))
            ->method('uniqueParameterKey')
            ->with(current($parameters))
            ->willReturn('unique value');

        $result = $reflectedGetParameters->invokeArgs($router, [
            [ 'parameters' => $parameters ],
            [ 'parameters' => $parameters ],
        ]);

        $this->assertEquals($parameters, $result);
    }


    public function testUniqueParameterKey()
    {
        $parameter = [
            'name' => 'some parameter',
            'in' => 'path',
        ];

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedUniqueParameterKey = $reflectedRouter->getMethod('uniqueParameterKey');
        $reflectedUniqueParameterKey->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedUniqueParameterKey->invokeArgs($router, [ $parameter ]);

        $this->assertEquals("{$parameter['name']}-{$parameter['in']}", $result);
    }

    public function testHydrateParameterValuesHandlesNoParameters()
    {
        $mockRequest = $this->createMock(RequestInterface::class);

        $mockParser = $this->createMock(ParameterParser::class);
        $mockParser->expects($this->never())
            ->method('__invoke');

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedHydrateParameterValues = $reflectedRouter->getMethod('hydrateParameterValues');
        $reflectedHydrateParameterValues->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedHydrateParameterValues->invokeArgs($router, [
            $mockParser,
            $mockRequest,
            [],
            '',
        ]);

        $this->assertEquals([], $result);
    }

    public function testHydrateParameterValuesHandlesMultipleParameters()
    {
        $route = 'some route';
        $parameters = [
            [ 'name' => 'parameter one' ],
            [ 'name' => 'parameter two' ],
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $valueMap = [
            [ $mockRequest, $parameters[0], $route, 'value one' ],
            [ $mockRequest, $parameters[1], $route, 'value two' ],
        ];

        $mockParser = $this->createMock(ParameterParser::class);
        $mockParser->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [ $mockRequest, $parameters[0], $route ],
                [ $mockRequest, $parameters[1], $route ]
            )
            ->will($this->returnValueMap($valueMap));

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedHydrateParameterValues = $reflectedRouter->getMethod('hydrateParameterValues');
        $reflectedHydrateParameterValues->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedHydrateParameterValues->invokeArgs($router, [
            $mockParser,
            $mockRequest,
            $parameters,
            $route,
        ]);

        $this->assertEquals([
            [
                'name' => 'parameter one',
                'value' => 'value one',
            ],
            [
                'name' => 'parameter two',
                'value' => 'value two',
            ],
        ], $result);
    }
}
