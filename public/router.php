<?php

class Router {
    private array $routes = [];

    public function add(string $route, string $callback) {
        $this->routes[$route] = $callback;
        return $this;
    }

    public function middleware(string $class) {
        $middleware = new $class();
        $middleware->handle();
    }

    public function run() {
        $route = $_SERVER['REQUEST_URI'];
        $last_char = $route[-1];
        if ($last_char === "/") {
            $route = substr($route, 0, -1);
        }

        $parts = explode("/", $route);
        array_shift($parts);

        // current route is /
        if (!count($parts)) {
            $parts[] = "";
        }

        $current_route = null;
        $current_callback = null;
        $current_params = [];

        foreach ($this->routes as $route => $callback) {
            $route_parts = explode("/", $route);
            array_shift($route_parts);
            
            for ($i = 0; $i < count($route_parts); $i++) {
                preg_match("/{(.*)}/", $route_parts[$i], $matches);

                for ($j = 0; $j < count($parts); $j++) {

                    if ($i == $j) {
                        if (!empty($matches[1])) {
                            $current_route = $route;
                            $current_callback = $callback;
                            $current_params[] = $parts[$j];
                            continue;
                        } elseif ($route_parts[$i] == $parts[$j]) {
                            $current_route = $route;
                            $current_callback = $callback;
                            continue;
                        } else {
                            $current_route = null;
                            continue;
                        }
                    } else {
                        $current_route = null;
                        continue;
                    }
                                
                }
            }

            if ($current_route) {
                break;
            }
        }

        if ($current_callback && $current_route) {
            $class_parts = explode("@", $current_callback);
            $class = $class_parts[0];
            $method = $class_parts[1];
            $class_name = "\\App\\Controllers\\" . $class;
            if (count($current_params) > 0) {
                call_user_func_array(array(new $class_name, $method), $current_params);
            } else {
                call_user_func(array(new $class_name, $method));
            }
            
        } else {
            echo "404";
        }
        
    }
}