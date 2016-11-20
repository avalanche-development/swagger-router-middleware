<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use Psr\Http\Message\ServerRequestInterface as Request;

class Form implements ParserInterface
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
        $body = $this->request->getParsedBody();
        if (!array_key_exists($this->parameter['name'], $body)) {
            return;
        }

        $value = $body[$this->parameter['name']];
        if ($this->parameter['type'] !== 'array') {
            return $value;
        }
        if (isset($this->parameter['collectionFormat']) && $this->parameter['collectionFormat'] === 'multi') {
            return (array) $value;
        }
        return $this->explodeValue($value, $this->parameter);
    }
}
