<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface as Stream;
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

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid swagger - could not decode
     */
    public function testInvokationDocumentationRouteBailsOnBadSwagger()
    {
        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

        $mockRequest = $this->createMock(Request::class);
        $mockResponse = $this->createMock(Response::class);

        $callback = function ($request, $response) {
            throw new \Exception('callback should not be called');
        };

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isDocumentationRoute',
                'log',
                'matchPath',
            ])
            ->getMock();
        $router->expects($this->once())
            ->method('isDocumentationRoute')
            ->with($mockRequest)
            ->willReturn(true);
        $router->expects($this->once())
            ->method('log')
            ->with('Documentation route - early response');
        $router->expects($this->never())
            ->method('matchPath');

        $reflectedSwagger->setValue($router, "\xB1\x31");

        $router($mockRequest, $mockResponse, $callback);
    }

    public function testInvokationDocumentationRouteReturnsSwagger()
    {
        $swagger = [
            'swagger' => '2.0',
        ];

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

        $mockRequest = $this->createMock(Request::class);

        $mockBody = $this->createMock(Stream::class);
        $mockBody->expects($this->once())
            ->method('write')
            ->with(json_encode($swagger));

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('withStatus')
            ->with(200)
            ->will($this->returnSelf());
        $mockResponse->expects($this->once())
            ->method('withHeader')
            ->with('Content-type', 'application/json')
            ->will($this->returnSelf());
        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockBody);

        $callback = function ($request, $response) {
            throw new \Exception('callback should not be called');
        };

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isDocumentationRoute',
                'log',
                'matchPath',
            ])
            ->getMock();
        $router->expects($this->once())
            ->method('isDocumentationRoute')
            ->with($mockRequest)
            ->willReturn(true);
        $router->expects($this->once())
            ->method('log')
            ->with('Documentation route - early response');
        $router->expects($this->never())
            ->method('matchPath');

        $reflectedSwagger->setValue($router, $swagger);

        $result = $router($mockRequest, $mockResponse, $callback);

        $this->assertSame($mockResponse, $result);
    }

    /**
     * @expectedException AvalancheDevelopment\Peel\HttpError\NotFound
     * @expectedExceptionMessage No match found in swagger docs
     */
    public function testInvokationBailsOnEmptyPath()
    {
        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

        $mockUri = $this->createMock(Uri::class);
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $mockResponse = $this->createMock(Response::class);

        $callback = function ($request, $response) {
            throw new \Exception('callback should not be called');
        };

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isDocumentationRoute',
                'matchPath',
            ])
            ->getMock();
        $router->expects($this->once())
            ->method('isDocumentationRoute')
            ->with($mockRequest)
            ->willReturn(false);
        $router->expects($this->never())
            ->method('matchPath');

        $reflectedSwagger->setValue($router, [ 'paths' => [] ]);

        $router($mockRequest, $mockResponse, $callback);
    }

    /**
     * @expectedException AvalancheDevelopment\Peel\HttpError\NotFound
     * @expectedExceptionMessage No match found in swagger docs
     */
    public function testInvokationBailsOnUnmatchedPaths()
    {
        $route = '/test-path';

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

        $mockUri = $this->createMock(Uri::class);
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $mockResponse = $this->createMock(Response::class);

        $callback = function ($request, $response) {
            throw new \Exception('callback should not be called');
        };

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isDocumentationRoute',
                'matchPath',
            ])
            ->getMock();
        $router->expects($this->once())
            ->method('isDocumentationRoute')
            ->with($mockRequest)
            ->willReturn(false);
        $router->expects($this->once())
            ->method('matchPath')
            ->with($mockRequest, $route)
            ->willReturn(false);

        $reflectedSwagger->setValue($router, [ 'paths' => [ $route => [] ] ]);

        $router($mockRequest, $mockResponse, $callback);
    }

    /**
     * @expectedException AvalancheDevelopment\Peel\HttpError\MethodNotAllowed
     * @expectedExceptionMessage No method found for this route
     */
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
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

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

        $callback = function ($request, $response) {
            throw new \Exception('callback should not be called');
        };

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isDocumentationRoute',
                'matchPath',
            ])
            ->getMock();
        $router->expects($this->once())
            ->method('isDocumentationRoute')
            ->with($mockRequest)
            ->willReturn(false);
        $router->expects($this->once())
            ->method('matchPath')
            ->with($mockRequest, key($path))
            ->willReturn(true);

        $reflectedSwagger->setValue($router, [ 'paths' => $path ]);

        $router($mockRequest, $mockResponse, $callback);
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
            ->setMethods([
                'getParameters',
                'getSecurity',
                'hydrateParameterValues',
                'isDocumentationRoute',
                'log',
                'matchPath',
            ])
            ->getMock();
        $router->expects($this->once())
            ->method('getParameters')
            ->with(current($path), current($path)['get'])
            ->willReturn([]);
        $router->expects($this->once())
            ->method('getSecurity')
            ->with(current($path)['get'], [ 'paths' => $path ])
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
        $router->expects($this->once())
            ->method('isDocumentationRoute')
            ->with($mockRequest)
            ->willReturn(false);
        $router->expects($this->never())
            ->method('log');
        $router->expects($this->once())
            ->method('matchPath')
            ->with($mockRequest, key($path))
            ->willReturn(true);

        $reflectedSwagger->setValue($router, [ 'paths' => $path ]);

        $result = $router($mockRequest, $mockResponse, $callback);

        $this->assertSame($mockResponse, $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage some exception
     */
    public function testInvokationBailsOnParameterException()
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

        $mockUri = $this->createMock(Uri::class);
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);
        $mockRequest->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');
        $mockRequest->expects($this->never())
            ->method('withAttribute');

        $mockResponse = $this->createMock(Response::class);

        $callback = function ($request, $response) {
            throw new \Exception('callback should not be called');
        };

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'hydrateParameterValues',
                'isDocumentationRoute',
                'matchPath',
            ])
            ->getMock();
        $router->expects($this->once())
            ->method('hydrateParameterValues')
            ->with(
                $this->isInstanceOf(ParameterParser::class),
                $mockRequest,
                [],
                key($path)
            )
            ->will($this->throwException(new \Exception('some exception')));
        $router->expects($this->once())
            ->method('isDocumentationRoute')
            ->with($mockRequest)
            ->willReturn(false);
        $router->expects($this->once())
            ->method('matchPath')
            ->with($mockRequest, key($path))
            ->willReturn(true);

        $reflectedSwagger->setValue($router, [ 'paths' => $path ]);

        $router($mockRequest, $mockResponse, $callback);
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
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

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
            ->setMethods([
                'getParameters',
                'getSecurity',
                'hydrateParameterValues',
                'isDocumentationRoute',
                'log',
                'matchPath',
              ])
            ->getMock();
        $router->expects($this->once())
            ->method('getParameters')
            ->with(current($path), current($path)['get'])
            ->willReturn([ $parameter ]);
        $router->expects($this->once())
            ->method('getSecurity')
            ->with(current($path)['get'], [ 'paths' => $path ])
            ->willReturn([]);
        $router->expects($this->once())
            ->method('hydrateParameterValues')
            ->with(
                $this->isInstanceOf(ParameterParser::class),
                $mockRequest,
                [ $parameter ],
                key($path)
            )
            ->willReturn([ $parameter ]);
        $router->expects($this->once())
            ->method('isDocumentationRoute')
            ->with($mockRequest)
            ->willReturn(false);
        $router->expects($this->never())
            ->method('log');
        $router->expects($this->once())
            ->method('matchPath')
            ->with($mockRequest, key($path))
            ->willReturn(true);

        $reflectedSwagger->setValue($router, [ 'paths' => $path ]);

        $result = $router($mockRequest, $mockResponse, $callback);

        $this->assertSame($mockResponse, $result);
    }

    public function testInvokationReturnsSecurity()
    {
        $path = [
            '/test-path' => [
                'get' => [
                    'description' => 'Some operation',
                    'responses' => [],
                ],
            ],
        ];

        $security = [
            'some security',
        ];

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedSwagger = $reflectedRouter->getProperty('swagger');
        $reflectedSwagger->setAccessible(true);

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
                'security' => $security,
            ])
            ->will($this->returnSelf());

        $mockResponse = $this->createMock(Response::class);

        $callback = function ($request, $response) {
            return $response;
        };

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getParameters',
                'getSecurity',
                'hydrateParameterValues',
                'isDocumentationRoute',
                'log',
                'matchPath',
              ])
            ->getMock();
        $router->expects($this->once())
            ->method('getParameters')
            ->with(current($path), current($path)['get'])
            ->willReturn([]);
        $router->expects($this->once())
            ->method('getSecurity')
            ->with(current($path)['get'], [ 'paths' => $path ])
            ->willReturn($security);
        $router->expects($this->once())
            ->method('hydrateParameterValues')
            ->with(
                $this->isInstanceOf(ParameterParser::class),
                $mockRequest,
                [],
                key($path)
            )
            ->willReturn([]);
        $router->expects($this->once())
            ->method('isDocumentationRoute')
            ->with($mockRequest)
            ->willReturn(false);
        $router->expects($this->never())
            ->method('log');
        $router->expects($this->once())
            ->method('matchPath')
            ->with($mockRequest, key($path))
            ->willReturn(true);

        $reflectedSwagger->setValue($router, [ 'paths' => $path ]);

        $result = $router($mockRequest, $mockResponse, $callback);

        $this->assertSame($mockResponse, $result);
    }

    public function testIsDocumentationRouteReturnsTrueOnMatch()
    {
        $path = '/api-docs';

        $mockUri = $this->createMock(Uri::class);
        $mockUri->method('getPath')
            ->willReturn($path);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedIsDocumentationRoute = $reflectedRouter->getMethod('isDocumentationRoute');
        $reflectedIsDocumentationRoute->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedIsDocumentationRoute->invokeArgs($router, [ $mockRequest ]);

        $this->assertTrue($result);
    }

    public function testIsDocumentationRouteReturnsFalseOnUnmatch()
    {
        $path = '/invalid-path';

        $mockUri = $this->createMock(Uri::class);
        $mockUri->method('getPath')
            ->willReturn($path);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedIsDocumentationRoute = $reflectedRouter->getMethod('isDocumentationRoute');
        $reflectedIsDocumentationRoute->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedIsDocumentationRoute->invokeArgs($router, [ $mockRequest ]);

        $this->assertFalse($result);
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
        $result = $reflectedMatchPath->invokeArgs(
            $router,
            [
                $mockRequest,
                $testPath,
            ]
        );

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
        $result = $reflectedMatchPath->invokeArgs(
            $router,
            [
                $mockRequest,
                $testPath,
            ]
        );

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
        $result = $reflectedMatchPath->invokeArgs(
            $router,
            [
                $mockRequest,
                $testPath,
            ]
        );

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
        $result = $reflectedMatchPath->invokeArgs(
            $router,
            [
                $mockRequest,
                $testPath,
            ]
        );

        $this->assertFalse($result);
    }

    public function testGetParametersHandlesNoParameters()
    {
        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedGetParameters = $reflectedRouter->getMethod('getParameters');
        $reflectedGetParameters->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedGetParameters->invokeArgs(
            $router,
            [ [], [] ]
        );

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

        $result = $reflectedGetParameters->invokeArgs(
            $router,
            [
                [ 'parameters' => $parameters ],
                []
            ]
        );

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

        $result = $reflectedGetParameters->invokeArgs(
            $router,
            [
                [],
                [ 'parameters' => $parameters ]
            ]
        );

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

        $result = $reflectedGetParameters->invokeArgs(
            $router,
            [
                [ 'parameters' => $parameters ],
                [ 'parameters' => $parameters ],
            ]
        );

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
        $result = $reflectedUniqueParameterKey->invokeArgs(
            $router,
            [ $parameter ]
        );

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
        $result = $reflectedHydrateParameterValues->invokeArgs(
            $router,
            [
                $mockParser,
                $mockRequest,
                [],
                '',
            ]
        );

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
        $result = $reflectedHydrateParameterValues->invokeArgs(
            $router,
            [
                $mockParser,
                $mockRequest,
                $parameters,
                $route,
            ]
        );

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

    public function testGetSecurityReturnsOperationSecurity()
    {
        $swagger = [
            'security' => [
                'invalid',
            ],
        ];

        $operation = [
            'security' => [
                'valid',
            ],
        ];

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedGetSecurity = $reflectedRouter->getMethod('getSecurity');
        $reflectedGetSecurity->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedGetSecurity->invokeArgs($router, [
            $operation,
            $swagger,
        ]);

        $this->assertEquals($operation['security'], $result);
    }

    public function testGetSecurityReturnsGlobalSecurity()
    {
        $swagger = [
            'security' => [
                'invalid',
            ],
        ];

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedGetSecurity = $reflectedRouter->getMethod('getSecurity');
        $reflectedGetSecurity->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedGetSecurity->invokeArgs($router, [
            [],
            $swagger,
        ]);

        $this->assertEquals($swagger['security'], $result);
    }

    public function testGetSecurityReturnsEmptyAsDefault()
    {
        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedGetSecurity = $reflectedRouter->getMethod('getSecurity');
        $reflectedGetSecurity->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedGetSecurity->invokeArgs($router, [
            [],
            [],
        ]);

        $this->assertEquals([], $result);
    }

    public function testLog()
    {
        $message = 'test debug message';

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with("swagger-router-middleware: {$message}");

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedLog = $reflectedRouter->getMethod('log');
        $reflectedLog->setAccessible(true);
        $reflectedLogger = $reflectedRouter->getProperty('logger');
        $reflectedLogger->setAccessible(true);

        $router = new Router([]);
        $reflectedLogger->setValue($router, $mockLogger);
        $reflectedLog->invokeArgs($router, [ $message ]);       
    }
}
