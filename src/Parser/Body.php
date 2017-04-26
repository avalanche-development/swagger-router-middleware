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
        $body = (string) $this->request->getBody();
        if (empty($body)) {
            return;
        }

        $headers = $this->request->getHeader('Content-Type');
        $jsonHeaders = array_filter($headers, [ $this, 'checkJsonHeader' ]);
        if (count($jsonHeaders) > 0) {
            return $this->parseJson($body);
        }

        return $body;
    }

    /**
     * @param string $header
     * @return boolean
     */
    protected function checkJsonHeader($header)
    {
        return preg_match('/application\/json/i', $header) > 0;
    }

    /**
     * @param string $body
     * @return mixed
     */
    protected function parseJson($body)
    {
        $parsedBody = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }

        return $parsedBody;
    }
}
