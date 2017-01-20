<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

interface ParsedSwaggerInterface
{

    /**
     * @return string
     */
    public function getApiPath();

    /**
     * @return array
     */
    public function getPath();

    /**
     * @return array
     */
    public function getOperation();

    /**
     * @return array
     */
    public function getParams();

    /**
     * @return array
     */
    public function getSecurity();

    /**
     * @return array
     */
    public function getSchemes();

    /**
     * @return array
     */
    public function getProduces();

    /**
     * @return array
     */
    public function getConsumes();

    /**
     * @return array
     */
    public function getResponses();
}
