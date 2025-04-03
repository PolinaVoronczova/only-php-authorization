<?php

namespace Controllers\Route;

use Closure;

class Router
{
    private array $routeList = [];
    
    public function addRoute(string $method, string $url, Closure $calledFunction) 
    {
        $this->routeList[$url][$method] = $calledFunction;
    }

    public function getRoute(string $method, string $requestUrl)
    {
        if (array_key_exists($requestUrl, $this->routeList) && array_key_exists($method, $this->routeList[$requestUrl])) {
            call_user_func($this->routeList[$requestUrl][$method]);
        } else {
            include APP_PATH . '/views/404.phtml';
        }
    }
}