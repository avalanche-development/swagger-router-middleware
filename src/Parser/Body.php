<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use Psr\Http\Message\RequestInterface as Request;

class Body implements ParserInterface
{

    /** @var Request */
    protected $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        // @todo should return null
        $body = (string) $this->request->getBody();
        return $body;
    }
}
