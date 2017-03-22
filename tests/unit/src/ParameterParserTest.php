<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use AvalancheDevelopment\SwaggerRouterMiddleware\Parser\ParserInterface;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use ReflectionClass;

class ParameterParserTest extends PHPUnit_Framework_TestCase
{

    public function testInvokeCallsGetParser()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [ 'something here' ];
        $mockRoute = '/some-route';
        $mockValue = 'some value';

        $mockParser = $this->createMock(ParserInterface::class);
        $mockParser->method('getValue')
            ->willReturn($mockValue);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParser' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getParser')
            ->with($mockRequest, $mockParameter, $mockRoute)
            ->willReturn($mockParser);

        $parameterParser->__invoke($mockRequest, $mockParameter, $mockRoute);
    }

    public function testInvokeUsesParserFromGetParser()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [ 'something here' ];
        $mockRoute = '/some-route';
        $mockValue = 'some value';

        $mockParser = $this->createMock(ParserInterface::class);
        $mockParser->expects($this->once())
            ->method('getValue')
            ->willReturn($mockValue);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParser' ])
            ->getMock();
        $parameterParser->method('getParser')
            ->willReturn($mockParser);

        $parameterParser->__invoke($mockRequest, $mockParameter, $mockRoute);
    }

    public function testInvokeReturnsDefaultValue()
    {
        $expectedValue = 'some default value';

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [
            'default' => $expectedValue,
        ];
        $mockRoute = '/some-route';
        $mockValue = null;

        $mockParser = $this->createMock(ParserInterface::class);
        $mockParser->method('getValue')
            ->willReturn($mockValue);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParser' ])
            ->getMock();
        $parameterParser->method('getParser')
            ->willReturn($mockParser);

        $parameterParser->__invoke($mockRequest, $mockParameter, $mockRoute);
    }

    public function testInvokeUsesValueFromParser()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [ 'something here' ];
        $mockRoute = '/some-route';
        $mockValue = 'some value';

        $mockParser = $this->createMock(ParserInterface::class);
        $mockParser->method('getValue')
            ->willReturn($mockValue);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParser' ])
            ->getMock();
        $parameterParser->method('getParser')
            ->willReturn($mockParser);

        $parameterParser->__invoke($mockRequest, $mockParameter, $mockRoute);
    }

    public function testGetParserHandlesQueryParameter()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [
            'in' => 'query',
        ];
        $mockRoute = '/some-route';

        $reflectedQueryParser = new ReflectionClass(Parser\Query::class);
        $reflectedRequest = $reflectedQueryParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedQueryParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetParser = $reflectedParameterParser->getMethod('getParser');
        $reflectedGetParser->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetParser->invokeArgs(
            $parameterParser,
            [
                $mockRequest,
                $mockParameter,
                $mockRoute,
            ]
        );

        $this->assertInstanceOf(ParserInterface::class, $result);
        $this->assertInstanceOf(Parser\Query::class, $result);
        $this->assertAttributeSame($mockRequest, 'request', $result);
        $this->assertAttributeSame($mockParameter, 'parameter', $result);
    }

    public function testGetParserHandlesHeaderParameter()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [
            'in' => 'header',
        ];
        $mockRoute = '/some-route';

        $reflectedHeaderParser = new ReflectionClass(Parser\Header::class);
        $reflectedRequest = $reflectedHeaderParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedHeaderParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetParser = $reflectedParameterParser->getMethod('getParser');
        $reflectedGetParser->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetParser->invokeArgs(
            $parameterParser,
            [
                $mockRequest,
                $mockParameter,
                $mockRoute,
            ]
        );

        $this->assertInstanceOf(ParserInterface::class, $result);
        $this->assertInstanceOf(Parser\Header::class, $result);
        $this->assertAttributeSame($mockRequest, 'request', $result);
        $this->assertAttributeSame($mockParameter, 'parameter', $result);
    }

    public function testGetParserHandlesPathParameter()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [
            'in' => 'path',
        ];
        $mockRoute = '/some-route';

        $reflectedPathParser = new ReflectionClass(Parser\Path::class);
        $reflectedRequest = $reflectedPathParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedPathParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);
        $reflectedRoute = $reflectedPathParser->getProperty('route');
        $reflectedRoute->setAccessible(true);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetParser = $reflectedParameterParser->getMethod('getParser');
        $reflectedGetParser->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetParser->invokeArgs(
            $parameterParser,
            [
                $mockRequest,
                $mockParameter,
                $mockRoute,
            ]
        );

        $this->assertInstanceOf(ParserInterface::class, $result);
        $this->assertInstanceOf(Parser\Path::class, $result);
        $this->assertAttributeSame($mockRequest, 'request', $result);
        $this->assertAttributeSame($mockParameter, 'parameter', $result);
        $this->assertAttributeSame($mockRoute, 'route', $result);
    }

    public function testGetParserHandlesFormParameter()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [
            'in' => 'formData',
        ];
        $mockRoute = '/some-route';

        $reflectedFormParser = new ReflectionClass(Parser\Form::class);
        $reflectedRequest = $reflectedFormParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedFormParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetParser = $reflectedParameterParser->getMethod('getParser');
        $reflectedGetParser->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetParser->invokeArgs(
            $parameterParser,
            [
                $mockRequest,
                $mockParameter,
                $mockRoute,
            ]
        );

        $this->assertInstanceOf(ParserInterface::class, $result);
        $this->assertInstanceOf(Parser\Form::class, $result);
        $this->assertAttributeSame($mockRequest, 'request', $result);
        $this->assertAttributeSame($mockParameter, 'parameter', $result);
    }

    public function testGetParserHandlesBodyParameter()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [
            'in' => 'body',
        ];
        $mockRoute = '/some-route';

        $reflectedBodyParser = new ReflectionClass(Parser\Body::class);
        $reflectedRequest = $reflectedBodyParser->getProperty('request');
        $reflectedRequest->setAccessible(true);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetParser = $reflectedParameterParser->getMethod('getParser');
        $reflectedGetParser->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetParser->invokeArgs(
            $parameterParser,
            [
                $mockRequest,
                $mockParameter,
                $mockRoute,
            ]
        );

        $this->assertInstanceOf(ParserInterface::class, $result);
        $this->assertInstanceOf(Parser\Body::class, $result);
        $this->assertAttributeSame($mockRequest, 'request', $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid parameter type defined in swagger
     */
    public function testGetParserBailsOnInvalidParameter()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [
            'in' => 'invalid',
        ];
        $mockRoute = '/some-route';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetParser = $reflectedParameterParser->getMethod('getParser');
        $reflectedGetParser->setAccessible(true);

        $parameterParser = new ParameterParser;
        $reflectedGetParser->invokeArgs(
            $parameterParser,
            [
                $mockRequest,
                $mockParameter,
                $mockRoute,
            ]
        );
    }
}
