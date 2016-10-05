<?php

namespace AvalancheDevelopment\SwaggerRouter;

use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ParameterParser implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger;
    }

    /**
     * @param RequestInterface $request
     * @param array $parameter
     * @return mixed
     */
    public function __invoke(RequestInterface $request, array $parameter)
    {
        switch ($parameter['in']) {
            case 'query' :
                $value = $this->getQueryValue($request, $parameter['name']);
                break;
            case 'header' :
                $value = $this->getHeaderValue($request, $parameter['name']);
                break;
            case 'path' :
                $value = $this->getPathValue($request, $parameter['name']);
                break;
            case 'formData' :
                $value = $this->getFormDataValue($request, $parameter['name']);
                break;
            case 'body' :
                $value = $this->getBodyValue($request, $parameter['name']);
                break;
            default :
                throw new Exception();
                break;
        }

        if (!isset($value) && isset($parameter['default'])) {
            $value = $parameter['default'];
        }

        // todo break apart arrays
        // todo cast into respective data types
        return $value;
    }

    /**
     * @param RequestInterface $request
     * @param string $name
     * @returns mixed
     */
    protected function getQueryValue(RequestInterface $request, $name)
    {
        parse_str($request->getUri()->getQuery(), $query);
        if (!array_key_exists($name, $query)) {
            return;
        }

        return $query[$name];
    }

    /**
     * @param RequestInterface $request
     * @param string $name
     * @returns mixed
     */
    protected function getHeaderValue(RequestInterface $request, $name)
    {
        $headers = $request->getHeaders();
        if (!array_key_exists($name, $headers)) {
            return;
        }

        if (count($headers[$name]) === 1) {
            return current($headers[$name]);
        }

        return $headers[$name]; // todo this will break array parser
    }

    protected function getPathValue(RequestInterface $request, $name) {}
    protected function getFormDataValue(RequestInterface $request, $name) {}
    protected function getBodyValue(RequestInterface $request, $name) {}
}
