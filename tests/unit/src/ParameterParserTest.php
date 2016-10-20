<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use DateTime;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use ReflectionClass;

class ParameterParserTest extends PHPUnit_Framework_TestCase
{

    public function testInvokeHandlesQueryParameter()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $parameter = [ 'in' => 'query' ];
        $route = '/some-route';
        $value = 'some value';

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getQueryValue', 'castType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getQueryValue')
            ->with($mockRequest, $parameter)
            ->willReturn($value);
        $parameterParser->expects($this->once())
            ->method('castType')
            ->with($value, $parameter)
            ->will($this->returnArgument(0));

        $result = $parameterParser($mockRequest, $parameter, $route);

        $this->assertEquals($value, $result);
    }

    public function testInvokeHandlesHeaderParameter()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $parameter = [ 'in' => 'header' ];
        $route = '/some-route';
        $value = 'some value';

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getHeaderValue', 'castType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getHeaderValue')
            ->with($mockRequest, $parameter)
            ->willReturn($value);
        $parameterParser->expects($this->once())
            ->method('castType')
            ->with($value, $parameter)
            ->will($this->returnArgument(0));

        $result = $parameterParser($mockRequest, $parameter, $route);

        $this->assertEquals($value, $result);
    }

    public function testInvokeHandlesPathParameter()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $parameter = [ 'in' => 'path' ];
        $route = '/some-route';
        $value = 'some value';

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getPathValue', 'castType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getPathValue')
            ->with($mockRequest, $parameter, $route)
            ->willReturn($value);
        $parameterParser->expects($this->once())
            ->method('castType')
            ->with($value, $parameter)
            ->will($this->returnArgument(0));

        $result = $parameterParser($mockRequest, $parameter, $route);

        $this->assertEquals($value, $result);
    }

    public function testInvokeHandlesFormParameter()
    {
        $this->markTestIncomplete('not yet implemented');
    }

    public function testInvokeHandlesBodyParameter()
    {
        $this->markTestIncomplete('not yet implemented');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage invalid parameter type
     */
    public function testInvokeBailsOnInvalidParameterType()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $parameter = [ 'in' => 'some type' ];
        $route = '/some-route';

        $parameterParser = new ParameterParser;
        $parameterParser($mockRequest, $parameter, $route);
    }

    public function testInvokeReturnsDefaultValue()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $parameter = [
            'in' => 'path',
            'default' => 'some default value',
        ];
        $route = '/some-route';

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getPathValue', 'castType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getPathValue')
            ->with($mockRequest, $parameter, $route);
        $parameterParser->expects($this->once())
            ->method('castType')
            ->with($parameter['default'], $parameter)
            ->will($this->returnArgument(0));

        $result = $parameterParser($mockRequest, $parameter, $route);

        $this->assertEquals($parameter['default'], $result);
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
            [ 'name' => 'other_variable' ],
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
            [
                'name' => 'some_variable',
                'type' => 'string',
            ],
        ]);

        $this->assertEquals('value', $result);
    }

    public function testGetQueryValueReturnsExplodedValueIfMatched()
    {
        $parameter = [
            'name' => 'some_variable',
            'type' => 'array',
        ];

        $value = [
            'some-value',
            'some-other-value',
        ];

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getQuery')
            ->willReturn('some_variable=value');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetQueryValue = $reflectedParameterParser->getMethod('getQueryValue');
        $reflectedGetQueryValue->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'explodeValue' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('explodeValue')
            ->with('value', $parameter)
            ->willReturn($value);

        $result = $reflectedGetQueryValue->invokeArgs($parameterParser, [
            $mockRequest,
            $parameter,
        ]);

        $this->assertEquals($value, $result);
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
            [ 'name' => 'Other-Header' ],
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
            [
                'name' => 'Some-Header',
                'type' => 'string',
            ],
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
            [
                'name' => 'Some-Header',
                'type' => 'array',
            ],
        ]);

        $this->assertEquals($headerValue, $result);
    }

    public function testGetPathValueReturnsNullIfUnmatched()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/path');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetPathValue = $reflectedParameterParser->getMethod('getPathValue');
        $reflectedGetPathValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetPathValue->invokeArgs($parameterParser, [
            $mockRequest,
            [ 'name' => 'id' ],
            '/path/{id}',
        ]);

        $this->assertNull($result);
    }

    public function testGetPathValueReturnsValueIfMatched()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/path/1234');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetPathValue = $reflectedParameterParser->getMethod('getPathValue');
        $reflectedGetPathValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetPathValue->invokeArgs($parameterParser, [
            $mockRequest,
            [
                'name' => 'id',
                'type' => 'string',
            ],
            '/path/{id}',
        ]);

        $this->assertEquals('1234', $result);
    }

    public function testGetPathValueReturnsExplodedValueIfMatched()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/path/1234,5678');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetPathValue = $reflectedParameterParser->getMethod('getPathValue');
        $reflectedGetPathValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetPathValue->invokeArgs($parameterParser, [
            $mockRequest,
            [
                'name' => 'id',
                'type' => 'array',
            ],
            '/path/{id}',
        ]);

        $this->assertEquals([
            '1234',
            '5678',
        ], $result);
    }

    public function testExplodeValue()
    {
        $parameter = [
            'collectionFormat' => 'csv',
        ];

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedExplodeValue = $reflectedParameterParser->getMethod('explodeValue');
        $reflectedExplodeValue->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getDelimiter' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getDelimiter')
            ->with($parameter)
            ->willReturn(',');

        $result = $reflectedExplodeValue->invokeArgs(
            $parameterParser,
            [
                'value1,value2',
                $parameter,
            ]
        );

        $this->assertEquals([
            'value1',
            'value2',
        ], $result);
    }

    public function testGetDelimiterHandlesCsv()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetDelimiter->invokeArgs(
            $parameterParser,
            [[ 'collectionFormat' => 'csv' ]]
        );

        $this->assertEquals(',', $result);
    }

    public function testGetDelimiterHandlesSsv()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetDelimiter->invokeArgs(
            $parameterParser,
            [[ 'collectionFormat' => 'ssv' ]]
        );

        $this->assertEquals('\s', $result);
    }

    public function testGetDelimiterHandlesTsv()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetDelimiter->invokeArgs(
            $parameterParser,
            [[ 'collectionFormat' => 'tsv' ]]
        );

        $this->assertEquals('\t', $result);
    }

    public function testGetDelimiterHandlesPipes()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetDelimiter->invokeArgs(
            $parameterParser,
            [[ 'collectionFormat' => 'pipes' ]]
        );

        $this->assertEquals('|', $result);
    }

    public function testGetDelimiterHandlesMulti()
    {
        $this->markTestIncomplete('Still not sure how to handle multi');
    }

    public function testGetDelimiterDefaultsToCsv()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetDelimiter->invokeArgs($parameterParser, [[]]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage invalid collection format value
     */
    public function testGetDelimiterReturnsCsvForUnknowns()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $reflectedGetDelimiter->invokeArgs(
            $parameterParser,
            [[ 'collectionFormat' => 'invalid' ]]
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage array items are not defined 
     */
    public function testCastTypeBailsWhenArrayHasNoItems()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = new ParameterParser;
        $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                '',
                [ 'type' => 'array' ],
            ]
        );
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerRouterMiddleware\Exception\BadRequest
     */ 
    public function testCaseTypeBailsWhenArrayValueIsBad()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = new ParameterParser;
        $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                '',
                [
                    'type' => 'array',
                    'items' => [],
                ],
            ]
        );
    }

    public function testCaseTypeHandlesArray()
    {
        $value = [
            123,
            456,
        ];
        $expectedValue = array_map(function ($row) {
            return (string) $row;
        }, $value);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                $value,
                [
                    'type' => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
            ]
        );

        $this->assertSame($expectedValue, $result);
    }

    public function testCastTypeHandlesBoolean()
    {
        $value = 'false';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'type' => 'boolean' ],
            ]
        );

        $this->assertSame((boolean) $value, $result);
    }

    public function testCastTypeHandlesFile()
    {
        $this->markTestIncomplete('not yet implemented');
    }

    public function testCastTypeHandlesInteger()
    {
        $value = '245';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'type' => 'integer' ],
            ]
        );

        $this->assertSame((int) $value, $result);
    }

    public function testCastTypeHandlesNumber()
    {
        $value = '3.141592';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'type' => 'number' ],
            ]
        );

        $this->assertSame((float) $value, $result);
    }

    public function testCastTypeHandlesString()
    {
        $value = 1337;
        $parameter = [
            'type' => 'string',
        ];

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'formatString' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('formatString')
            ->with($value, $parameter)
            ->will($this->returnArgument(0));

        $result = $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                $value,
                $parameter,
            ]
        );

        $this->assertSame((string) $value, $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage invalid parameter type value
     */
    public function testCastTypeBailsOnUnknownType()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = new ParameterParser;
        $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                '',
                [ 'type' => 'invalid' ],
            ]
        );
    }

    public function testFormatStringIgnoresFormatlessParameter()
    {
        $value = 'some string';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatString = $reflectedParameterParser->getMethod('formatString');
        $reflectedFormatString->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedFormatString->invokeArgs(
            $parameterParser,
            [
                $value,
                []
            ]
        );

        $this->assertSame($value, $result);
    }

    public function testFormatStringHandlesDate()
    {
        $value = '2016-10-18';
        $expectedValue = DateTime::createFromFormat('Y-m-d', $value);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatString = $reflectedParameterParser->getMethod('formatString');
        $reflectedFormatString->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedFormatString->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'format' => 'date' ],
            ]
        );

        $this->assertEquals($expectedValue, $result);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerRouterMiddleware\Exception\BadRequest
     */
    public function testFormatStringHandlesDateFailures()
    {
        $value = 'invalid date';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatString = $reflectedParameterParser->getMethod('formatString');
        $reflectedFormatString->setAccessible(true);

        $parameterParser = new ParameterParser;
        $reflectedFormatString->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'format' => 'date' ],
            ]
        );
    }

    public function testFormatStringHandlesDateTime()
    {
        $value = '2016-10-18T+07:00';
        $expectedValue = new DateTime($value);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatString = $reflectedParameterParser->getMethod('formatString');
        $reflectedFormatString->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedFormatString->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'format' => 'date-time' ],
            ]
        );

        $this->assertEquals($expectedValue, $result);
    }

    /**
     * @expectedException AvalancheDevelopment\SwaggerRouterMiddleware\Exception\BadRequest
     */
    public function testFormatStringHandlesDateTimeFailures()
    {
        $value = 'invalid date';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatString = $reflectedParameterParser->getMethod('formatString');
        $reflectedFormatString->setAccessible(true);

        $parameterParser = new ParameterParser;
        $reflectedFormatString->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'format' => 'date-time' ],
            ]
        );
    }

    public function testFormatStringIgnoresOnUnmatchedFormat()
    {
        $value = 'some value';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatString = $reflectedParameterParser->getMethod('formatString');
        $reflectedFormatString->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedFormatString->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'format' => 'random' ],
            ]
        );

        $this->assertSame($value, $result);
    }
}
