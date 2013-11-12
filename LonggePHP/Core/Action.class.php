<?php

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