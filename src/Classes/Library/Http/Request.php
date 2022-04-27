<?php

namespace App\Classes\Library\Http;

use App\Classes\Container\Cookie;
use App\Classes\Library\ParameterBag;

class Request
{
    /** @var string * */
    protected $method;
    /** @var string * */
    protected $protocol;
    /** @var bool * */
    protected $isCrossDomain = false;
    /** @var string * */
    protected $externalDomain;
    /** @var string * */
    protected $redirect;
    /** @var string * */
    protected $path;
    /** @var ParameterBag * */
    public $headers;
    /** @var ParameterBag * */
    public $request;
    /** @var ParameterBag * */
    public $query;
    /** @var Cookie * */
    public $cookies;
    /** @var string * */
    public $body = '';

    public function __construct()
    {
        $this->headers = new ParameterBag();
        $this->request = new ParameterBag();
        $this->query = new ParameterBag();
        $this->cookies = new Cookie();
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $protocol
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $externalDomain
     *
     * @return \App\Classes\Library\Http\Request
     */
    public function setCrossDomain($externalDomain)
    {
        $this->externalDomain = $externalDomain;
        $this->isCrossDomain = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function getCrossDomain()
    {
        return $this->isCrossDomain;
    }
}
