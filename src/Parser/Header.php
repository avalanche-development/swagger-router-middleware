<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use Psr\Http\Message\RequestInterface as Request;

class Header implements ParserInterface
{

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
        $headers = $this->request->getHeaders();
        if (!array_key_exists($this->parameter['name'], $headers)) {
            return;
        }

        if ($this->parameter['type'] !== 'array') {
            return current($headers[$this->parameter['name']]);
        }

        return $headers[$this->parameter['name']];
    }
}
