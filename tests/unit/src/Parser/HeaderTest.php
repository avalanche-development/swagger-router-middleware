<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;

class HeaderTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsParserInterface()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [];

        $headerParser = new Header($mockRequest, $mockParameter);

        $this->isInstanceOf(ParserInterface::class, $headerParser);
    }

    public function testConstructSetsRequest()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [];

        $headerParser = new Header($mockRequest, $mockParameter);

        $this->assertAttributeSame($mockRequest, 'request', $headerParser);
    }

    public function testConstructSetsParameter()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [ 'some value' ];

        $headerParser = new Header($mockRequest, $mockParameter);

        $this->assertAttributeSame($mockParameter, 'parameter', $headerParser);
    }

    public function testGetValueReturnsNullIfUnmatched()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getHeaders')
            ->willReturn([
                'Some-Header' => [
                    'value',
                ],
            ]);
        $mockParameter = [
            'name' => 'Other-Header',
        ];

        $reflectedHeaderParser = new ReflectionClass(Header::class);
        $reflectedRequest = $reflectedHeaderParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedHeaderParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedRequest->setValue($header, $mockRequest);
        $reflectedParameter->setValue($header, $mockParameter);

        $result = $header->getValue();

        $this->assertNull($result);
    }

    public function testGetValueReturnsSingleValueIfMatched()
    {
        $expectedValue = 'some_value';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getHeaders')
            ->willReturn([
                'Some-Header' => [
                    $expectedValue,
                ],
            ]);
        $mockParameter = [
            'name' => 'Some-Header',
            'type' => 'string',
        ];

        $reflectedHeaderParser = new ReflectionClass(Header::class);
        $reflectedRequest = $reflectedHeaderParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedHeaderParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedRequest->setValue($header, $mockRequest);
        $reflectedParameter->setValue($header, $mockParameter);

        $result = $header->getValue();

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
        $mockRequest->method('getHeaders')
            ->willReturn([
                'Some-Header' => $expectedValue,
            ]);
        $mockParameter = [
            'name' => 'Some-Header',
            'type' => 'array',
        ];

        $reflectedHeaderParser = new ReflectionClass(Header::class);
        $reflectedRequest = $reflectedHeaderParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedHeaderParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedRequest->setValue($header, $mockRequest);
        $reflectedParameter->setValue($header, $mockParameter);

        $result = $header->getValue();

        $this->assertEquals($expectedValue, $result);
    }
}
