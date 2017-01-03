<?php

/**
 * Middleware router based on swagger definition
 * Accepts an incoming request object and passes on a decorated request object
 * Request object will have attributes filled out w/ route information
 * Also, reserved 'swagger' attribute with the operation info from swagger
 * Throws exception for unmatched request
 */

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use AvalancheDevelopment\Peel\HttpError\MethodNotAllowed;
use AvalancheDevelopment\Peel\HttpError\NotFound;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
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
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return ServerRequest
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $this->log('start');

        if ($this->isDocumentationRoute($request)) {
            $this->log('documentation route - early response');

            $swaggerDoc = json_encode($this->swagger);
            if ($swaggerDoc === false || json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid swagger - could not decode');
            }

            $response = $response->withStatus(200);
            $response = $response->withHeader('Content-type', 'application/json');
            $response->getBody()->write($swaggerDoc);
            return $response;
        }

        $matchedPath = false;
        foreach ($this->swagger['paths'] as $route => $pathItem) {
            if ($this->matchPath($request, $route)) {
                $matchedPath = true;
                break;
            }
        }
        if (!$matchedPath) {
            $this->log('no match found, exiting with NotFound exception');
            throw new NotFound('No match found in swagger docs');
        }

        $pathItem = $this->resolveRefs($pathItem);

        $method = strtolower($request->getMethod());
        if (!array_key_exists($method, $pathItem)) {
            $this->log('no method found for path, exiting with MethodNotAllowed exception');
            throw new MethodNotAllowed('No method found for this route');
        }

        $this->log("request matched with {$route}");
        $operation = $pathItem[$method];

        $parameters = $this->getParameters($pathItem, $operation);
        $parameters = $this->hydrateParameterValues(new ParameterParser, $request, $parameters, $route);
        $security = $this->getSecurity($operation);
        $schemes = $this->getSchemes($operation);
        $produces = $this->getProduces($operation);
        $consumes = $this->getConsumes($operation);
        $responses = $this->getResponses($operation);

        $request = $request->withAttribute('swagger', [
            'apiPath' => $route,
            'path' => $pathItem,
            'operation' => $operation,
            'params' => $parameters,
            'security' => $security,
            'schemes' => $schemes,
            'produces' => $produces,
            'consumes' => $consumes,
            'responses' => $responses,
        ]);

        $this->log('finished');
        return $next($request, $response);
    }

    /**
     * @param Request $request
     * @return boolean
     */
    protected function isDocumentationRoute(Request $request)
    {
        return (
            $request->getMethod() === 'GET' &&
            $request->getUri()->getPath() === '/api-docs'
        );
    }

    /**
     * @param Request $request
     * @param string $route
     * @return boolean
     */
    protected function matchPath(Request $request, $route)
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
     * @param array $chunk
     * @return array
     */
    protected function resolveRefs(array $chunk)
    {
        $resolvedChunk = [];
        foreach ($chunk as $key => $value) {
            if ($key === '$ref') {
                $reference = $this->lookupReference($value);
                $reference = $this->resolveRefs($reference);
                $resolvedChunk = array_merge($resolvedChunk, $reference);
                continue;
            }
            if (is_array($value)) {
                $resolvedChunk[$key] = $this->resolveRefs($value);
                continue;
            }
            $resolvedChunk[$key] = $value;
        }
        return $resolvedChunk;
    }

    /**
     * @param string $reference
     * @return mixed
     */
    protected function lookupReference($reference)
    {
        if (substr($reference, 0, 2) !== '#/') {
            throw new \Exception('invalid json reference found in swagger');
        }

        $reference = substr($reference, 2);
        $reference = explode('/', $reference);

        $referencedObject = $this->swagger;
        foreach ($reference as $referencePiece) {
            if (!array_key_exists($referencePiece, $referencedObject)) {
                throw new \Exception('reference not found in swagger');
            }
            $referencedObject = $referencedObject[$referencePiece];
        }
        return $referencedObject;
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
                $uniqueParameters[$parameter['name']] = $parameter;
            }
        }
        if (array_key_exists('parameters', $operation)) {
            foreach ($operation['parameters'] as $parameter) {
                $uniqueParameters[$parameter['name']] = $parameter;
            }
        }

        return array_values($uniqueParameters);
    }

    /**
     * @param ParameterParser $parser
     * @param Request $request
     * @param array $parameters
     * @param string $route
     * @return array
     */
    protected function hydrateParameterValues(
        ParameterParser $parser,
        Request $request,
        array $parameters,
        $route
    ) {
        $hydratedParameters = [];
        foreach ($parameters as $parameter) {
            $parameter['value'] = $parser($request, $parameter, $route);
            $hydratedParameters[$parameter['name']] = $parameter;
        }

        return $hydratedParameters;
    }

    /**
     * @param array $operation
     * @return array
     */
    protected function getSecurity(array $operation)
    {
        $securityRequirement = [];

        if (isset($operation['security'])) {
            $securityRequirement = $operation['security'];
        } elseif (isset($this->swagger['security'])) {
            $securityRequirement = $this->swagger['security'];
        }

        if (empty($securityRequirement)) {
            return [];
        }

        if (!array_key_exists('securityDefinitions', $this->swagger)) {
            throw new \Exception('No security schemes defined');
        }

        $security = [];
        foreach ($securityRequirement as $requirement) {
            $scheme = key($requirement);
            $scopes = current($requirement);
            if (!array_key_exists($scheme, $this->swagger['securityDefinitions'])) {
                throw new \Exception('Security scheme is not defined');
            }
            $security[$scheme] = $this->swagger['securityDefinitions'][$scheme];
            // todo this should only be oauth, plus should validate against defined scopes
            if (!empty($scopes)) {
                $security[$scheme]['operationScopes'] = $scopes;
            }
        }
        return $security;
    }

    /**
     * @param array $operation
     * @return array
     */
    protected function getSchemes(array $operation)
    {
        $schemes = [];

        if (array_key_exists('schemes', $operation)) {
            $schemes = $operation['schemes'];
        } elseif (isset($this->swagger['schemes'])) {
            $schemes = $this->swagger['schemes'];
        }

        // todo else pull from inbound request
        return $schemes;
    }

    /**
     * @param array $operation
     * @return array
     */
    protected function getProduces(array $operation)
    {
        $produces = [];

        if (array_key_exists('produces', $operation)) {
            $produces = $operation['produces'];
        } elseif (isset($this->swagger['produces'])) {
            $produces = $this->swagger['produces'];
        }

        return $produces;
    }

    /**
     * @param array $operation
     * @return array
     */
    protected function getConsumes(array $operation)
    {
        $consumes = [];

        if (array_key_exists('consumes', $operation)) {
            $consumes = $operation['consumes'];
        } elseif (isset($this->swagger['consumes'])) {
            $consumes = $this->swagger['consumes'];
        }

        return $consumes;
    }

    /**
     * @param array $operation
     * @return array
     */
    protected function getResponses(array $operation)
    {
        $responses = [];

        if (array_key_exists('responses', $operation)) {
            $responses = $operation['responses'];
        }

        return $responses;
    }

    /**
     * @param string $message
     */
    protected function log($message)
    {
        $this->logger->debug("swagger-router-middleware: {$message}");
    }
}
