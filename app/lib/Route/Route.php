<?php
require_once "RouteNode.php";

class Route
{
    private static $routs = array();

    public static function add($route, $callable, $method = "any")
    {
        $method = strtolower($method);

        if($route == '*')
            $routePattern = '.*?';

        $routePattern = preg_replace_callback("#{(.*?)}#",function($matches){
                        $parts = explode("=", $matches[1], 2);
                        if(count($parts) == 2)
                            return '(' . trim($parts[1], '()') . ')';

                        return '(.*?)';
                    },$route );

        $routePattern = "/^" . str_replace('/', '\/' , $routePattern) . '$/i';
        $routerNode = new RouteNode($method, $callable, $routePattern, $route);

        self::$routs[] = $routerNode;

        return $routerNode;
    }

    public static function post($route, $callable)
    {
        return self::add( $route, $callable,"post");
    }

    public static function get($route, $callable)
    {
        return self::add( $route, $callable,"get");
    }

    public static function any($route, $callable)
    {
        return self::add( $route, $callable,"any");
    }

    public static function exec()
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        list($path) = explode('?',$_SERVER['REQUEST_URI'],2);
        $find = false;
        foreach (self::$routs as $route)
        {
            if( ($route->getMethod() == $method || $route->getMethod() == 'any') && preg_match($route->getRoutePattern(), $path, $matches))
            {
                $find = true;
                array_shift($matches);

                // call Before Callable
                self::call($route->getBefore());

                // call Router callable
                $res = self::call($route->getCallable(), $matches);

                // call After Callable
                self::call($route->getAfter());

                if($res === null || $res === true)
                {
                    break;
                }

                if(is_array($res))
                {
                    header('Content-Type: application/json');
                    echo json_encode($res);
                    break;
                }

                if(is_string($res) || is_numeric($res))
                {
                    echo $res;
                    break;
                }
            }
        }

        if(!$find)
        {
            die("Not Found");
        }
    }

    public static function link($routeName, $params= array())
    {
        foreach (self::$routs as $route)
        {
            if($route->getName() == $routeName)
            {
                return preg_replace_callback("#{(.*?)}#",function($matches) use ($routeName, $params){
                    list($name) = explode("=", $matches[1], 2);
                    if(isset($params[$name]))
                        return $params[$name];

                    throw new Exception("Route Make Link of {$routeName} no passed {$name}.");
                },$route->getRouteOriginal() );
            }
        }

        throw new Exception("Route Make Link not find {$routeName}.");
    }

    private static function call($callable, $params = array())
    {
        $res = null;
        if($callable != null && is_callable($callable))
            $res = call_user_func_array($callable, $params);
        return $res;
    }

    public static function redirect($location)
    {
        header("Location: {$location}");
        die();
    }

    public static function back()
    {
        $back_url = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "/";
        self::redirect($back_url);
    }

}