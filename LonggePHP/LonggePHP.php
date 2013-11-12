<?php

require_once LONGGE_PATH. 'Core/Dispatcher.class.php';
require_once LONGGE_PATH .'Core/Template.class.php';
require_once LONGGE_PATH . 'Core/Action.class.php';
require_once LONGGE_PATH . 'Common/Common.php';

header('Content-Type:text/html; charset=utf-8');

//if(!defined(APP_PATH))
	//define('APP_PATH',str_replace('\\','/', dirname(dirname(__FILE__))));

$dispatcher = Dispatcher::getInstance();

//系统配置文件
$option = require LONGGE_PATH . 'Conf/Conf.php';

//项目配置文件
$app_option = require APP_PATH . 'Conf/Conf.php';

$option = array_merge($option, $app_option);

//自定义泛路由
//$router = array('space'=>array('Space', 'index','uid'));

$dispatcher->setOption($option);
//$dispatcher->setRoute($router);


create_app_dir();

//创建项目目录
function create_app_dir()
{
    // 没有创建项目目录的话自动创建
    if(!is_dir(APP_PATH)) 
    	mkdir(APP_PATH,0755,true);
    if(is_writeable(APP_PATH)) {
        $dirs  = array(
            //LIB_PATH,
            //RUNTIME_PATH,
            //CONF_PATH,
            //COMMON_PATH,
            //LANG_PATH,
            //CACHE_PATH,
            //TMPL_PATH,
            //TMPL_PATH.C('DEFAULT_THEME').'/',
            //LOG_PATH,
            //TEMP_PATH,
            //DATA_PATH,
            APP_PATH.'Model/',
            APP_PATH.'Common/',
            APP_PATH.'Action/',
            APP_PATH.'Conf/',
            APP_PATH.'Tpl/',
            APP_PATH.'Runtime/',
            //LIB_PATH.'Behavior/',
            //LIB_PATH.'Widget/',
            );
        foreach ($dirs as $dir){
            if(!is_dir($dir))  mkdir($dir,0755,true);
        }
        // 写入目录安全文件
        //build_dir_secure($dirs);
        // 写入初始配置文件
        if(!is_file(APP_PATH.'Common/Common.php'))
            file_put_contents(APP_PATH.'Common/Common.php',"<?php\n//项目公共函数\n");

        if(!is_file(APP_PATH.'Conf/config.php'))
            file_put_contents(APP_PATH.'Conf/Conf.php',"<?php\nif(!defined('LONGGE_PATH'))exit();\nreturn array(\n\t//'配置项'=>'配置值'\n);\n?>");
        // 写入测试Action
        if(!is_file(APP_PATH.'Action/IndexAction.class.php')){
		    $content = file_get_contents(LONGGE_PATH.'Tpl/default_index.tpl');
		    file_put_contents(APP_PATH.'Action/IndexAction.class.php',$content);
        }
    }else{
        header('Content-Type:text/html; charset=utf-8');
        exit('项目目录不可写，目录无法自动生成！<BR>请使用项目生成器或者手动生成项目目录~');
    }

}

Template::setU($dispatcher);
Template::setReal(true);
Template::setTemplateDir(APP_PATH . 'Tpl/default');
Template::setTmpDir(APP_PATH . 'Runtime/');

$dispatcher->run();
