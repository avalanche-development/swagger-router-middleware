<?php

/**
 * Router based on swagger definition
 * Accepts an incoming request object and returns decorated request object
 * Request object will have attributes filled out w/ route information
 * Also, reserved 'swagger' attribute with the operation info from swagger
 * Throws exception for unmatched request
 */

namespace AvalancheDevelopment\SwaggerRouter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
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
        $this->logger = new NullLogger;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function __invoke(ServerRequestInterface $request)
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
            throw new Exception\NotFound;
        }

        $method = strtolower($request->getMethod());
        if (!array_key_exists($method, $pathItem)) {
            throw new Exception\MethodNotAllowed;
        }
        $operation = $matchedPath[$method];

        $parameters = $this->getParameters($matchedPath, $operation);

        $parser = new ParameterParser;
        $parameters = $this->hydrateParameterValues($parser, $request, $parameters, key($matchedPath));
        // todo security would be cool here too

        // todo not sold on this interface - may tweak it
        return $request->withAttribute('swagger', [
            'path' => $matchedPath,
            'operation' => $operation,
            'params' => $parameters,
        ]);
    }

    /**
     * @param RequestInterface $request
     * @param string $route
     * @response boolean
     */
    protected function matchPath(RequestInterface $request, $route)
    {
        $isVariablePath = strstr($route, '{') && strstr($route, '}');
        if (!$isVariablePath && $request->getUri()->getPath() === $route) {
            return true;
        }

        $variablePath = preg_replace('/({[a-z_]+})/', '\w+', $route);
        $variablePath = "@^{$variablePath}$@";
        $matchedVariablePath = preg_match($variablePath, $request->getUri()->getPath());
        if ($matchedVariablePath) {
            return true;
        }

        return false;
    }

    /**
     * @param array $pathItem
     * @param array $operation
     * @return array
     */
    protected function getParameters(array $pathItem, array $operation)
    {
        $uniqueParameters = [];
        if (array_key_exists('parameters', $pathItem)) {
            foreach ($pathItem['parameters'] as $parameter) {
                $key = $this->uniqueParameterKey($parameter);
                $uniqueParameters[$key] = $parameter;
            }
        }
        if (array_key_exists('parameters', $operation)) {
            foreach ($operation['parameters'] as $parameter) {
                $key = $this->uniqueParameterKey($parameter);
                $uniqueParameters[$key] = $parameter;
            }
        }

        return array_values($uniqueParameters);
    }

    /**
     * @param array $parameter
     * @return string
     */
    protected function uniqueParameterKey(array $parameter)
    {
        return "{$parameter['name']}-{$parameter['in']}";
    }

    /**
     * @param ParameterParser $parser
     * @param RequestInterface $request
     * @param array $parameters
     * @param string $route
     * @return array
     */
    protected function hydrateParameterValues(
        ParameterParser $parser,
        RequestInterface $request,
        array $parameters,
        $route
    ) {
        return array_map(function ($parameter) use ($parser, $request, $route) {
            $parameter['value'] = $parser($request, $parameter, $route);
            return $parameter;
        }, $parameters);
    }
}
