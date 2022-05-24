<?php
namespace Library;
class RouteParameterBinder{
    
    public static function bindRouterParameter(&$router, $uri){
        $parameters = self::detectRouterWithParameter($router->uri, $uri);
        if(!empty($parameters)){
            $router->parameters = self::bindParameterDefault($router->parameters, $parameters);
            $router->parameters = array_merge($router->parameters, $parameters);
            return true;
        }
        else{
            return false;
        }
    }
    private static function bindParameterDefault($parameterDefault, $parameters){
        foreach($parameterDefault as $key => $value){
            if(strpos($value, '{') === 0 && strpos($value, '}') === strlen($value) - 1){
                $keyToDetect = substr($value, 1, strlen($value) - 2);
                if(isset($parameters[$keyToDetect])){
                    $parameterDefault[$key] = $parameters[$keyToDetect];
                }
            }
        }
        return $parameterDefault;
    }
    private static function detectRouterWithParameter($uriPattern, $uri){
        $parameterKeys = self::getParamtersKey($uriPattern);
        if(count($parameterKeys) > 0){
            $regex = self::convertToRegex($uriPattern, $parameterKeys);
            $parameters = self::detectParamters($regex, $uri, $parameterKeys);
            return $parameters;
        }
        return false;
    }
    private static function detectParamters($regex, $string, $parameterKeys){
        $parameters = [];
        $matchsParameter = [];
        preg_match($regex,$string,$matchsParameter);
        if(count($parameterKeys)+1==count($matchsParameter)){
            for($i=0; $i < count($parameterKeys); $i++){
                $key = substr($parameterKeys[$i], 1, strlen($parameterKeys[$i]) - 2);
                $parameters[$key] = $matchsParameter[$i + 1];
            }
        }
        return $parameters;
    }
    
    private static function convertToRegex($uri, $parameter){
        foreach($parameter as $key){
            $uri = str_replace($key,'(.+)',$uri);
        }
        $uri = str_replace('/','\/', $uri);
        $uri = "/^$uri$/";
        return $uri;
    }
    private static function getParamtersKey($uri){
        $matchs = [];
        preg_match_all('/\{([A-Za-z0-9_-]+)\}/', $uri, $matchs);
        if(isset($matchs[0])){
            return $matchs[0];
        }
        return [];
    }
}