<?php

namespace Simphp;

class WebApp
{

    /**
     * 框架版本
     * @var string
     */
    protected $_version = 'Simphp 1.0';

    /**
     * 配置项
     * @var array
     */
    protected $_config = [];

    /**
     * 中间件
     * @var array
     */
    protected $_middlewares = [];

    /**
     * 对象树
     * @var array
     */
    protected $_objectTree = [];

    /**
     * 当前路由前缀
     * @var string
     */
    protected $_current_route_prefix = '';

    /**
     * 当前路由需要绑定的中间件
     * @var array
     */
    protected $_current_route_middlewares = [];

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
     * 设置对象到对象树中
     * @param $name
     * @param $object
     * @return $this
     */
    public function setObject($name, $object)
    {
        $this->_objectTree[$name] = $object;
        return $this;
    }

    /**
     * 快速获取对象树中的对象
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->_objectTree[$name])) {
            return $this->_objectTree[$name];
        } else {
            $this->error($name . '对象不存在');
            return false;
        }
    }

    /**
     * 添加中间件
     * @param callable $handle
     * @return $this
     */
    public function addMiddleware(callable $handle)
    {
        $this->_middlewares[] = $handle;
        return $this;
    }

    /**
     * get 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middlewares
     * @return $this
     */
    public function get($route, $handle, $middlewares = [])
    {
        $this->map(['GET'], $route, $handle, $middlewares);
        return $this;
    }

    /**
     * post 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middlewares
     * @return $this
     */
    public function post($route, $handle, $middlewares = [])
    {
        $this->map(['POST'], $route, $handle, $middlewares);
        return $this;
    }

    /**
     * put 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middlewares
     * @return $this
     */
    public function put($route, $handle, $middlewares = [])
    {
        $this->map(['PUT'], $route, $handle, $middlewares);
        return $this;
    }

    /**
     * patch 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middlewares
     * @return $this
     */
    public function patch($route, $handle, $middlewares = [])
    {
        $this->map(['PATCH'], $route, $handle, $middlewares);
        return $this;
    }

    /**
     * delete 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middlewares
     * @return $this
     */
    public function delete($route, $handle, $middlewares = [])
    {
        $this->map(['DELETE'], $route, $handle, $middlewares);
        return $this;
    }

    /**
     * options 路由快捷方法
     * @param $route
     * @param $handle
     * @param array $middlewares
     * @return $this
     */
    public function options($route, $handle, $middlewares = [])
    {
        $this->map(['OPTIONS'], $route, $handle, $middlewares);
        return $this;
    }

    /**
     * any 路由快捷方法
     * @param $route
     * @param $handle
     * @return $this
     */
    public function any($route, $handle)
    {
        $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route, $handle);
        return $this;
    }

    /**
     * 设置路由
     * @param array $methods
     * @param $route
     * @param $handle
     * @param array $middlewares
     * @return $this
     */
    public function map(array $methods, $route, $handle, $middlewares = [])
    {
        foreach ($methods as $method) {
            $this->_routes[$method][$this->_current_route_prefix . $route] = [
                'handle' => $handle,
                'middlewares' => array_merge($middlewares, $this->_current_route_middlewares)
            ];
        }
        return $this;
    }

    /**
     * 路由分组
     * @param $route
     * @param callable $handle
     * @param array $middlewares
     * @return $this
     */
    public function group($route, callable $handle, $middlewares = [])
    {
        // 路由
        $temp_route_prefix = $this->_current_route_prefix;
        $this->_current_route_prefix .= $route;

        // 中间件
        $temp_route_middlewares = $this->_current_route_middlewares;
        $this->_current_route_middlewares = array_merge($this->_current_route_middlewares, $middlewares);

        $handle($this, $route);

        $this->_current_route_middlewares = $temp_route_middlewares;
        $this->_current_route_prefix = $temp_route_prefix;
        return $this;
    }

    /**
     * ajax 方法，用于输出数据
     * @param array $data
     * @return string
     */
    public function ajax(array $data)
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
        foreach ($route_keys as $route_key) {
            preg_match('#^' . $route_key . '$#is', $action, $all);
            if (!empty($all)) {
                //执行中间件中的处理函数
                foreach ($this->_middlewares as $middleware) {
                    $middleware = $middleware->bindTo($this);
                    $middleware($route_key, $all);
                }
                $handler = $this->_routes[$method][$route_key]['handle'];
                $this->exec($handler);
                $flag = true;
                break;
            }
        }
        if (false === $flag) {
            echo $this->error('接口不存在');
        }
    }

    /**
     * 执行处理器
     * @param $handler
     * @param array $param
     */
    protected function exec($handler, $param = [])
    {
        if ($handler instanceof \Closure) {
            $handler($param);
        } else if (is_object($handler)) {
            $handler($param);
        } else if (is_string($handler)) {
            $handler = new $handler;
            $handler($param);
        } else if (is_array($handler)) {
            if (is_object($handler[1])) {
                $controller = new $handler
            }

        }
    }
}