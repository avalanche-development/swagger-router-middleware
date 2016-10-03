<?php

namespace AvalancheDevelopment\SwaggerRouter;

class ParameterParser
{

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

        return $value;
    }

    protected function getQueryValue(RequestInterface $request, $name) {}
    protected function getHeaderValue(RequestInterface $request, $name) {}
    protected function getPathValue(RequestInterface $request, $name) {}
    protected function getFormDataValue(RequestInterface $request, $name) {}
    protected function getBodyValue(RequestInterface $request, $name) {}
}
