<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;

class BodyTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsParserInterface()
    {
        $mockRequest = $this->createMock(RequestInterface::class);

        $bodyParser = new Body($mockRequest);

        $this->isInstanceOf(ParserInterface::class, $bodyParser);
    }

    public function testConstructSetsRequest()
    {
        $mockRequest = $this->createMock(RequestInterface::class);

        $bodyParser = new Body($mockRequest);

        $this->assertAttributeSame($mockRequest, 'request', $bodyParser);
    }

    public function testGetValueReturnsNullIfNoBody()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getBody')
            ->willReturn('');
        $mockRequest->expects($this->never())
            ->method('getHeader');

        $reflectedBodyParser = new ReflectionClass(Body::class);
        $reflectedRequest = $reflectedBodyParser->getProperty('request');
        $reflectedRequest->setAccessible(true);

        $bodyParser = $this->getMockBuilder(Body::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkJsonHeader',
                'parseJson',
            ])
            ->getMock();
        $bodyParser->expects($this->never())
            ->method('checkJsonHeader');
        $bodyParser->expects($this->never())
            ->method('parseJson');

        $reflectedRequest->setValue($bodyParser, $mockRequest);

        $result = $bodyParser->getValue();

        $this->assertNull($result);
    }

    public function testGetValueChecksForJsonHeader()
    {
        $mockHeader = [
            'some header',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getBody')
            ->willReturn('some string');
        $mockRequest->method('getHeader')
            ->with('Content-Type')
            ->willReturn($mockHeader);

        $reflectedBodyParser = new ReflectionClass(Body::class);
        $reflectedRequest = $reflectedBodyParser->getProperty('request');
        $reflectedRequest->setAccessible(true);

        $bodyParser = $this->getMockBuilder(Body::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkJsonHeader',
                'parseJson',
            ])
            ->getMock();
        $bodyParser->expects($this->once())
            ->method('checkJsonHeader')
            ->with($mockHeader[0]);

        $reflectedRequest->setValue($bodyParser, $mockRequest);

        $bodyParser->getValue();
    }

    public function testGetValueParsesJsonIfJsonHeaderFound()
    {
        $mockBody = 'some body';
        $parsedBody = 'some parsed body';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getBody')
            ->willReturn($mockBody);
        $mockRequest->method('getHeader')
            ->with('Content-Type')
            ->willReturn([
                'some header',
            ]);

        $reflectedBodyParser = new ReflectionClass(Body::class);
        $reflectedRequest = $reflectedBodyParser->getProperty('request');
        $reflectedRequest->setAccessible(true);

        $bodyParser = $this->getMockBuilder(Body::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkJsonHeader',
                'parseJson',
            ])
            ->getMock();
        $bodyParser->method('checkJsonHeader')
            ->willReturn(true);
        $bodyParser->expects($this->once())
            ->method('parseJson')
            ->with($mockBody)
            ->willReturn($parsedBody);

        $reflectedRequest->setValue($bodyParser, $mockRequest);

        $result = $bodyParser->getValue();

        $this->assertSame($parsedBody, $result);
    }

    public function testGetValueReturnsStringIfNoJsonHeaderFound()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getBody')
            ->willReturn(123);
        $mockRequest->method('getHeader')
            ->with('Content-Type')
            ->willReturn([
                'some header',
            ]);

        $reflectedBodyParser = new ReflectionClass(Body::class);
        $reflectedRequest = $reflectedBodyParser->getProperty('request');
        $reflectedRequest->setAccessible(true);

        $bodyParser = $this->getMockBuilder(Body::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkJsonHeader',
                'parseJson',
            ])
            ->getMock();
        $bodyParser->method('checkJsonHeader')
            ->willReturn(false);
        $bodyParser->expects($this->never())
            ->method('parseJson');

        $reflectedRequest->setValue($bodyParser, $mockRequest);

        $result = $bodyParser->getValue();

        $this->assertSame('123', $result);
    }

    public function testCheckJsonHeaderReturnsTrueIfJsonHeaderFound()
    {
        $contentType = 'application/json';

        $reflectedBodyParser = new ReflectionClass(Body::class);
        $reflectedCheckJsonHeader = $reflectedBodyParser->getMethod('checkJsonHeader');
        $reflectedCheckJsonHeader->setAccessible(true);

        $bodyParser = $this->getMockBuilder(Body::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckJsonHeader->invokeArgs($bodyParser, [ $contentType ]);

        $this->assertTrue($result);
    }

    public function testCheckJsonHeaderReturnsFalseIfNoJsonHeadersFound()
    {
        $contentType = 'application/xml';

        $reflectedBodyParser = new ReflectionClass(Body::class);
        $reflectedCheckJsonHeader = $reflectedBodyParser->getMethod('checkJsonHeader');
        $reflectedCheckJsonHeader->setAccessible(true);

        $bodyParser = $this->getMockBuilder(Body::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedCheckJsonHeader->invokeArgs($bodyParser, [ $contentType ]);

        $this->assertFalse($result);
    }

    public function testParseJsonReturnsNullIfInvalidJson()
    {
        $body = 'some string';

        $reflectedBodyParser = new ReflectionClass(Body::class);
        $reflectedParseJson = $reflectedBodyParser->getMethod('parseJson');
        $reflectedParseJson->setAccessible(true);

        $bodyParser = $this->getMockBuilder(Body::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedParseJson->invokeArgs($bodyParser, [ $body ]);

        $this->assertNull($result);
    }

    public function testParseJsonReturnsJsonIfValidJson()
    {
        $body = '{"key":"some value"}';

        $parsedBody = [
            'key' => 'some value',
        ];

        $reflectedBodyParser = new ReflectionClass(Body::class);
        $reflectedParseJson = $reflectedBodyParser->getMethod('parseJson');
        $reflectedParseJson->setAccessible(true);

        $bodyParser = $this->getMockBuilder(Body::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $reflectedParseJson->invokeArgs($bodyParser, [ $body ]);

        $this->assertSame($parsedBody, $result);
    }
}
