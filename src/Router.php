<?php

/**
 * Router based on swagger definition
 * Accepts an incoming request object and returns decorated request object
 * Request object will have attributes filled out w/ route information
 * Also, reserved 'swagger' attribute with the operation info from swagger
 * Throws exception for unmatched request
 */

namespace AvalancheDevelopment\Route;

use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Router implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    /** @var array $swagger */
    protected $swagger;

    /**
     * @param array $swagger
     */
    public function __construct(array $swagger)
    {
        $this->swagger = $swagger;
    }

    /**
     * @param RequestInterface $request
     * @return RequestInterface
     */
    public function __invoke(RequestInterface $request)
    {
        $matchedPath = null;
        foreach ($this->swagger['paths'] as $route => $pathItem) {
            if (!$this->matchPath($request, $route)) {
                continue;
            }
            $matchedPath = $pathItem;
            break;
        }
        if (!$matchedPath) {
            throw new Exception('Not Found');
        }

        $method = strtolower($request->getMethod());
        if (!array_key_exists($method, $pathItem)) {
            throw new Exception('Method not allowed');
        }
        $operation = $matchedPath[$method];

        // extract out path parameters and throw in withAttribute
        // returns request object
    }

    /**
     * @param RequestInterface $request
     * @param string $route
     * @response boolean
     */
    protected function matchPath(RequestInterface $request, $route)
    {
        // todo what are acceptable path param values, anyways?
        $isVariablePath = preg_match('/{[a-z_]+}/', $route);
        if (!$isVariablePath && $request->getUri()->getPath() === $route) {
            return true;
        }

        // todo how much do we care about strings vs integers, etc?
        $variablePath = preg_replace('/({[a-z_]+})/', '(\w+)', $route);
        $matchedVariablePath = preg_match($variablePath, $request->getUri()->getPath());
        if ($matchedVariablePath) {
            return true;
        }

        return false;
    }
}
