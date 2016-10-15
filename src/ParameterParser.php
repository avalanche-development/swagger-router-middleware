<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ParameterParser implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger;
    }

    /**
     * @param Request $request
     * @param array $parameter
     * @param string $route
     * @return mixed
     */
    public function __invoke(Request $request, array $parameter, $route)
    {
        switch ($parameter['in']) {
            case 'query':
                $value = $this->getQueryValue($request, $parameter);
                break;
            case 'header':
                $value = $this->getHeaderValue($request, $parameter);
                break;
            case 'path':
                $value = $this->getPathValue($request, $parameter, $route);
                break;
            case 'formData':
                throw new \Exception('not yet implemented');
                break;
            case 'body':
                throw new \Exception('not yet implemented');
                break;
            default:
                throw new \Exception('invalid parameter type');
                break;
        }

        if (!isset($value) && isset($parameter['default'])) {
            $value = $parameter['default'];
        }

        // todo cast into respective data types
        return $value;
    }

    /**
     * @param Request $request
     * @param array $parameter
     * @returns mixed
     */
    protected function getQueryValue(Request $request, array $parameter)
    {
        parse_str($request->getUri()->getQuery(), $query);
        if (!array_key_exists($parameter['name'], $query)) {
            return;
        }

        $value = $query[$parameter['name']];
        if ($parameter['type'] === 'array') {
            // todo can we have nested arrays? gosh, I hope not
            $value = $this->explodeValue($value, $parameter);
        }

        return $value;
    }

    /**
     * @param Request $request
     * @param array $parameter
     * @returns mixed
     */
    protected function getHeaderValue(Request $request, array $parameter)
    {
        $headers = $request->getHeaders();
        if (!array_key_exists($parameter['name'], $headers)) {
            return;
        }

        if ($parameter['type'] !== 'array') {
            return current($headers[$parameter['name']]);
        }

        return $headers[$parameter['name']];
    }

    /**
     * @param Request $request
     * @param array $parameter
     * @param string $route
     * @returns mixed
     */
    protected function getPathValue(Request $request, array $parameter, $route)
    {
        $path = $request->getUri()->getPath();
        $key = str_replace(
            '{' . $parameter['name'] . '}',
            '(?P<' . $parameter['name'] . '>[^/]+)',
            $route
        );
        $key = "@{$key}@";

        if (!preg_match($key, $path, $pathMatches)) {
            return;
        }

        $value = $pathMatches[$parameter['name']];
        if ($parameter['type'] === 'array') {
            $value = $this->explodeValue($value, $parameter);
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param array $parameter
     * @return array
     */
    protected function explodeValue($value, array $parameter)
    {
        $delimiter = $this->getDelimiter($parameter);
        return preg_split("@{$delimiter}@", $value);
    }

    /**
     * @param array $parameter
     * @return string
     */
    protected function getDelimiter(array $parameter)
    {
        $collectionFormat = 'csv';
        if (isset($parameter['collectionFormat'])) {
            $collectionFormat = $parameter['collectionFormat'];
        }

        switch ($collectionFormat) {
            case 'csv':
                $delimiter = ',';
                break;
            case 'ssv':
                $delimiter = '\s';
                break;
            case 'tsv':
                $delimiter = '\t';
                break;
            case 'pipes':
                $delimiter = '|';
                break;
            case 'multi':
                throw new \Exception('not sure how this will work yet');
                break;
            default:
                throw new \Exception('invalid collection format value');
                break;
        }

        return $delimiter;
    }
}
