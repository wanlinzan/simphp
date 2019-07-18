<?php

namespace Simphp;

class WebApp
{
    /**
     * 配置项
     * @var array
     */
    protected $_config = [];

    /**
     * 中间件
     * @var array
     */
    protected $_middleware = [];

    /**
     * 依赖注入
     * @var array
     */
    protected $_dependency_injections = [];

    /**
     * 当前路由前缀
     * @var string
     */
    protected $_current_route_prefix = '';

    /**
     * 当前路由需要绑定的中间件
     * @var array
     */
    protected $_current_route_middleware = [];

    /**
     * 所有的路由
     * @var array
     */
    protected $_routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'OPTION' => [],
        'DELETE' => []
    ];

    public function __construct(array $config = [])
    {
        $this->_config = array_merge($this->_config, $config);
    }

    /**
     * 依赖注入
     * @param $name
     * @param $object
     * @return $this
     */
    public function setDependencyInjection($name, $object)
    {
        $this->_dependency_injections[$name] = $object;
        return $this;
    }

    /**
     * 添加中间件
     * @param $handle
     * @return $this
     */
    public function addMiddleware($handle)
    {
        $this->_middleware[] = $handle;
        return $this;
    }

    /**
     * get 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middleware
     */
    public function get($route, $handle, $middleware = [])
    {
        $middleware = (array)$middleware;
        $this->map(['GET'], $route, $handle, $middleware);
    }

    /**
     * post 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middleware
     */
    public function post($route, $handle, $middleware = [])
    {
        $middleware = (array)$middleware;
        $this->map(['POST'], $route, $handle, $middleware);
    }

    /**
     * put 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middleware
     */
    public function put($route, $handle, $middleware = [])
    {
        $middleware = (array)$middleware;
        $this->map(['PUT'], $route, $handle, $middleware);
    }

    /**
     * patch 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middleware
     */
    public function patch($route, $handle, $middleware = [])
    {
        $middleware = (array)$middleware;
        $this->map(['PATCH'], $route, $handle, $middleware);
    }

    /**
     * delete 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middleware
     */
    public function delete($route, $handle, $middleware = [])
    {
        $middleware = (array)$middleware;
        $this->map(['DELETE'], $route, $handle, $middleware);
    }

    /**
     * options 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middleware
     */
    public function options($route, $handle, $middleware = [])
    {
        $middleware = (array)$middleware;
        $this->map(['OPTIONS'], $route, $handle, $middleware);
    }

    /**
     * any 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middleware
     */
    public function any($route, $handle, $middleware = [])
    {
        $middleware = (array)$middleware;
        $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route, $handle, $middleware);
    }

    /**
     * 设置路由
     * @param array $methods
     * @param $route
     * @param $handle
     * @param array $middleware
     */
    public function map(array $methods, $route, $handle, $middleware = [])
    {
        $middleware = (array)$middleware;
        foreach ($methods as $method) {
            $this->_routes[$method][$this->_current_route_prefix . $route] = [
                'handle' => $handle,
                'middleware' => array_merge($middleware, $this->_current_route_middleware)
            ];
        }
    }

    /**
     * 路由分组
     * @param $route
     * @param callable $handle
     * @param array $middleware
     */
    public function group($route, callable $handle, $middleware = [])
    {
        $middleware = (array)$middleware;
        // 路由
        $temp_route_prefix = $this->_current_route_prefix;
        $this->_current_route_prefix .= $route;

        // 中间件
        $temp_route_middleware = $this->_current_route_middleware;
        $this->_current_route_middleware = array_merge($this->_current_route_middleware, $middleware);

        $handle($this, $route);

        $this->_current_route_middleware = $temp_route_middleware;
        $this->_current_route_prefix = $temp_route_prefix;
    }

    /**
     * ajax 方法，用于输出数据
     * @param array $data
     * @return string
     */
    public function ajax($data)
    {
        header('Content-Type:application/json; charset=utf-8');
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * ajax 的快捷方法
     * @param string $message
     * @param array $data
     * @return string
     */
    public function success($message = '', $data = [])
    {
        return $this->ajax([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * ajax 的快捷方法
     * @param $message
     * @return string
     */
    public function error($message)
    {
        return $this->ajax([
            'status' => 'error',
            'message' => $message
        ]);
    }

    /**
     * 运行APP
     */
    public function run()
    {
        //获取 action
        if (isset($_SERVER['REQUEST_URI'])) {
            $params = parse_url($_SERVER['REQUEST_URI']);
            $action = isset($params['path']) ? rtrim($params['path'], '/') : '/';
            if (isset($_SERVER['SCRIPT_NAME'][0])) {
                if (strpos($action, $_SERVER['SCRIPT_NAME']) === 0) {
                    $action = (string)substr($action, strlen($_SERVER['SCRIPT_NAME']));
                } elseif (strpos($action, dirname($_SERVER['SCRIPT_NAME'])) === 0 && dirname($_SERVER['SCRIPT_NAME']) != '/') {
                    $action = (string)substr($action, strlen(dirname($_SERVER['SCRIPT_NAME'])));
                }
            }
        } else {
            $action = '/';
        }
//        $action = (!isset($_SERVER['PATH_INFO']) || empty($_SERVER['PATH_INFO'])) ? '/' : rtrim($_SERVER['PATH_INFO'], '/');

        //请求方式
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        //路由查找
        $route_keys = array_keys($this->_routes[$method]);
        $flag = false;
        $handle_result = '';
        foreach ($route_keys as $route_key) {
            preg_match('#^' . $route_key . '$#is', $action, $all);
            if (empty($all)) {
                continue;
            }
            $flag = true;
            $middleware_result = true;
            // 执行中间件中的处理函数
            $this->_middleware = array_merge($this->_middleware, $this->_routes[$method][$route_key]['middleware']);
            foreach ($this->_middleware as $middleware) {
                $middleware_result = $this->exec($middleware, $all);
                if ($middleware_result !== true) {
                    break;
                }
            }
            if ($middleware_result === true) {
                $handle_result = $this->exec($this->_routes[$method][$route_key]['handle'], $all);
            } else {
                $handle_result = $middleware_result;
            }

            break;
        }
        if (false === $flag) {
            echo $this->error('接口不存在');
        } else {
            echo $this->ajax($handle_result);
        }
    }

    /**
     * 执行处理器
     * @param $handler
     * @param array $params
     * @return mixed|null
     */
    protected function exec($handler, $params = [])
    {
        try {


            if ($handler instanceof \Closure) {
                $reflection = new \ReflectionFunction($handler);
                $di = [];
                foreach ($reflection->getParameters() as $parameter) {
                    if ($parameter->hasType()) {
                        $className = $parameter->getType()->getName();
                        if (isset($this->_dependency_injections[$className])) {
                            $di[] = $this->_dependency_injections[$className];
                        }
                    }
                }
                $di = array_merge($di, $params);
                return $reflection->invokeArgs($di);
            } else if (is_object($handler)) {

                $reflection = new \ReflectionObject($handler);
                $di = [];
                if ($reflection->hasMethod('__invoke')) {
                    $construct = $reflection->getMethod('__invoke');
                    foreach ($construct->getParameters() as $parameter) {
                        if ($parameter->hasType()) {
                            $className = $parameter->getType()->getName();
                            if (isset($this->_dependency_injections[$className])) {
                                $di[] = $this->_dependency_injections[$className];
                            }
                        }
                    }
                    $di = array_merge($di, $params);
                    return $construct->invokeArgs($handler, $di);
                }

            } else if (is_string($handler)) {
                $handler = new $handler;
                $reflection = new \ReflectionObject($handler);
                $di = [];
                if ($reflection->hasMethod('__invoke')) {
                    $construct = $reflection->getMethod('__invoke');
                    foreach ($construct->getParameters() as $parameter) {
                        if ($parameter->hasType()) {
                            $className = $parameter->getType()->getName();
                            if (isset($this->_dependency_injections[$className])) {
                                $di[] = $this->_dependency_injections[$className];
                            }
                        }
                    }
                    $di = array_merge($di, $params);
                    return $construct->invokeArgs($handler, $di);
                }
            } else if (is_array($handler)) {
                if (is_object($handler[0])) {
                    $reflection = new \ReflectionObject($handler[0]);
                    $di = [];
                    if ($reflection->hasMethod($handler[1])) {
                        $construct = $reflection->getMethod($handler[1]);
                        foreach ($construct->getParameters() as $parameter) {
                            if ($parameter->hasType()) {
                                $className = $parameter->getType()->getName();
                                if (isset($this->_dependency_injections[$className])) {
                                    $di[] = $this->_dependency_injections[$className];
                                }
                            }
                        }
                        $di = array_merge($di, $params);
                        return $construct->invokeArgs($handler[0], $di);
                    }
                } else if (is_string($handler[0])) {
                    $controller = new $handler[0]();
                    $reflection = new \ReflectionObject($controller);
                    $di = [];
                    if ($reflection->hasMethod($handler[1])) {
                        $construct = $reflection->getMethod($handler[1]);
                        foreach ($construct->getParameters() as $parameter) {
                            if ($parameter->hasType()) {
                                $className = $parameter->getType()->getName();
                                if (isset($this->_dependency_injections[$className])) {
                                    $di[] = $this->_dependency_injections[$className];
                                }
                            }
                        }
                        $di = array_merge($di, $params);
                        return $construct->invokeArgs($controller, $di);
                    }
                }
            }
            return null;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}