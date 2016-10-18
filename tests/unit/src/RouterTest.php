<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface as Uri;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
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

    public function testInvokationBailsOnEmptyPath()
    {
        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedLogger = $reflectedRouter->getProperty('logger');
        $reflectedLogger->setAccessible(true);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

        $mockLogger = $this->createMock(Logger::class);

        $mockUri = $this->createMock(Uri::class);
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->will($this->returnSelf());

        $callback = function ($request, $response) {
            throw new \Exception('callback should not be called');
        };

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'matchPath' ])
            ->getMock();
        $router->expects($this->never())
            ->method('matchPath');

        $reflectedLogger->setValue($router, $mockLogger);
        $reflectedSwagger->setValue($router, [ 'paths' => [] ]);

        $result = $router($mockRequest, $mockResponse, $callback);

        $this->assertSame($mockResponse, $result);
    }

    public function testInvokationBailsOnUnmatchedPaths()
    {
        $route = '/test-path';

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedLogger = $reflectedRouter->getProperty('logger');
        $reflectedLogger->setAccessible(true);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

        $mockLogger = $this->createMock(Logger::class);

        $mockUri = $this->createMock(Uri::class);
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->will($this->returnSelf());

        $callback = function ($request, $response) {
            throw new \Exception('callback should not be called');
        };

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'matchPath' ])
            ->getMock();
        $router->expects($this->once())
            ->method('matchPath')
            ->with($mockRequest, $route)
            ->willReturn(false);

        $reflectedLogger->setValue($router, $mockLogger);
        $reflectedSwagger->setValue($router, [ 'paths' => [ $route => [] ] ]);

        $result = $router($mockRequest, $mockResponse, $callback);

        $this->assertSame($mockResponse, $result);
    }

    public function testInvokationBailsOnUnmatchedOperation()
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
        $reflectedLogger = $reflectedRouter->getProperty('logger');
        $reflectedLogger->setAccessible(true);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

        $mockLogger = $this->createMock(Logger::class);

        $mockUri = $this->createMock(Uri::class);
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);
        $mockRequest->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');
        $mockRequest->expects($this->never())
            ->method('withAttribute');

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('withStatus')
            ->with(405)
            ->will($this->returnSelf());

        $callback = function ($request, $response) {
            throw new \Exception('callback should not be called');
        };

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'matchPath' ])
            ->getMock();
        $router->expects($this->once())
            ->method('matchPath')
            ->with($mockRequest, key($path))
            ->willReturn(true);

        $reflectedLogger->setValue($router, $mockLogger);
        $reflectedSwagger->setValue($router, [ 'paths' => $path ]);

        $result = $router($mockRequest, $mockResponse, $callback);

        $this->assertSame($mockResponse, $result);
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
        $reflectedLogger = $reflectedRouter->getProperty('logger');
        $reflectedLogger->setAccessible(true);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

        $mockLogger = $this->createMock(Logger::class);

        $mockUri = $this->createMock(Uri::class);
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);
        $mockRequest->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');
        $mockRequest->expects($this->once())
            ->method('withAttribute')
            ->with('swagger', [
                'apiPath' => key($path),
                'path' => current($path),
                'operation' => current($path)['get'],
                'params' => [],
                'security' => [],
            ])
            ->will($this->returnSelf());

        $mockResponse = $this->createMock(Response::class);

        $callback = function ($request, $response) {
            return $response;
        };

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'matchPath', 'getParameters', 'hydrateParameterValues' ])
            ->getMock();
        $router->expects($this->once())
            ->method('matchPath')
            ->with($mockRequest, key($path))
            ->willReturn(true);
        $router->expects($this->once())
            ->method('getParameters')
            ->with(current($path), current($path)['get'])
            ->willReturn([]);
        $router->expects($this->once())
            ->method('hydrateParameterValues')
            ->with(
                $this->isInstanceOf(ParameterParser::class),
                $mockRequest,
                [],
                key($path)
            )
            ->willReturn([]);

        $reflectedLogger->setValue($router, $mockLogger);
        $reflectedSwagger->setValue($router, [ 'paths' => $path ]);

        $result = $router($mockRequest, $mockResponse, $callback);

        $this->assertSame($mockResponse, $result);
    }

    public function testInvokationReturnsParameters()
    {
        $path = [
            '/test-path' => [
                'get' => [
                    'description' => 'Some operation',
                    'responses' => [],
                ],
            ],
        ];

        $parameter = [
            'name' => 'id',
            'in' => 'query',
        ];

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedLogger = $reflectedRouter->getProperty('logger');
        $reflectedLogger->setAccessible(true);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

        $mockLogger = $this->createMock(Logger::class);

        $mockUri = $this->createMock(Uri::class);
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);
        $mockRequest->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');
        $mockRequest->expects($this->once())
            ->method('withAttribute')
            ->with('swagger', [
                'apiPath' => key($path),
                'path' => current($path),
                'operation' => current($path)['get'],
                'params' => [ $parameter ],
                'security' => [],
            ])
            ->will($this->returnSelf());

        $mockResponse = $this->createMock(Response::class);

        $callback = function ($request, $response) {
            return $response;
        };

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'matchPath', 'getParameters', 'hydrateParameterValues' ])
            ->getMock();
        $router->expects($this->once())
            ->method('matchPath')
            ->with($mockRequest, key($path))
            ->willReturn(true);
        $router->expects($this->once())
            ->method('getParameters')
            ->with(current($path), current($path)['get'])
            ->willReturn([ $parameter ]);
        $router->expects($this->once())
            ->method('hydrateParameterValues')
            ->with(
                $this->isInstanceOf(ParameterParser::class),
                $mockRequest,
                [ $parameter ],
                key($path)
            )
            ->willReturn([ $parameter ]);

        $reflectedLogger->setValue($router, $mockLogger);
        $reflectedSwagger->setValue($router, [ 'paths' => $path ]);

        $result = $router($mockRequest, $mockResponse, $callback);

        $this->assertSame($mockResponse, $result);
    }

    public function testMatchPathPassesMatchedNonVariablePath()
    {
        $testPath = '/test-path';

        $mockUri = $this->createMock(Uri::class);
        $mockUri->method('getPath')
            ->willReturn('/test-path');

        $mockRequest = $this->createMock(Request::class);
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

        $mockUri = $this->createMock(Uri::class);
        $mockUri->method('getPath')
            ->willReturn('/not-test-path');

        $mockRequest = $this->createMock(Request::class);
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

        $mockUri = $this->createMock(Uri::class);
        $mockUri->method('getPath')
            ->willReturn('/resource/123');

        $mockRequest = $this->createMock(Request::class);
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

        $mockUri = $this->createMock(Uri::class);
        $mockUri->method('getPath')
            ->willReturn('/other-resource/123');

        $mockRequest = $this->createMock(Request::class);
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
        $mockRequest = $this->createMock(Request::class);

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

        $mockRequest = $this->createMock(Request::class);

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
