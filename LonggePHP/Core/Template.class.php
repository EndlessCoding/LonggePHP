<?php
/**
 * LonggePHP 模板类
 * @package  Core
 * @author   chenlong <1025194094@qq.com>
 * @version  $Id$
 */

class Template {
    
    //private $_tpl_engine = '';

    private $_smarty = null;

    public function __construct() {
        $this->_smarty = $this->getSmarty();
    }
    
    /**
     * 获取Smarty对象实例
     */
    private function getSmarty()
    {
        if($this->_smarty == null){
            $smarty_path = LONGGE_PATH . 'Drive/TemplateEngine/Smarty-3.1.14/libs/Smarty.class.php';
            require_once $smarty_path;
            $this->_smarty = new Smarty();
            $this->_smarty->template_dir = APP_PATH. 'Tpl/' . v('Default_Tpl');
            $this->_smarty->compile_dir = APP_PATH. 'Runtime/cache_tpl';
            $this->_smarty->caching = v('Template_Engine_cache');
            $this->_smarty->debugging = v('Debug');
            $this->_smarty->cache_lifetime = -1;
            $this->_smarty->left_delimiter = v('Left_Delimiter');
            $this->_smarty->right_delimiter = v('Right_Delimiter');
        }
        //dump($this->_smarty);
        return $this->_smarty;
    }

    /**
     * 变量赋值
     * @param mixed $name
     * @param mixed $value
     */
    public function assign($name, $value) {
        $this->_smarty->assign($name, $value);
    }

    /**
     * 运行并显示模版内容
     * @param string $tfile
     */
    public function display($tFile) {
        //dump(APP_PATH. 'Tpl/' . v('Default_Tpl') . '/' . $tFile);
        //$tFile = str_replace("\\","/", $tFile);

        //$tFile = APP_PATH. 'Tpl/' . v('Default_Tpl') . '/' . $tFile;
        $this->_smarty->display($tFile);
    }

}

?>
