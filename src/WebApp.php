<?php

namespace Simphp;


class WebApp
{
    /**
     * 配置项
     * @var array
     */
    protected $_config = [
        'debug' => true,
        'write_log' => true
    ];

    /**
     * 中间件
     * @var array
     */
    protected $_middleware = [];


    /**
     * 服务提供者，闭包函数提供
     * @var array
     */
    protected $_service_providers = [

    ];

    /**
     * 注册的所有服务
     * @var array
     */
    protected $_services = [];

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
        $this->_init();
    }

    private function _init()
    {
        if ($this->_config['debug']) {
            ini_set('display_errors', true);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', false);
            error_reporting(0);
        }

        // 注册依赖的服务提供者
        $this->register(\Monolog\Logger::class, function () {
            return new \Monolog\Logger('my_logger', new \Monolog\Handler\StreamHandler(__DIR__ . '/a.txt'));
        });

        if ($this->_config['write_log']) {
            $logger = $this->getService(\Monolog\Logger::class);
            set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logger) {

                $logger->info(error_get_last());

//                p_log('--------------------');
//                p_log(error_get_last());
//                p_log('[' . date('Y-m-d H:i:s') . ']' . $errfile . '[' . $errline . ']:' . $errstr);
            });
            set_exception_handler(function ($e) use ($logger){
                $logger->info(error_get_last());
//                p_log('--------------------');
//
//                p_log('[' . date('Y-m-d H:i:s') . ']' . $e->getFile() . '[' . $e->getLine() . ']:' . $e->getMessage());
            });
        }
    }

    /**
     * 依赖注入
     * @param $name
     * @param $handler
     * @return $this
     */
    public function register($name, $handler)
    {
        if ($handler instanceof \Closure) {
            $this->_service_providers[$name] = $handler;
        } else {
            $this->_services[$name] = $handler;
        }
        return $this;
    }

    /**
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function getService($name)
    {
        if (isset($this->_services[$name])) {
            return $this->_services[$name];
        }

        if (isset($this->_service_providers[$name])) {
            $this->_services[$name] = $this->_service_providers[$name]();
            return $this->_services[$name];
        }

        throw new \Exception('代码错误：' . $name . '服务未提供');
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
                $reflectionFunction = new \ReflectionFunction($handler);
                $params = array_merge($this->_getDependencies($reflectionFunction->getParameters()), $params);
                return $reflectionFunction->invokeArgs($params);
            }

            if (!is_array($handler)) {
                $handler = [$handler, '__invoke'];
            }

            // 类名转换成对象
            if (is_string($handler[0])) {
                $handler[0] = new $handler[0];
            }

            $reflection = new \ReflectionObject($handler[0]);
            if ($reflection->hasMethod($handler[1])) {
                $reflectionMethod = $reflection->getMethod($handler[1]);
                $params = array_merge($this->_getDependencies($reflectionMethod->getParameters()), $params);
                return $reflectionMethod->invokeArgs($handler[0], $params);
            }

            return null;
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     *  获取函数，类方法的依赖参数
     * @param array $dependencies
     * @return array
     * @throws \Exception
     */
    private function _getDependencies($dependencies = [])
    {
        $di = [];
        foreach ($dependencies as $parameter) {
            if ($parameter->hasType()) {
                $className = $parameter->getType()->getName();
                $di[] = $this->getService($className);
            }
        }
        return $di;
    }
}