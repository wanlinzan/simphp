<?php

namespace simphp;


use Monolog\Formatter\LineFormatter;
use Noodlehaus\Config;

class WebApp
{
    /**
     * 配置文件所在的目录
     * @var string
     */
    protected $_config_dir = __DIR__ . '/config';

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

    /**
     * WebApp constructor.
     * @param null $config_dir
     */
    public function __construct($config_dir = null)
    {
        // 配置文件目录
        if (!is_null($config_dir)) {
            $this->_config_dir = $config_dir;
        }

        // 注册配置读取服务提供者
        $this->register(Config::class, new Config($this->_config_dir));

        // 初始化
        $this->_init();
    }

    /**
     * 初始化
     */
    private function _init()
    {
        $config = $this->getService(Config::class);

        if ($config['debug']) {
            ini_set('display_errors', true);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', false);
            error_reporting(0);
        }

        // 注册依赖的服务提供者
        $this->register(\Monolog\Logger::class, function () use ($config) {
            $logger = new \Monolog\Logger('my_logger');
            $stream = new \Monolog\Handler\StreamHandler($config['log_dir'] . '/' . date('Ymd') . '.log');
            $output = "[%datetime%]%level_name%: %message% %context% %extra%\n";
            $formatter = new LineFormatter($output);
            $stream->setFormatter($formatter);
            $logger->pushHandler($stream);
            return $logger;
        });

        // 错误处理
        set_error_handler(function ($err_no, $err_str, $err_file, $err_line) use ($config) {
            $logger = $this->getService(\Monolog\Logger::class);
            $logger->info(error_get_last());
            $logger->info($err_file . '[' . $err_line . ']' . ':' . $err_str);

            if ($config['debug']) {
                echo $err_file . '[' . $err_line . ']' . ':' . $err_str;
            }
        });
        set_exception_handler(function ($e) use ($config) {
            $logger = $this->getService(\Monolog\Logger::class);
            $logger->info(error_get_last());
            $logger->info($e->getFile() . '[' . $e->getLine() . ']' . ':' . $e->getMessage());
            if ($config['debug']) {
                echo $e->getFile() . '[' . $e->getLine() . ']' . ':' . $e->getMessage();
            }
        });
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
     * 获取服务
     * @param $name
     * @return mixed
     */
    public function getService($name)
    {
        if (isset($this->_services[$name])) {
            return $this->_services[$name];
        }

        if (isset($this->_service_providers[$name])) {
            $this->_services[$name] = $this->exec($this->_service_providers[$name]);
            return $this->_services[$name];
        }

        return $this->make($name);
    }

    /**
     * 实列化类
     * @param $className
     * @return mixed
     */
    public function make($className)
    {
        try {
            $reflectionClass = new \ReflectionClass($className);

            $params = [];

            $reflectionMethod = $reflectionClass->getConstructor();

            if (!is_null($reflectionMethod)) {

                $parameters = $reflectionMethod->getParameters();

                foreach ($parameters as $parameter) {

                    if ($parameter->getClass()) {
                        $params[] = $this->getService($parameter->getClass()->getName());
                    } else if ($parameter->isDefaultValueAvailable()) {
                        $params[] = $parameter->getDefaultValue();
                    } else if ($parameter->isArray()) {
                        $params[] = [];
                    } else {
                        $params[] = '';
                    }
                }
            }

            $this->_services[$className] = $reflectionClass->newInstanceArgs($params);

            return $this->_services[$className];
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
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
        if (isset($_SERVER['PATH_INFO'])) {
            $action = (!isset($_SERVER['PATH_INFO']) || empty($_SERVER['PATH_INFO'])) ? '/' : rtrim($_SERVER['PATH_INFO'], '/');
        } else if (isset($_SERVER['REQUEST_URI'])) {
            $params = parse_url($_SERVER['REQUEST_URI']);
            $action = isset($params['path']) ? rtrim($params['path'], '/') : '/';
            if (isset($_SERVER['SCRIPT_NAME'][0])) {
                if (strpos($action, $_SERVER['SCRIPT_NAME']) === 0) {
                    $action = (string)substr($action, strlen($_SERVER['SCRIPT_NAME']));
                } elseif (strpos($action, dirname($_SERVER['SCRIPT_NAME'])) === 0 && dirname($_SERVER['SCRIPT_NAME']) != '/') {
                    $action = (string)substr($action, strlen(dirname($_SERVER['SCRIPT_NAME'])));
                }
            }
            if ($action == '') {
                $action = '/';
            }
        } else {
            $action = '/';
        }

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
     * 执行处理器,指定中间件，控制器，闭包
     * @param $handler
     * @param array $param
     * @return mixed|null
     */
    protected function exec($handler, $param = null)
    {
        try {
            if ($handler instanceof \Closure) {
                $reflectionFunction = new \ReflectionFunction($handler);
                $params = $this->_getDependencies($reflectionFunction->getParameters());
                if (!is_null($param)) {
                    $params[] = $param;
                }
                return $reflectionFunction->invokeArgs($params);
            }

            if (!is_array($handler)) {
                $handler = [$handler, '__invoke'];
            }

            // 类名转换成对象
            if (is_string($handler[0])) {
                $handler[0] = $this->getService($handler[0]);
            }

            $reflection = new \ReflectionObject($handler[0]);
            if ($reflection->hasMethod($handler[1])) {
                $reflectionMethod = $reflection->getMethod($handler[1]);
                $params = $this->_getDependencies($reflectionMethod->getParameters());
                if (!is_null($param)) {
                    $params[] = $param;
                }
                return $reflectionMethod->invokeArgs($handler[0], $params);
            }

            return null;
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     *  获取函数，类方法的依赖参数
     * @param array $dependencies
     * @return array
     * @throws \Exception
     */
    protected function _getDependencies($dependencies = [])
    {
        $di = [];
        foreach ($dependencies as $parameter) {
            if ($parameter->getClass()) {
                $className = $parameter->getClass()->getName();
                $di[] = $this->getService($className);
            }
        }
        return $di;
    }
}