<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use AvalancheDevelopment\Peel\HttpError\BadRequest;
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
                // todo implement form parameters
                throw new \Exception('Form parameters are not yet implemented');
                break;
            case 'body':
                $value = $this->getBodyValue($request);
                break;
            default:
                throw new \Exception('Invalid parameter type defined in swagger');
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
        $query = $this->parseQueryString($request);
        if (!array_key_exists($parameter['name'], $query)) {
            return;
        }

        $value = $query[$parameter['name']];
        if ($parameter['type'] !== 'array') {
            return $value;
        }
        if (isset($parameter['collectionFormat']) && $parameter['collectionFormat'] === 'multi') {
            return (array) $value;
        }
        return $this->explodeValue($value, $parameter);
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
     * @param Request $request
     * @return array
     */
    protected function parseQueryString(Request $request)
    {
        $params = [];
        $queryString = $request->getUri()->getQuery();
        $setList = explode('&', $queryString);

        foreach ($setList as $set) {
            if (empty($set)) {
                continue;
            }

            list($name, $value) = explode('=', $set);
            $name = urldecode($name);
            if (substr($name, -2) === '[]') {
                $name = substr($name, 0, -2);
            }
            if (!isset($params[$name])) {
                $params[$name] = $value;
                continue;
            }
            if (!is_array($params[$name])) {
                $params[$name] = [$params[$name]];
            }
            array_push($params[$name], $value);
        }

        return $params;
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
            default:
                throw new \Exception('Invalid collection format value defined in swagger');
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
        $type = $this->getParameterType($parameter);

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
                // todo implement file types
                throw new \Exception('File types are not yet implemented');
                break;
            case 'integer':
                $value = (int) $value;
                break;
            case 'number':
                $value = (float) $value;
                break;
            case 'object':
                $value = $this->formatObject($value, $parameter);
                break;
            case 'string':
                $value = (string) $value;
                $value = $this->formatString($value, $parameter);
                break;
            default:
                throw new \Exception('Invalid parameter type value defined in swagger');
                break;
        }

        return $value;
    }

    /**
     * @param array $parameter
     * @return string
     */
    protected function getParameterType(array $parameter)
    {
        $type = '';

        if (isset($parameter['type'])) {
            $type = $parameter['type'];
        }
        if (isset($parameter['in']) && $parameter['in'] === 'body') {
            $type = $parameter['schema']['type'];
        }

        if (empty($type)) {
            throw new \Exception('Parameter type is not defined in swagger');
        }
        return $type;
    }

    /**
     * @param string $value
     * @param array $parameter
     * @return object
     */
    protected function formatObject($value, array $parameter)
    {
        $object = $value;
        if (!is_object($object)) {
            $object = (string) $object;
            $object = json_decode($object);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new BadRequest('Bad json object passed in as parameter');
            }
        }

        $schema = array_key_exists('schema', $parameter) ? $parameter['schema'] : $parameter;
        if (empty($schema['properties'])) {
            return $object;
        }
        $properties = $schema['properties'];

        foreach ($object as $key => $attribute) {
            $object->{$key} = $this->castType($attribute, $properties[$key]);
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
                    throw new BadRequest('Invalid date parameter passed in');
                }
                break;
            case 'date-time':
                try {
                    $value = new DateTime($value);
                } catch (\Exception $e) {
                    throw new BadRequest('Invalid date parameter passed in');
                }
                break;
            default:
                // this is an open-type property
                break;
        }

        return $value;
    }
}
