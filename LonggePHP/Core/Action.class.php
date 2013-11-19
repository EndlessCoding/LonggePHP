<?php
/**
 * LonggePHP Action控制器基类 抽象类
 * @package  Core
 * @author   chenlong <1025194094@qq.com>
 * @version  $Id$
 * @abstract
 */

abstract class Action{

	private static $_template = null;

	public function __construct(){}

	public static function getTemplateObj()
	{
		if(self::$_template == null)
			self::$_template =  new Template();

		return self::$_template;
	}

	public function assign($name, $value)
	{
    	self::getTemplateObj()->assign($name, $value);
	}

	public function display($tFile)
	{
    	self::getTemplateObj()->display($tFile);
	}
}