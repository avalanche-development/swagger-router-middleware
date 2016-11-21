<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use ReflectionClass;

class FormTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsParserInterface()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [];

        $formParser = new Form($mockRequest, $mockParameter);

        $this->isInstanceOf(ParserInterface::class, $formParser);
    }

    public function testConstructSetsRequest()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [];

        $formParser = new Form($mockRequest, $mockParameter);

        $this->assertAttributeSame($mockRequest, 'request', $formParser);
    }

    public function testConstructSetsParameter()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockParameter = [ 'some value' ];

        $formParser = new Form($mockRequest, $mockParameter);

        $this->assertAttributeSame($mockParameter, 'parameter', $formParser);
    }

    public function testGetValueReturnsNullIfUnmatchedFile()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getUploadedFiles')
            ->willReturn([]);
        $mockRequest->expects($this->never())
            ->method('getParsedBody');

        $mockParameter = [
            'name' => 'some_variable',
            'type' => 'file',
        ];

        $reflectedFormParser = new ReflectionClass(Form::class);
        $reflectedRequest = $reflectedFormParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedFormParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $formParser = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedRequest->setValue($formParser, $mockRequest);
        $reflectedParameter->setValue($formParser, $mockParameter);

        $result = $formParser->getValue();

        $this->assertNull($result);
    }

    public function testGetValueReturnsFileIfMatched()
    {
        $expectedValue = $this->createMock(UploadedFileInterface::class);

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getUploadedFiles')
            ->willReturn([
                'some_variable' => $expectedValue,
            ]);
        $mockRequest->expects($this->never())
            ->method('getParsedBody');

        $mockParameter = [
            'name' => 'some_variable',
            'type' => 'file',
        ];

        $reflectedFormParser = new ReflectionClass(Form::class);
        $reflectedRequest = $reflectedFormParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedFormParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $formParser = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedRequest->setValue($formParser, $mockRequest);
        $reflectedParameter->setValue($formParser, $mockParameter);

        $result = $formParser->getValue();

        $this->assertSame($expectedValue, $result);
   }

    public function testGetValueReturnsNullIfUnmatched()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->never())
            ->method('getUploadedFiles');
        $mockRequest->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([]);

        $mockParameter = [
            'name' => 'some_variable',
            'type' => 'string',
        ];

        $reflectedFormParser = new ReflectionClass(Form::class);
        $reflectedRequest = $reflectedFormParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedFormParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $formParser = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'explodeValue',
            ])
            ->getMock();
        $formParser->expects($this->never())
            ->method('explodeValue');

        $reflectedRequest->setValue($formParser, $mockRequest);
        $reflectedParameter->setValue($formParser, $mockParameter);

        $result = $formParser->getValue();

        $this->assertNull($result);
    }

    public function testGetValueReturnsSingleValueIfMatched()
    {
        $expectedValue = 'value';

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'some_variable' => $expectedValue,
            ]);

        $mockParameter = [
            'name' => 'some_variable',
            'type' => 'string',
        ];

        $reflectedFormParser = new ReflectionClass(Form::class);
        $reflectedRequest = $reflectedFormParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedFormParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $formParser = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'explodeValue',
            ])
            ->getMock();
        $formParser->expects($this->never())
            ->method('explodeValue');

        $reflectedRequest->setValue($formParser, $mockRequest);
        $reflectedParameter->setValue($formParser, $mockParameter);

        $result = $formParser->getValue();

        $this->assertEquals($expectedValue, $result);
    }

    public function testGetValueReturnsMultipleValuesIfMatched()
    {
        $expectedValue = [
            'first_value',
            'second_value',
            'third_value',
        ];

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'some_variable' => $expectedValue,
            ]);

        $mockParameter = [
            'name' => 'some_variable',
            'type' => 'array',
        ];

        $reflectedFormParser = new ReflectionClass(Form::class);
        $reflectedRequest = $reflectedFormParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedFormParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $formParser = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'explodeValue',
            ])
            ->getMock();
        $formParser->expects($this->once())
            ->method('explodeValue')
            ->with($expectedValue)
            ->willReturn($expectedValue);

        $reflectedRequest->setValue($formParser, $mockRequest);
        $reflectedParameter->setValue($formParser, $mockParameter);

        $result = $formParser->getValue();

        $this->assertEquals($expectedValue, $result);
    }

    public function testGetValueReturnsMultipleValuesIfMatchedAndMulti()
    {
        $expectedValue = [
            'first_value',
        ];

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'some_variable' => current($expectedValue),
            ]);

        $mockParameter = [
            'name' => 'some_variable',
            'type' => 'array',
            'collectionFormat' => 'multi',
        ];

        $reflectedFormParser = new ReflectionClass(Form::class);
        $reflectedRequest = $reflectedFormParser->getProperty('request');
        $reflectedRequest->setAccessible(true);
        $reflectedParameter = $reflectedFormParser->getProperty('parameter');
        $reflectedParameter->setAccessible(true);

        $formParser = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'explodeValue',
            ])
            ->getMock();
        $formParser->expects($this->never())
            ->method('explodeValue');

        $reflectedRequest->setValue($formParser, $mockRequest);
        $reflectedParameter->setValue($formParser, $mockParameter);

        $result = $formParser->getValue();

        $this->assertEquals($expectedValue, $result);
    }
}
