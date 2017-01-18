<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use PHPUnit_Framework_TestCase;
use ReflectionClass;

class ParsedSwaggerTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsParsedSwaggerInterface()
    {
        $parsedSwagger = new ParsedSwagger;

        $this->assertInstanceOf(ParsedSwaggerInterface::class, $parsedSwagger);
    }

    public function testSetApiPathSetsApiPath()
    {
        $apiPath = 'some path';

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $parsedSwagger->setApiPath($apiPath);

        $this->assertAttributeEquals($apiPath, 'apiPath', $parsedSwagger);
    }

    public function testGetApiPathGetsApiPath()
    {
        $apiPath = 'some path';

        $reflectedParsedSwagger = new ReflectionClass(ParsedSwagger::class);
        $reflectedApiPath = $reflectedParsedSwagger->getProperty('apiPath');
        $reflectedApiPath->setAccessible(true);

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedApiPath->setValue($parsedSwagger, $apiPath);

        $result = $parsedSwagger->getApiPath();

        $this->assertEquals($apiPath, $result);
    }

    public function testGetApiPathGetsDefaultApiPath()
    {
        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $parsedSwagger->getApiPath();

        $this->assertEquals('', $result);
    }

    public function testSetPathSetsPath()
    {
        $path = [
            'some path'
        ];

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $parsedSwagger->setPath($path);

        $this->assertAttributeEquals($path, 'path', $parsedSwagger);
    }

    public function testGetPathGetsPath()
    {
        $path = [
            'some path'
        ];

        $reflectedParsedSwagger = new ReflectionClass(ParsedSwagger::class);
        $reflectedPath = $reflectedParsedSwagger->getProperty('path');
        $reflectedPath->setAccessible(true);

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedPath->setValue($parsedSwagger, $path);

        $result = $parsedSwagger->getPath();

        $this->assertEquals($path, $result);
    }

    public function testGetPathGetsDefaultPath()
    {
        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $parsedSwagger->getPath();

        $this->assertEquals([], $result);
    }

    public function testSetOperationSetsOperation()
    {
        $operation = [
            'some operation'
        ];

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $parsedSwagger->setOperation($operation);

        $this->assertAttributeEquals($operation, 'operation', $parsedSwagger);
    }

    public function testGetOperationGetsOperation()
    {
        $operation = [
            'some operation'
        ];

        $reflectedParsedSwagger = new ReflectionClass(ParsedSwagger::class);
        $reflectedOperation = $reflectedParsedSwagger->getProperty('operation');
        $reflectedOperation->setAccessible(true);

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedOperation->setValue($parsedSwagger, $operation);

        $result = $parsedSwagger->getOperation();

        $this->assertEquals($operation, $result);
    }

    public function testGetOperationGetsDefaultOperation()
    {
        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $parsedSwagger->getOperation();

        $this->assertEquals([], $result);
    }

    public function testSetParamsSetsParams()
    {
        $params = [
            'some params'
        ];

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $parsedSwagger->setParams($params);

        $this->assertAttributeEquals($params, 'params', $parsedSwagger);
    }

    public function testGetParamsGetsParams()
    {
        $params = [
            'some params'
        ];

        $reflectedParsedSwagger = new ReflectionClass(ParsedSwagger::class);
        $reflectedParams = $reflectedParsedSwagger->getProperty('params');
        $reflectedParams->setAccessible(true);

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedParams->setValue($parsedSwagger, $params);

        $result = $parsedSwagger->getParams();

        $this->assertEquals($params, $result);
    }

    public function testGetParamsGetsDefaultParams()
    {
        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $parsedSwagger->getParams();

        $this->assertEquals([], $result);
    }

    public function testSetSecuritySetsSecurity()
    {
        $security = [
            'some security'
        ];

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $parsedSwagger->setSecurity($security);

        $this->assertAttributeEquals($security, 'security', $parsedSwagger);
    }

    public function testGetSecurityGetsSecurity()
    {
        $security = [
            'some security'
        ];

        $reflectedParsedSwagger = new ReflectionClass(ParsedSwagger::class);
        $reflectedSecurity = $reflectedParsedSwagger->getProperty('security');
        $reflectedSecurity->setAccessible(true);

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedSecurity->setValue($parsedSwagger, $security);

        $result = $parsedSwagger->getSecurity();

        $this->assertEquals($security, $result);
    }

    public function testGetSecurityGetsDefaultSecurity()
    {
        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $parsedSwagger->getSecurity();

        $this->assertEquals([], $result);
    }

    public function testSetSchemesSetsSchemes()
    {
        $schemes = [
            'some schemes'
        ];

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $parsedSwagger->setSchemes($schemes);

        $this->assertAttributeEquals($schemes, 'schemes', $parsedSwagger);
    }

    public function testGetSchemesGetsSchemes()
    {
        $schemes = [
            'some schemes'
        ];

        $reflectedParsedSwagger = new ReflectionClass(ParsedSwagger::class);
        $reflectedSchemes = $reflectedParsedSwagger->getProperty('schemes');
        $reflectedSchemes->setAccessible(true);

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedSchemes->setValue($parsedSwagger, $schemes);

        $result = $parsedSwagger->getSchemes();

        $this->assertEquals($schemes, $result);
    }

    public function testGetSchemesGetsDefaultSchemes()
    {
        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $parsedSwagger->getSchemes();

        $this->assertEquals([], $result);
    }

    public function testSetProducesSetsProduces()
    {
        $produces = [
            'some produces'
        ];

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $parsedSwagger->setProduces($produces);

        $this->assertAttributeEquals($produces, 'produces', $parsedSwagger);
    }

    public function testGetProducesGetsProduces()
    {
        $produces = [
            'some produces'
        ];

        $reflectedParsedSwagger = new ReflectionClass(ParsedSwagger::class);
        $reflectedProduces = $reflectedParsedSwagger->getProperty('produces');
        $reflectedProduces->setAccessible(true);

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedProduces->setValue($parsedSwagger, $produces);

        $result = $parsedSwagger->getProduces();

        $this->assertEquals($produces, $result);
    }

    public function testGetProducesGetsDefaultProduces()
    {
        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $parsedSwagger->getProduces();

        $this->assertEquals([], $result);
    }

    public function testSetConsumesSetsConsumes()
    {
        $consumes = [
            'some consumes'
        ];

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $parsedSwagger->setConsumes($consumes);

        $this->assertAttributeEquals($consumes, 'consumes', $parsedSwagger);
    }

    public function testGetConsumesGetsConsumes()
    {
        $consumes = [
            'some consumes'
        ];

        $reflectedParsedSwagger = new ReflectionClass(ParsedSwagger::class);
        $reflectedConsumes = $reflectedParsedSwagger->getProperty('consumes');
        $reflectedConsumes->setAccessible(true);

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedConsumes->setValue($parsedSwagger, $consumes);

        $result = $parsedSwagger->getConsumes();

        $this->assertEquals($consumes, $result);
    }

    public function testGetConsumesGetsDefaultConsumes()
    {
        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $parsedSwagger->getConsumes();

        $this->assertEquals([], $result);
    }

    public function testSetResponsesSetsResponses()
    {
        $responses = [
            'some responses'
        ];

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $parsedSwagger->setResponses($responses);

        $this->assertAttributeEquals($responses, 'responses', $parsedSwagger);
    }

    public function testGetResponsesGetsResponses()
    {
        $responses = [
            'some responses'
        ];

        $reflectedParsedSwagger = new ReflectionClass(ParsedSwagger::class);
        $reflectedResponses = $reflectedParsedSwagger->getProperty('responses');
        $reflectedResponses->setAccessible(true);

        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedResponses->setValue($parsedSwagger, $responses);

        $result = $parsedSwagger->getResponses();

        $this->assertEquals($responses, $result);
    }

    public function testGetResponsesGetsDefaultResponses()
    {
        $parsedSwagger = $this->getMockBuilder(ParsedSwagger::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $parsedSwagger->getResponses();

        $this->assertEquals([], $result);
    }
}
