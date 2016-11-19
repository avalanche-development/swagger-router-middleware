<?php

/**
 * the way PHPUnit_Framework_TestCase::getMockForTrait returns the mocked trait
 * requires the reflection to happen on the trait, no the original
 * this does not flow with the rest of the test convention and should be fixed
 * once the trait mocking logic is fixed
 */

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use PHPUnit_Framework_TestCase;
use ReflectionClass;

class ExplodeTraitTest extends PHPUnit_Framework_TestCase
{

    public function testExplodeValue()
    {
        $expectedValue = [
            'value1',
            'value2',
        ];

        $mockValue = 'value1,value2';
        $mockParameter = [
            'collectionFormat' => 'csv',
        ];

        $explodeTrait = $this->getMockForTrait(
            ExplodeTrait::class,
            [],
            '',
            true,
            true,
            true,
            [ 'getDelimiter' ]
        );

        $explodeTrait->expects($this->once())
            ->method('getDelimiter')
            ->with($mockParameter)
            ->willReturn(',');

        $reflectedExplodeTrait = new ReflectionClass($explodeTrait);
        $reflectedExplodeValue = $reflectedExplodeTrait->getMethod('explodeValue');
        $reflectedExplodeValue->setAccessible(true);

        $result = $reflectedExplodeValue->invokeArgs(
            $explodeTrait,
            [
                $mockValue,
                $mockParameter,
            ]
        );

        $this->assertEquals($expectedValue, $result);
    }

    public function testGetDelimiterDefaultsToCsv()
    {
        $expectedValue = ',';

        $mockParameter = [];
 
        $explodeTrait = $this->getMockForTrait(ExplodeTrait::class);

        $reflectedExplodeTrait = new ReflectionClass($explodeTrait);
        $reflectedGetDelimiter = $reflectedExplodeTrait->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $result = $reflectedGetDelimiter->invokeArgs(
            $explodeTrait,
            [ $mockParameter ]
        );

        $this->assertEquals($expectedValue, $result);
    }

    public function testGetDelimiterHandlesCsv()
    {
        $expectedValue = ',';

        $mockParameter = [
            'collectionFormat' => 'csv',
        ];

        $explodeTrait = $this->getMockForTrait(ExplodeTrait::class);

        $reflectedExplodeTrait = new ReflectionClass($explodeTrait);
        $reflectedGetDelimiter = $reflectedExplodeTrait->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $result = $reflectedGetDelimiter->invokeArgs(
            $explodeTrait,
            [ $mockParameter ]
        );

        $this->assertEquals($expectedValue, $result);
    }

    public function testGetDelimiterHandlesSsv()
    {
        $expectedValue = '\s';

        $mockParameter = [
            'collectionFormat' => 'ssv',
        ];

        $explodeTrait = $this->getMockForTrait(ExplodeTrait::class);

        $reflectedExplodeTrait = new ReflectionClass($explodeTrait);
        $reflectedGetDelimiter = $reflectedExplodeTrait->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $result = $reflectedGetDelimiter->invokeArgs(
            $explodeTrait,
            [ $mockParameter ]
        );

        $this->assertEquals($expectedValue, $result);
    }

    public function testGetDelimiterHandlesTsv()
    {
        $expectedValue = '\t';

        $mockParameter = [
            'collectionFormat' => 'tsv',
        ];

        $explodeTrait = $this->getMockForTrait(ExplodeTrait::class);

        $reflectedExplodeTrait = new ReflectionClass($explodeTrait);
        $reflectedGetDelimiter = $reflectedExplodeTrait->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $result = $reflectedGetDelimiter->invokeArgs(
            $explodeTrait,
            [ $mockParameter ]
        );

        $this->assertEquals($expectedValue, $result);
    }

    public function testGetDelimiterHandlesPipes()
    {
        $expectedValue = '|';

        $mockParameter = [
            'collectionFormat' => 'pipes',
        ];

        $explodeTrait = $this->getMockForTrait(ExplodeTrait::class);

        $reflectedExplodeTrait = new ReflectionClass($explodeTrait);
        $reflectedGetDelimiter = $reflectedExplodeTrait->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $result = $reflectedGetDelimiter->invokeArgs(
            $explodeTrait,
            [ $mockParameter ]
        );

        $this->assertEquals($expectedValue, $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid collection format value defined in swagger
     */
    public function testGetDelimiterBailsForUnknownCollectionFormats()
    {
        $mockParameter = [
            'collectionFormat' => 'invalid',
        ];

        $explodeTrait = $this->getMockForTrait(ExplodeTrait::class);

        $reflectedExplodeTrait = new ReflectionClass($explodeTrait);
        $reflectedGetDelimiter = $reflectedExplodeTrait->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $reflectedGetDelimiter->invokeArgs(
            $explodeTrait,
            [ $mockParameter ]
        );
    }
}
