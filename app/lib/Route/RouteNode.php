<?php


class RouteNode
{
    private $name;
    private $method;
    private $callable;
    private $routePattern;
    private $routeOriginal;
    private $after;
    private $before;

    public function __construct($method, $callable, $routePattern, $routeOriginal)
    {
        $this->method = $method;
        $this->callable = $callable;
        $this->routePattern = $routePattern;
        $this->routeOriginal = $routeOriginal;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return mixed
     */
    public function getRoutePattern()
    {
        return $this->routePattern;
    }

    /**
     * @return mixed
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @return mixed
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * @return mixed
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * @return mixed
     */
    public function getRouteOriginal()
    {
        return $this->routeOriginal;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param mixed $after
     */
    public function setAfter($after)
    {
        $this->after = $after;
        return $this;
    }

    /**
     * @param mixed $before
     */
    public function setBefore($before)
    {
        $this->before = $before;
        return $this;
    }
}