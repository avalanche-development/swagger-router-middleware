<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use ReflectionClass;

class PathTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsParserInterface()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [];
        $mockRoute = '';

        $pathParser = new Path($mockRequest, $mockParameter, $mockRoute);

        $this->isInstanceOf(ParserInterface::class, $pathParser);
    }

    public function testConstructSetsRequest()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [];
        $mockRoute = '';

        $pathParser = new Path($mockRequest, $mockParameter, $mockRoute);

        $this->assertAttributeSame($mockRequest, 'request', $pathParser);
    }

    public function testConstructSetsParameter()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [ 'some value' ];
        $mockRoute = '';

        $pathParser = new Path($mockRequest, $mockParameter, $mockRoute);

        $this->assertAttributeSame($mockParameter, 'parameter', $pathParser);
    }

    public function testConstructSetsRoute()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [];
        $mockRoute = 'some value';

        $pathParser = new Path($mockRequest, $mockParameter, $mockRoute);

        $this->assertAttributeSame($mockRoute, 'route', $pathParser);
    }

    public function testGetValueReturnsNullIfUnmatched()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/path');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $mockParameter = [
            'name' => 'id',
        ];
        $mockRoute = '/path/{id}';

        $reflectedPathParser = new ReflectionClass(Path::class);
        $reflectedRequest = $reflectedPathParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedPathParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);
        $reflectedRoute = $reflectedPathParser->getProperty('route');
        $reflectedRoute->setAccessible(true);

        $pathParser = $this->getMockBuilder(Path::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'explodeValue' ])
            ->getMock();
        $pathParser->expects($this->never())
            ->method('explodeValue');

        $reflectedRequest->setValue($pathParser, $mockRequest);
        $reflectedParameter->setValue($pathParser, $mockParameter);
        $reflectedRoute->setValue($pathParser, $mockRoute);

        $result = $pathParser->getValue();

        $this->assertNull($result);
    }

    public function testGetValueReturnsSingleValueIfMatched()
    {
        $expectedValue = '1234';

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn("/path/{$expectedValue}");

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $mockParameter = [
            'name' => 'id',
            'type' => 'string',
        ];
        $mockRoute = '/path/{id}';

        $reflectedPathParser = new ReflectionClass(Path::class);
        $reflectedRequest = $reflectedPathParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedPathParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);
        $reflectedRoute = $reflectedPathParser->getProperty('route');
        $reflectedRoute->setAccessible(true);

        $pathParser = $this->getMockBuilder(Path::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'explodeValue' ])
            ->getMock();
        $pathParser->expects($this->never())
            ->method('explodeValue');

        $reflectedRequest->setValue($pathParser, $mockRequest);
        $reflectedParameter->setValue($pathParser, $mockParameter);
        $reflectedRoute->setValue($pathParser, $mockRoute);

        $result = $pathParser->getValue();

        $this->assertEquals($expectedValue, $result);
    }

    public function testGetValueReturnsMultipleValuesIfMatched()
    {
        $expectedValue = [
            '1234',
            '5678',
        ];

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/path/' . implode(',', $expectedValue));

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $mockParameter = [
            'name' => 'id',
            'type' => 'array',
        ];
        $mockRoute = '/path/{id}';

        $reflectedPathParser = new ReflectionClass(Path::class);
        $reflectedRequest = $reflectedPathParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedPathParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);
        $reflectedRoute = $reflectedPathParser->getProperty('route');
        $reflectedRoute->setAccessible(true);

        $pathParser = $this->getMockBuilder(Path::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'explodeValue' ])
            ->getMock();
        $pathParser->expects($this->once())
            ->method('explodeValue')
            ->with(implode(',', $expectedValue))
            ->willReturn($expectedValue);

        $reflectedRequest->setValue($pathParser, $mockRequest);
        $reflectedParameter->setValue($pathParser, $mockParameter);
        $reflectedRoute->setValue($pathParser, $mockRoute);

        $result = $pathParser->getValue();

        $this->assertEquals($expectedValue, $result);
    }
}
