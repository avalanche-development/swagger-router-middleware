<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use Psr\Http\Message\RequestInterface as Request;

class Query implements ParserInterface
{

    use ExplodeTrait;

    /** @var Request */
    protected $request;

    /** @var array */
    protected $parameter;

    /**
     * @param Request $request
     * @param array $parameter
     */
    public function __construct(Request $request, array $parameter)
    {
        $this->request = $request;
        $this->parameter = $parameter;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        $query = $this->parseQueryString($this->request);
        if (!array_key_exists($this->parameter['name'], $query)) {
            return;
        }

        $value = $query[$this->parameter['name']];
        if ($this->parameter['type'] !== 'array') {
            return $value;
        }
        if (isset($this->parameter['collectionFormat']) && $this->parameter['collectionFormat'] === 'multi') {
            return (array) $value;
        }
        return $this->explodeValue($value, $this->parameter);
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
}
