<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use AvalancheDevelopment\Peel\HttpError\BadRequest;
use Exception;
use Psr\Http\Message\ServerRequestInterface as Request;

class ParameterParser
{

    /**
     * @param Request $request
     * @param array $parameter
     * @param string $route
     * @return mixed
     */
    public function __invoke(Request $request, array $parameter, $route)
    {
        $parser = $this->getParser($request, $parameter, $route);
        $value = $parser->getValue($request, $parameter);

        if (!isset($value) && isset($parameter['default'])) {
            $value = $parameter['default'];
        }

        return $value;
    }

    /**
     * @param Request $request
     * @param array $parameter
     * @param string $route
     * @return ParserInterface
     */
    protected function getParser(Request $request, array $parameter, $route)
    {
        switch ($parameter['in']) {
            case 'query':
                $parser = new Parser\Query($request, $parameter);
                break;
            case 'header':
                $parser = new Parser\Header($request, $parameter);
                break;
            case 'path':
                $parser = new Parser\Path($request, $parameter, $route);
                break;
            case 'formData':
                $parser = new Parser\Form($request, $parameter);
                break;
            case 'body':
                $parser = new Parser\Body($request);
                break;
            default:
                throw new Exception('Invalid parameter type defined in swagger');
                break;
        }
        return $parser;
    }
}
