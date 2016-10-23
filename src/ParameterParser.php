<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use DateTime;
use Psr\Http\Message\RequestInterface as Request;

class ParameterParser
{

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
                $value = $this->getBodyValue($request);
                break;
            default:
                throw new \Exception('invalid parameter type');
                break;
        }

        if (!isset($value) && isset($parameter['default'])) {
            $value = $parameter['default'];
        }

        $value = $this->castType($value, $parameter);
        return $value;
    }

    /**
     * @param Request $request
     * @param array $parameter
     * @return mixed
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
     * @return mixed
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
     * @return mixed
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
     * @param Request $request
     * @return mixed
     */
    protected function getBodyValue(Request $request)
    {
        $body = (string) $request->getBody();
        return $body;
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

    /**
     * @param mixed $value
     * @param array $parameter
     * @return mixed
     */
    protected function castType($value, array $parameter)
    {
        if (isset($parameter['type'])) {
            $type = $parameter['type'];
        }
        if (isset($parameter['in']) && $parameter['in'] === 'body') {
            $type = $parameter['schema']['type'];
        }

        switch ($type) {
            case 'array':
                foreach ($value as $key => $row) {
                    $value[$key] = $this->castType($row, $parameter['items']);
                }
                break;
            case 'boolean':
                $value = (boolean) $value;
                break;
            case 'file':
                throw new \Exception('implement file');
                break;
            case 'integer':
                $value = (int) $value;
                break;
            case 'number':
                $value = (float) $value;
                break;
            case 'object':
                $value = (string) $value;
                $value = $this->formatObject($value);
                break;
            case 'string':
                $value = (string) $value;
                $value = $this->formatString($value, $parameter);
                break;
            default:
                throw new \Exception('invalid parameter type value');
                break;
        }

        return $value;
    }

    /**
     * @param string $value
     * @return object
     */
    protected function formatObject($value)
    {
        // todo this should probably loop through things and format accordingly
        $object = json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception\BadRequest;
        }
        return $object;
    }

    /**
     * @param string $value
     * @param array $parameter
     * @return mixed
     */
    protected function formatString($value, array $parameter)
    {
        if (!array_key_exists('format', $parameter)) {
            return $value;
        }

        switch ($parameter['format']) {
            case 'date':
                $value = DateTime::createFromFormat('Y-m-d', $value);
                if (!$value) {
                    throw new Exception\BadRequest;
                }
                break;
            case 'date-time':
                try {
                    $value = new DateTime($value);
                } catch (\Exception $e) {
                    throw new Exception\BadRequest('', 0, $e);
                }
                break;
            default:
                // this is an open-type property
                break;
        }

        return $value;
    }
}
