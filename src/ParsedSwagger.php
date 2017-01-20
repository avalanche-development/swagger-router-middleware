<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

class ParsedSwagger implements ParsedSwaggerInterface
{

    /** @var string $apiPath */
    protected $apiPath;

    /** @var array $path */
    protected $path;

    /** @var array $operation */
    protected $operation;

    /** @var array $params */
    protected $params;

    /** @var array $security */
    protected $security;

    /** @var array $schemes */
    protected $schemes;

    /** @var array $produces */
    protected $produces;

    /** @var array $consumes */
    protected $consumes;

    /** @var array $responses */
    protected $responses;

    /**
     * @param string $apiPath
     */
    public function setApiPath($apiPath)
    {
        $this->apiPath = $apiPath;
    }

    /**
     * @return string
     */
    public function getApiPath()
    {
        return $this->apiPath ?: '';
    }

    /**
     * @param array $path
     */
    public function setPath(array $path)
    {
        $this->path = $path;
    }

    /**
     * @return array
     */
    public function getPath()
    {
        return $this->path ?: [];
    }

    /**
     * @param array $operation
     */
    public function setOperation(array $operation)
    {
        $this->operation = $operation;
    }

    /**
     * @return array
     */
    public function getOperation()
    {
        return $this->operation ?: [];
    }

    /**
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params ?: [];
    }

    /**
     * @param array $security
     */
    public function setSecurity(array $security)
    {
        $this->security = $security;
    }

    /**
     * @return array
     */
    public function getSecurity()
    {
        return $this->security ?: [];
    }

    /**
     * @param array $schemes
     */
    public function setSchemes(array $schemes)
    {
        $this->schemes = $schemes;
    }

    /**
     * @return array
     */
    public function getSchemes()
    {
        return $this->schemes ?: [];
    }

    /**
     * @param array $produces
     */
    public function setProduces(array $produces)
    {
        $this->produces = $produces;
    }

    /**
     * @return array
     */
    public function getProduces()
    {
        return $this->produces ?: [];
    }

    /**
     * @param array $consumes
     */
    public function setConsumes(array $consumes)
    {
        $this->consumes = $consumes;
    }

    /**
     * @return array
     */
    public function getConsumes()
    {
        return $this->consumes ?: [];
    }

    /**
     * @param array $responses
     */
    public function setResponses(array $responses)
    {
        $this->responses = $responses;
    }

    /**
     * @return array
     */
    public function getResponses()
    {
        return $this->responses ?: [];
    }
}
