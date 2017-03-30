<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

interface ParsedSwaggerInterface
{

    /**
     * @param string $apiPath
     */
    public function setApiPath($apiPath);

    /**
     * @return string
     */
    public function getApiPath();

    /**
     * @param array $path
     */
    public function setPath(array $path);

    /**
     * @return array
     */
    public function getPath();

    /**
     * @param array $operation
     */
    public function setOperation(array $operation);

    /**
     * @return array
     */
    public function getOperation();

    /**
     * @param array
     */
    public function setParams(array $params);

    /**
     * @return array
     */
    public function getParams();

    /**
     * @param array $security
     */
    public function setSecurity(array $security);

    /**
     * @return array
     */
    public function getSecurity();

    /**
     * @param array $schemes
     */
    public function setSchemes(array $schemes);

    /**
     * @return array
     */
    public function getSchemes();

    /**
     * @param array $produces
     */
    public function setProduces(array $produces);

    /**
     * @return array
     */
    public function getProduces();

    /**
     * @param array $consumes
     */
    public function setConsumes(array $consumes);

    /**
     * @return array
     */
    public function getConsumes();

    /**
     * @param array $responses
     */
    public function setResponses(array $responses);

    /**
     * @return array
     */
    public function getResponses();
}
