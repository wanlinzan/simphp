<?php
namespace simphp;

class App
{

    //框架版本
    protected $_version = '0.1';

    //配置项
    protected $_config = [];

    //中间件
    protected $_middleware = [];

    //对象树
    protected $_objectTree = [];

    //所有的路由
    protected $_routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'OPTION' => [],
        'DELETE' => []
    ];

    //http错误处理函数
    protected $_httpErrors = [
        'NotFound' => '',
    ];


    public function __construct(array $config = [])
    {
        $this->_config = array_merge($this->_config, $config);
        $this->_httpErrors['NotFound'] = function () {
            exit('页面被程序猿吃了...');
        };
    }

    //设置对象到对象树中
    public function setObject($name, $object)
    {
        $this->_objectTree[$name] = $object;
        return $this;
    }

    //快速获取对象树中的对象
    public function __get($name)
    {
        if (isset($this->_objectTree[$name])) {
            return $this->_objectTree[$name];
        } else {
            exit ($name . '对象不存在');
        }
    }

    //添加中间件
    public function addMiddleware(callable $handle)
    {
        $this->_middleware[] = $handle;
        return $this;
    }


    //设置404未找到方法
    public function setNotFound(callable $handle)
    {
        $this->_httpErrors['NotFound'] = $handle;
        return $this;
    }

    //get路由快捷方法
    public function get($route, $handle)
    {
        $this->map(['GET'], $route, $handle);
        return $this;
    }

    //post路由快捷方法
    public function post($route, $handle)
    {
        $this->map(['POST'], $route, $handle);
        return $this;
    }

    //put路由快捷方法
    public function put($route, $handle)
    {
        $this->map(['PUT'], $route, $handle);
        return $this;
    }

    //patch路由快捷方法
    public function patch($route, $handle)
    {
        $this->map(['PATCH'], $route, $handle);
        return $this;
    }

    //delete路由快捷方法
    public function delete($route, $handle)
    {
        $this->map(['DELETE'], $route, $handle);
        return $this;
    }

    //options路由快捷方法
    public function options($route, $handle)
    {
        $this->map(['OPTIONS'], $route, $handle);
        return $this;
    }

    //any路由快捷方法
    public function any($route, $handle)
    {
        $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route, $handle);
        return $this;
    }

    //设置路由
    public function map(array $methods, $route, callable $handle)
    {
        foreach ($methods as $method) {
            $this->_routes[$method][$route] = $handle;
        }
        return $this;
    }


    //ajax方法，用于输出数据
    public function ajax(array $data)
    {
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    //ajax的快捷方法
    public function success($message, $url = null)
    {
        $data = [
            'code' => 1,
            'msg' => $message
        ];
        if (!is_null($url)) {
            $data['url'] = $url;
        }
        $this->ajax($data);
    }

    //ajax的快捷方法
    public function error($message, $url = null)
    {
        $data = [
            'code' => 0,
            'msg' => $message
        ];
        if (!is_null($url)) {
            $data['url'] = $url;
        }
        $this->ajax($data);
    }

    //重定向
    public function redirect($url)
    {
        if (!headers_sent()) {
            header('Location: ' . $url);
            exit;
        } else {
            exit('<meta http-equiv="Refresh" content="0;URL=' . $url . '">');
        }
    }

    //运行APP
    public function run()
    {
        //获取 action
        if (isset($_SERVER['PATH_INFO'])) {
            if ($_SERVER['PATH_INFO'] == '') {
                $action = '/';
            } else {
                $action = $_SERVER['PATH_INFO'];
            }
        } else {
            $action = explode('&', $_SERVER['QUERY_STRING']);
            $action = empty($action[0]) || $_SERVER['QUERY_STRING'][0] != '/' ? '/' : $action[0];
        }

        //请求方式
        $method = $_SERVER['REQUEST_METHOD'];

        //路由查找
        $route_keys = array_keys($this->_routes[$method]);
        $flag = false;
        foreach ($route_keys as $route_key) {
            preg_match('#^' . $route_key . '$#is', $action, $all);
            if (!empty($all)) {
                //执行中间件中的处理函数
                foreach ($this->_middleware as $middleware) {
                    $middleware = $middleware->bindTo($this);
                    $middleware($route_key);
                }

                $handler = $this->_routes[$method][$route_key];
                if ($handler instanceof \Closure) {
                    $handler = $handler->bindTo($this);
                }
                $handler($all);
                $flag = true;
                break;
            }
        }
        if (false === $flag) {
            $this->_httpErrors['NotFound']();
        }
    }


}