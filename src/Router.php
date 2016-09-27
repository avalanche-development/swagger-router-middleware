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
            if (!$this->matchPath($request, $route, $pathItem)) {
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
     * @param array $pathItem
     * @response boolean
     */
    protected function matchPath(RequestInterface $request, $route, array $pathItem)
    {
        if ($request->getUri()->getPath() === $route) {
            return true;
        }

        // todo what are acceptable path param values, anyways?
        $isVariablePath = preg_match_all('/{([a-z_]+)}/', $route, $pathMatches);
        if (!$isVariablePath) {
            return false;
        }

        // loop da loop
        // todo feels weird that we pull operation out here and then do it again later
        // todo this is borked
        $method = strtolower($request->getMethod());
        $operation = $pathItem[$method]; // todo invalid operations?
        foreach ($pathMatches[1] as $pathParam) {
            foreach ($operation['parameters'] as $parameter) {
                if ($pathParam == $parameter['name']) {
                    if ($parameter['type'] == 'string') {
                        $pathKey = str_replace(
                            '{' . $pathParam . '}',
                            '(?P<' . $pathParam . '>\w+)',
                            $route
                        );
                        continue 2;
                    }
                }
            }
            return false;
        }

        $matchedVariablePath = preg_match(
            '@' . $pathKey . '@',
            $request->getUri()->getPath(),
            $pathMatches
        );
        if (!$matchedVariablePath) {
            return false;
        }

        $pathMatches = array_filter($pathMatches, function ($key) {
            return !is_numeric($key);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($pathMatches as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        return $request;
    }
}
