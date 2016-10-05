<?php

namespace AvalancheDevelopment\SwaggerRouter;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

class ParameterParserTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsLoggerAwareInterface()
    {
        $router = new ParameterParser;

        $this->assertInstanceOf(LoggerAwareInterface::class, $router);
    }

    public function testConstructSetsNullLogger()
    {
        $logger = new NullLogger;

        $router = new ParameterParser;

        $this->assertAttributeEquals($logger, 'logger', $router);
    }

    public function testGetQueryValueReturnsNullIfUnmatched()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getQuery')
            ->willReturn('some_variable=bar');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetQueryValue = $reflectedParameterParser->getMethod('getQueryValue');
        $reflectedGetQueryValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetQueryValue->invokeArgs($parameterParser, [
            $mockRequest,
            'other_variable',
        ]);

        $this->assertNull($result);
    }

    public function testGetQueryValueReturnsValueIfMatched()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getQuery')
            ->willReturn('some_variable=value');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetQueryValue = $reflectedParameterParser->getMethod('getQueryValue');
        $reflectedGetQueryValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetQueryValue->invokeArgs($parameterParser, [
            $mockRequest,
            'some_variable',
        ]);

        $this->assertEquals('value', $result);
    }

    public function testGetHeaderValueReturnsNullIfUnmatched()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getHeaders')
            ->willReturn([
                'Some-Header' => [ 'value' ],
            ]);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetHeaderValue = $reflectedParameterParser->getMethod('getHeaderValue');
        $reflectedGetHeaderValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetHeaderValue->invokeArgs($parameterParser, [
            $mockRequest,
            'Other-Header',
        ]);

        $this->assertNull($result);
    }

    public function testGetHeaderValueReturnsSingleValueIfMatched()
    {
        $headerValue = 'some_value';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getHeaders')
            ->willReturn([
                'Some-Header' => [ $headerValue ],
            ]);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetHeaderValue = $reflectedParameterParser->getMethod('getHeaderValue');
        $reflectedGetHeaderValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetHeaderValue->invokeArgs($parameterParser, [
            $mockRequest,
            'Some-Header',
        ]);

        $this->assertEquals($headerValue, $result);
    }

    public function testGetHeaderValueReturnsMultipleValuesIfMatched()
    {
        $headerValue = [
            'first_value',
            'second_value',
            'third_value',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getHeaders')
            ->willReturn([
                'Some-Header' => $headerValue,
            ]);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetHeaderValue = $reflectedParameterParser->getMethod('getHeaderValue');
        $reflectedGetHeaderValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetHeaderValue->invokeArgs($parameterParser, [
            $mockRequest,
            'Some-Header',
        ]);

        $this->assertEquals($headerValue, $result);
    }
}
