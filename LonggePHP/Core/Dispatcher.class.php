<?php

/**
 * 控制调度器
 * 先定义APP_PATH常量 制定应用程序目录
 * 默认模块目录 APP_PATH 目录下面的 Action目录
 * 默认控制器为IndexAction
 * 默认模块名为index
 * 默认参数分隔符为"/"，可选"-"
 * 默认入口文件为index.php
 * 模块的类名必须和文件同名，比如：IndexModule则它的文件名为IndexModule.class.php
 * 可通过setOption方法设置一些参数
 * pathinfo模式支持伪静态
 * 新增普通url模式
 * setOption参数说明
 * 传进去的是数组键值对形式的参数
 * 设置选项条件，可设置的有
 * MODULE_PATH=>查找模块目录的位置
 * DEFAULT_MODULE=>默认Module
 * DEFAULT_ACTION=>默认Action
 * DEBUG=>开启调试(true|false)
 * URL_MODEL=>路由模式(0:普通模式,1:pathinfo模式)
 * URL_DELIMITER=>参数分隔符 pathinfo模式使用
 * URL_HTML_SUFFIX=>'文件后缀' pathinfo模式伪静态使用
 * ENTRY_INDEX=>入口文件
 * URL_ROUTER_ON=>开启自定义路由
 * 普通URL模式U(模块名/操作名?参1=值1&参2=值2)
 * 路由模式U(路由名@?参1=值1&参2=值2)
 */
class Dispatcher {

    private static $instance;
    private static $_SGLOBAL; //调度配置
    private static $route = array(); //泛路由

    private function __construct() {
        self::initConfig();
    }

    public static function getInstance() {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __clone() {

    }

    /**
     * 运行控制器
     */
    public function run() {
        $route = array();
        if (self::$_SGLOBAL['URL_MODEL'] == 1) {
            $route = $this->pathInfoRoute();
        } else {
            $route = $this->generalRoute();
        }
        $modulefile = self::$_SGLOBAL['MODULE_PATH'] . "/{$route['module']}.class.php";

        if (file_exists($modulefile)) {
            include $modulefile;
            if (class_exists($route['module'])) {
                $class = new $route['module'];
                if (method_exists($class, $route['action'])) {
                    call_user_func(array(&$class, $route['action']));
                }else
                    die("in <b>{$route['module']}</b> module no this <b>{$route['action']}</b> action");
            }else
                die("no this <b>{$route['module']}</b> module1");
        }else {
            die("no this <b>{$route['module']}</b> module2");
        }
        self::$_SGLOBAL['endtime'] = microtime(true);
        $this->debugInfo();
    }

    /**
     * 输出调试信息
     */
    private function debugInfo() {
        if (self::$_SGLOBAL['DEBUG']) {
            $exectime = self::$_SGLOBAL['endtime'] - self::$_SGLOBAL['starttime'];
            $debuginfo = <<<HTML
            <style type="text/css">
            .dispatcher_debug_table th,.dispatcher_debug_table td{padding:5px;}
            .dispatcher_debug_table th{
            border-top:1px solid red;
            border-left:1px solid red;
            background-color:#ccc;
            }
            .dispatcher_debug_table td{
            border-top:1px solid red;
            border-left:1px solid red;
            border-right:1px solid red;
            }
.dispatcher_debug_table_last td,.dispatcher_debug_table_last th{
            border-bottom:1px solid red;
            }
            .dispatcher_debug_table_title{border-right:1px solid red;}

            </style>
 <table class="dispatcher_debug_table" cellpadding="0" cellspacing="0">
       <tr><th class="dispatcher_debug_table_title">Debug Info</th></tr>
   <tr>
   <th>Execute Time</th><td>$exectime s</td>
   </tr>
   <tr><th>Include File</th><td>
HTML;
            foreach (get_included_files () as $file) {
                $debuginfo.=$file . "<br/>";
            }
            $debuginfo.="<tr><th>Server Info</th><td>";
            $debuginfo.="Host:" . $_SERVER['HTTP_HOST'] . "<br/>";
            $debuginfo.="PHP_Version:" . PHP_VERSION . "<br/>";
            $debuginfo.="Server_Version:" . $_SERVER['SERVER_SOFTWARE'] . "<br/>";
            $debuginfo.="</td></tr>";
            $debuginfo.="<tr class='dispatcher_debug_table_last'><th>Client Info</th><td>";
            $debuginfo.="Remote_Addr:" . $_SERVER['REMOTE_ADDR'] . "<br/>";
            $debuginfo.="User_Agent:" . $_SERVER['HTTP_USER_AGENT'] . "<br/>";
            $debuginfo.="</td></tr>";
            $debuginfo.="</table>";
            echo $debuginfo;
        }
    }

    private function generalRoute() {
        $route = array();
        $route['module'] = !empty($_GET['m']) ? $_GET['m'] : self::$_SGLOBAL['DEFAULT_MODULE'];
        $route['action'] = !empty($_GET['a']) ? $_GET['a'] : self::$_SGLOBAL['DEFAULT_ACTION'];
        $route['module'].='Action';
        unset($_GET['m']);
        unset($_GET['a']);

        return $route;
    }

    /**
     * PATHINFO形式的路由调度
     * 支持伪静态
     */
    private function pathInfoRoute() {
        $route = array();
        //伪静态
        if (self::$_SGLOBAL['URL_HTML_SUFFIX']) {
            $pos = strlen($_SERVER['PATH_INFO']) - strlen(self::$_SGLOBAL['URL_HTML_SUFFIX']);
            $_SERVER['PATH_INFO'] = substr($_SERVER['PATH_INFO'], 0, $pos);
        }
        
        if (!isset($_SERVER['PATH_INFO']) || $_SERVER['PATH_INFO'] == '/') {
            $route = array(
                'module' => self::$_SGLOBAL['DEFAULT_MODULE'],
                'action' => self::$_SGLOBAL['DEFAULT_ACTION']
                    );
        } else {
            $_SERVER['PATH_INFO'] = substr($_SERVER['PATH_INFO'], 1);
            
            $pathinfo = explode(self::$_SGLOBAL['URL_DELIMITER'], $_SERVER['PATH_INFO']);
            
            //用户自定义路由
            if (self::$_SGLOBAL['URL_ROUTER_ON'] && in_array($pathinfo[0], array_keys(self::$route))) {
                echo 'aaaaaaaa';
                $route['module'] = self::$route[$pathinfo[0]][0];
                $route['action'] = self::$route[$pathinfo[0]][1];
                $c = explode(',', self::$route[$pathinfo[0]][2]);
                array_shift($pathinfo);
                foreach ($c as $r) {
                    $_GET[$r] = array_shift($pathinfo);
                }
            } else {
                if (count($pathinfo) < 2) {
                    
                    $route['module'] = $pathinfo[0];
                    $route['action'] = self::$_SGLOBAL['DEFAULT_ACTION'];
                } else {
                    $route['module'] = array_shift($pathinfo);
                    $route['action'] = array_shift($pathinfo);
                }
            }
            if (count($pathinfo) >= 2) {
                for ($i = 0, $cnt = count($pathinfo); $i < $cnt; $i++) {
                    if (isset($pathinfo[$i + 1])) {
                        $_GET[$pathinfo[$i]] = $pathinfo[++$i];
                    }
                }
            }
        }

        $route['module'] = !empty($route['module']) ? $route['module'] : self::$_SGLOBAL['DEFAULT_MODULE'];
        $route['action'] = !empty($route['action']) ? $route['action'] : self::$_SGLOBAL['DEFAULT_ACTION'];
        
        $route['module'].='Action';
        $_REQUEST = array_merge($_GET, $_POST);
        return $route;
    }

    /**
     * url地址组合
     * 格式为:Module/Action?get=par(模块名/动作名?get参数)
     * @param string $url
     * @return string $url
     */
    public static function U($url) {
        $pathinfo = parse_url($url);
        $path = '';
        $get = array();
        $inroute = false; //用户定义的路由
        if (isset($pathinfo['query'])) {
            $query = explode('&', $pathinfo['query']);
            foreach ($query as $q) {
                list($k, $v) = explode('=', $q);
                $get[$k] = $v;
            }
        }

        if (!self::$_SGLOBAL) {
            self::initConfig();
        }
        //pathinfo方式的url
        if (self::$_SGLOBAL['URL_MODEL'] == 1) {

            if (self::$_SGLOBAL['URL_ROUTER_ON'] && strpos($pathinfo['path'], '@') !== false) {
                //取出所有用户定义的路由
                $routeNames = array_keys(self::$route);
                $p = substr($pathinfo['path'], 0, -1);
                if (in_array($p, $routeNames)) {
                    $inroute = true;
                    $path.='/' . $p;
                    $c = explode(',', self::$route[$p][2]);
                    foreach ($c as $v) {
                        if (isset($get[$v])) {
                            $path.=self::$_SGLOBAL['URL_DELIMITER'] . $get[$v];
                            unset($get[$v]);
                        }
                    }
                }
            }
            if (!$inroute) {
                if (isset($pathinfo['path'])) {
                    list($module, $action) = explode('/', $pathinfo['path']);
                    $module = $module ? $module : self::$_SGLOBAL['DEFAULT_MODULE'];
                    $action = $action ? $action : self::$_SGLOBAL['DEFAULT_ACTION'];
                } else {
                    $module = self::$_SGLOBAL['DEFAULT_MODULE'];
                    $action = self::$_SGLOBAL['DEFAULT_ACTION'];
                }
                $path = "/$module" . self::$_SGLOBAL['URL_DELIMITER'] . $action;
            }
            if (!empty($get)) {
                foreach ($get as $k => $v) {
                    $path.=self::$_SGLOBAL['URL_DELIMITER'] . $k . self::$_SGLOBAL['URL_DELIMITER'] . $v;
                }
            }
            //url伪静态
            if (self::$_SGLOBAL['URL_HTML_SUFFIX']) {
                $path.=self::$_SGLOBAL['URL_HTML_SUFFIX'];
            }
        } elseif (self::$_SGLOBAL['URL_MODEL'] == 0) {
            $url = parse_url($url);
            if (isset($url['path'])) {
                list($module, $action) = explode('/', $url['path']);
                $module = $module ? $module : self::$_SGLOBAL['DEFAULT_MODULE'];
                $action = $action ? $action : self::$_SGLOBAL['DEFAULT_ACTION'];
            } else {
                $module = self::$_SGLOBAL['DEFAULT_MODULE'];
                $action = self::$_SGLOBAL['DEFAULT_ACTION'];
            }
            $path.="?m=$module&a=$action";
            if ($url['query']) {
                $path.='&' . $url['query'];
            }
        }
        if (!self::$_SGLOBAL['URL_REWRITE'])
            $path = '/' . self::$_SGLOBAL['ENTRY_INDEX'] . $path;
        
        return $path;
    }

    /**
     * 初始化配置信息
     */
    private static function initConfig() {
        if (defined('APP_PATH')) {
            //默认模块目录
            self::$_SGLOBAL['MODULE_PATH'] = APP_PATH . '/Action';
        }
        self::$_SGLOBAL['DEFAULT_ACTION'] = 'index'; //默认action
        self::$_SGLOBAL['DEFAULT_MODULE'] = 'Index'; //默认module
        //默认url路由模式，1：pathinfo模式，0为普通模式
        self::$_SGLOBAL['URL_MODEL'] = 1;
        self::$_SGLOBAL['URL_DELIMITER'] = '/'; //参数分隔符
        self::$_SGLOBAL['ENTRY_INDEX'] = 'index.php';
        self::$_SGLOBAL['URL_HTML_SUFFIX'] = null; //url伪静态
        self::$_SGLOBAL['URL_REWRITE'] = false; //URL重写
        self::$_SGLOBAL['starttime'] = microtime(true);
        self::$_SGLOBAL['URL_ROUTER_ON'] = false; //是否启用路由功能
        self::$_SGLOBAL['DEBUG'] = false;
    }

    /**
     * 设置选项条件，可设置的有
     * MODULE_PATH=>查找模块目录的位置
     * DEFAULT_MODULE=>默认Module
     * DEFAULT_ACTION=>默认Action
     * DEBUG=>开启调试(true|false)
     * URL_DELIMITER=>参数分隔符
     * URL_MODEL=>路由模式(0:普通模式,1:pathinfo模式)
     * URL_HTML_SUFFIX=>'文件后缀' pathinfo模式伪静态使用
     * ENTRY_INDEX=>入口文件
     * URL_ROUTER_ON=>开启自定义路由
     * @param array $option 选项
     */
    public function setOption($option) {
        $o = array('MODULE_PATH', 'DEFAULT_MODULE',
            'DEFAULT_ACTION', 'DEBUG',
            'URL_DELIMITER', 'URL_MODEL',
            'URL_HTML_SUFFIX', 'ENTRY_INDEX', 'URL_REWRITE', 'URL_ROUTER_ON');
        foreach ($option as $k => $v) {
            if (in_array($k, $o)) {
                self::$_SGLOBAL[$k] = $v;
            }
        }
    }

    /**
     * 设置路由
     * array('route'=>array('模块名称', '操作名称','参数1,参数2,参数3'))
     * @param array $route 路由
     */
    public function setRoute($route) {
        self::$route = $route;
    }

}

?>