<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use ReflectionClass;

class QueryTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsParserInterface()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [];

        $queryParser = new Query($mockRequest, $mockParameter);

        $this->isInstanceOf(ParserInterface::class, $queryParser);
    }

    public function testConstructSetsRequest()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [];

        $queryParser = new Query($mockRequest, $mockParameter);

        $this->assertAttributeSame($mockRequest, 'request', $queryParser);
    }

    public function testConstructSetsParameter()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [ 'some value' ];

        $queryParser = new Query($mockRequest, $mockParameter);

        $this->assertAttributeSame($mockParameter, 'parameter', $queryParser);
    }

    public function testGetValueReturnsNullIfUnmatched()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [
            'name' => 'some_variable',
        ];

        $reflectedQueryParser = new ReflectionClass(Query::class);
        $reflectedRequest = $reflectedQueryParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedQueryParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $queryParser = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'explodeValue',
                'parseQueryString',
            ])
            ->getMock();
        $queryParser->expects($this->never())
            ->method('explodeValue');
        $queryParser->expects($this->once())
            ->method('parseQueryString')
            ->with($mockRequest)
            ->willReturn([
                'other_variable' => 'value',
            ]);

        $reflectedRequest->setValue($queryParser, $mockRequest);
        $reflectedParameter->setValue($queryParser, $mockParameter);

        $result = $queryParser->getValue();

        $this->assertNull($result);
    }

    public function testGetValueReturnsSingleValueIfMatched()
    {
        $expectedValue = 'value';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [
            'name' => 'some_variable',
            'type' => 'string',
        ];

        $reflectedQueryParser = new ReflectionClass(Query::class);
        $reflectedRequest = $reflectedQueryParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedQueryParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $queryParser = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'explodeValue',
                'parseQueryString',
            ])
            ->getMock();
        $queryParser->expects($this->never())
            ->method('explodeValue');
        $queryParser->expects($this->once())
            ->method('parseQueryString')
            ->with($mockRequest)
            ->willReturn([
                'some_variable' => $expectedValue,
            ]);

        $reflectedRequest->setValue($queryParser, $mockRequest);
        $reflectedParameter->setValue($queryParser, $mockParameter);

        $result = $queryParser->getValue();

        $this->assertEquals($expectedValue, $result);
    }

    public function testGetValueReturnsMultipleValuesIfMatched()
    {
        $expectedValue = [
            'first_value',
            'second_value',
            'third_value',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [
            'name' => 'some_variable',
            'type' => 'array',
        ];

        $reflectedQueryParser = new ReflectionClass(Query::class);
        $reflectedRequest = $reflectedQueryParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedQueryParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $queryParser = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'explodeValue',
                'parseQueryString',
            ])
            ->getMock();
        $queryParser->expects($this->once())
            ->method('explodeValue')
            ->with(implode('|', $expectedValue))
            ->willReturn($expectedValue);
        $queryParser->expects($this->once())
            ->method('parseQueryString')
            ->with($mockRequest)
            ->willReturn([
                'some_variable' => implode('|', $expectedValue),
            ]);

        $reflectedRequest->setValue($queryParser, $mockRequest);
        $reflectedParameter->setValue($queryParser, $mockParameter);

        $result = $queryParser->getValue();

        $this->assertEquals($expectedValue, $result);
    }

    public function testGetValueReturnsMultipleValuesIfMatchedAndMulti()
    {
        $expectedValue = [
            'first_value',
            'second_value',
            'third_value',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [
            'name' => 'some_variable',
            'type' => 'array',
            'collectionFormat' => 'multi',
        ];

        $reflectedQueryParser = new ReflectionClass(Query::class);
        $reflectedRequest = $reflectedQueryParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedQueryParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $queryParser = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'explodeValue',
                'parseQueryString',
            ])
            ->getMock();
        $queryParser->expects($this->never())
            ->method('explodeValue');
        $queryParser->expects($this->once())
            ->method('parseQueryString')
            ->with($mockRequest)
            ->willReturn([
                'some_variable' => $expectedValue,
            ]);

        $reflectedRequest->setValue($queryParser, $mockRequest);
        $reflectedParameter->setValue($queryParser, $mockParameter);

        $result = $queryParser->getValue();

        $this->assertEquals($expectedValue, $result);
    }

    public function testParseQueryStringHandlesEmptyQuery()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getQuery')
            ->willReturn('');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedQueryParser = new ReflectionClass(Query::class);
        $reflectedParseQueryString = $reflectedQueryParser->getMethod('parseQueryString');
        $reflectedParseQueryString->setAccessible(true);

        $queryParser = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
  
        $result = $reflectedParseQueryString->invokeArgs(
            $queryParser,
            [ $mockRequest]
        );

        $this->assertSame([], $result);
    }

    public function testParseQueryStringHandlesStandardSyntax()
    {
        $expectedValue = [
            'id' => '1',
        ];

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getQuery')
            ->willReturn('id=1');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedQueryParser = new ReflectionClass(Query::class);
        $reflectedParseQueryString = $reflectedQueryParser->getMethod('parseQueryString');
        $reflectedParseQueryString->setAccessible(true);

        $queryParser = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
  
        $result = $reflectedParseQueryString->invokeArgs(
            $queryParser,
            [ $mockRequest]
        );

        $this->assertSame($expectedValue, $result);
    }

    public function testParseQueryStringHandlesMultiSyntax()
    {
        $expectedValue = [
            'id' => [
                '1',
                '2',
            ],
        ];

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getQuery')
            ->willReturn('id=1&id=2');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedQueryParser = new ReflectionClass(Query::class);
        $reflectedParseQueryString = $reflectedQueryParser->getMethod('parseQueryString');
        $reflectedParseQueryString->setAccessible(true);

        $queryParser = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
  
        $result = $reflectedParseQueryString->invokeArgs(
            $queryParser,
            [ $mockRequest]
        );

        $this->assertSame($expectedValue, $result);
    }

    public function testParseQueryStringHandlesArraySyntax()
    {
        $expectedValue = [
            'id' => [
                '1',
                '2',
            ],
        ];

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getQuery')
            ->willReturn('id[]=1&id[]=2');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedQueryParser = new ReflectionClass(Query::class);
        $reflectedParseQueryString = $reflectedQueryParser->getMethod('parseQueryString');
        $reflectedParseQueryString->setAccessible(true);

        $queryParser = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
  
        $result = $reflectedParseQueryString->invokeArgs(
            $queryParser,
            [ $mockRequest]
        );

        $this->assertSame($expectedValue, $result);
    }

    public function testParseQueryStringFlattensSingleArraySyntax()
    {
        $expectedValue = [
            'id' => '1',
        ];

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getQuery')
            ->willReturn('id[]=1');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedQueryParser = new ReflectionClass(Query::class);
        $reflectedParseQueryString = $reflectedQueryParser->getMethod('parseQueryString');
        $reflectedParseQueryString->setAccessible(true);

        $queryParser = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
  
        $result = $reflectedParseQueryString->invokeArgs(
            $queryParser,
            [ $mockRequest]
        );

        $this->assertSame($expectedValue, $result);
    }
}
