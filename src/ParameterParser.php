<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use AvalancheDevelopment\Peel\HttpError\BadRequest;
use DateTime;
use Exception;
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
        $parser = $this->getParser($request, $parameter, $route);
        $value = $parser->getValue($request, $parameter);

        if (!isset($value) && isset($parameter['default'])) {
            $value = $parameter['default'];
        }

        $value = $this->castType($value, $parameter);
        return $value;
    }

    /**
     * @param Request $request
     * @param array $parameter
     * @param string $route
     * @return ParserInterface
     */
    protected function getParser(Request $request, array $parameter, $route)
    {
        switch ($parameter['in']) {
            case 'query':
                $parser = new Parser\Query($request, $parameter);
                break;
            case 'header':
                $parser = new Parser\Header($request, $parameter);
                break;
            case 'path':
                $parser = new Parser\Path($request, $parameter, $route);
                break;
            case 'formData':
                $parser = new Parser\Form($request, $parameter);
                break;
            case 'body':
                $parser = new Parser\Body($request);
                break;
            default:
                throw new Exception('Invalid parameter type defined in swagger');
                break;
        }
        return $parser;
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
                throw new Exception('File types are not yet implemented');
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
                throw new Exception('Invalid parameter type value defined in swagger');
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
            throw new Exception('Parameter type is not defined in swagger');
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
                } catch (Exception $e) {
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
