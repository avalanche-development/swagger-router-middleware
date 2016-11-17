<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parameter;

use Exception;
use Psr\Http\Message\RequestInterface as Request;

class Query
{

    /**
     * @param Request $request
     * @param array $parameter
     * @return mixed
     */
    public function __invoke(Request $request, array $parameter)
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
                throw new Exception('Invalid collection format value defined in swagger');
                break;
        }

        return $delimiter;
    }
}
