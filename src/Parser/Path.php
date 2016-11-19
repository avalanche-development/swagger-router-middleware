<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use Psr\Http\Message\RequestInterface as Request;

class Path implements ParserInterface
{

    use ExplodeTrait;

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
}
