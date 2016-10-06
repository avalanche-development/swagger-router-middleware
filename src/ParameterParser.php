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
            case 'query':
                $value = $this->getQueryValue($request, $parameter);
                break;
            case 'header':
                $value = $this->getHeaderValue($request, $parameter);
                break;
            case 'path':
                $value = $this->getPathValue($request, $parameter);
                break;
            case 'formData':
                $value = $this->getFormDataValue($request, $parameter);
                break;
            case 'body':
                $value = $this->getBodyValue($request, $parameter);
                break;
            default:
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
     * @param array $parameter
     * @returns mixed
     */
    protected function getQueryValue(RequestInterface $request, array $parameter)
    {
        parse_str($request->getUri()->getQuery(), $query);
        if (!array_key_exists($parameter['name'], $query)) {
            return;
        }

        return $query[$parameter['name']];
    }

    /**
     * @param RequestInterface $request
     * @param array $parameter
     * @returns mixed
     */
    protected function getHeaderValue(RequestInterface $request, $parameter)
    {
        $headers = $request->getHeaders();
        if (!array_key_exists($parameter['name'], $headers)) {
            return;
        }

        if (count($headers[$parameter['name']]) === 1) {
            return current($headers[$parameter['name']]);
        }

        return $headers[$parameter['name']];
    }

    protected function getPathValue(RequestInterface $request, $parameter) {}
    protected function getFormDataValue(RequestInterface $request, $parameter) {}
    protected function getBodyValue(RequestInterface $request, $parameter) {}
}
