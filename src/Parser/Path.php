<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use Exception;
use Psr\Http\Message\RequestInterface as Request;

class Path implements ParserInterface
{

    /** @var Request */
    protected $request;

    /** @var array */
    protected $parameter;

    /** @var string */
    protected $route;

    /**
     * @param Request $request
     * @param array $parameter
     * @param string $route
     */
    public function __construct(Request $request, array $parameter, $route)
    {
        $this->request = $request;
        $this->parameter = $parameter;
        $this->route = $route;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        $path = $this->request->getUri()->getPath();
        $key = str_replace(
            '{' . $this->parameter['name'] . '}',
            '(?P<' . $this->parameter['name'] . '>[^/]+)',
            $this->route
        );
        $key = "@{$key}@";

        if (!preg_match($key, $path, $pathMatches)) {
            return;
        }

        $value = $pathMatches[$this->parameter['name']];
        if ($this->parameter['type'] === 'array') {
            $value = $this->explodeValue($value, $this->parameter);
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
            default:
                throw new Exception('Invalid collection format value defined in swagger');
                break;
        }

        return $delimiter;
    }
}
