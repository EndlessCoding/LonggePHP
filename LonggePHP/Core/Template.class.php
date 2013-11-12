<?php

/**
 * {loop $array $key $value}..........{/loop} 循环
 * {loop $array $value}..........{/loop} 循环
 * {if condition}...{elseif condition}..{else}..{/if} if条件语句
 * {$val} 输出变量值
 * {eval echo "ok";} 运行PHP代码
 * {template file} 包含另外一个模版
 */
class Template {

    private static $tDir; //模版文件目录
    private static $tTmpDir; //编译好后的文件目录
    private $tVal;  //模版变量
    private $tFile; //模版文件
    private $tContent; //模版内容
    private static $uDispatcher; //URL调度器
    private static $real = false; //实时编译

    public function __construct() {
        $this->tVal = array();
    }

    /**
     * 设置模版文件目录
     * @param string $dir
     */
    public static function setTemplateDir($dir) {
        self::$tDir = $dir;
    }

    /**
     * 是否实时编译
     * @param bool $real
     */
    public static function setReal($real) {
        self::$real = (bool) $real;
    }

    /**
     * 临时文件目录
     * @param string $dir
     */
    public static function setTmpDir($dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0, true))
                die("tmp dir $dir can't to mkdir");
        }
        self::$tTmpDir = realpath($dir);
    }

    /**
     * URL调度器
     * @param Dispatcher $dispatcher
     */
    public static function setU(&$dispatcher) {
        if (is_object($dispatcher) && method_exists($dispatcher, 'U')) {
            self::$uDispatcher = $dispatcher;
        }
    }

    /**
     * 变量赋值
     * @param mixed $name
     * @param mixed $value
     */
    public function assign($name, $value) {
        $this->tVal[$name] = $value;
    }

    /**
     * 取得模版的变量
     * @param string $name
     */
    public function getVal($name) {
        if (isset($this->tVal[$name])) {
            return $this->tVal[$name];
        }else
            return false;
    }

    /**
     * 将运行好后的内容，保存到一个html文件中
     * @param string $tFile
     * @param string $html
     */
    public function saveHtml($tFile, $html) {
        ob_start();
        $this->display($tFile);
        $buffer = ob_get_contents();
        ob_end_clean();
        file_put_contents($html, $buffer);
    }

    /**
     * 运行并显示模版内容
     * @param string $tfile
     */
    public function display($tFile) {
        $this->tFile = $this->parseTemplatePath($tFile);
        if (!self::$real) {
            if (!file_exists($this->getTmpFile()))
                $this->parse();
            elseif ((filemtime($this->tFile) > filemtime($this->getTmpFile())))
                $this->parse();
        }else
            $this->parse();
        extract($this->tVal, EXTR_OVERWRITE);

        include $this->getTmpFile();
    }

    /**
     * 编译好后的文件
     * @return string $filepath
     */
    private function getTmpFile() {
        $basename = basename($this->tFile);
        $pos = strrpos($basename, '.');
        $tmp = 'tpl_' . substr($basename, 0, $pos) . '.php';
        return self::$tTmpDir . '/' . $tmp;
    }

    private function parse() {
        $this->tContent = file_get_contents($this->tFile);
        $this->parseInclude();
        $this->parseSection();
        $this->parseVal();
        $this->parseEval();
        file_put_contents($this->getTmpFile(), $this->tContent);
    }

    private function parseInclude() {
        $this->tContent = preg_replace("/\{template\s+([a-zA-z0-9\._]+)\}/ies", "\$this->subtemplate('$1')", $this->tContent);
    }

    /**
     * 获取只模版
     * @param string $file
     */
    private function subtemplate($file) {
        return file_get_contents($this->parseTemplatePath($file));
    }

    /**
     * 解析模版路径
     * @param string $file
     * @return string $filepath
     */
    private function parseTemplatePath($tFile) {
        $tFile.='.html';
        $tFile = self::$tDir ? self::$tDir . '/' . $tFile : $tFile;
        if (!file_exists($tFile)) {
            die("No template file $tFile");
        } else {
            $tFile = realpath($tFile);
        }
        return $tFile;
    }

    /**
     * 解析变量
     */
    private function parseVal() {
        $this->tContent = preg_replace("/\{(\\$\S+?)\}/is", "<?php echo \\1 ;?>", $this->tContent);
    }

    /**
     * 解析段落
     */
    private function parseSection() {
        //逻辑
        $this->tContent = preg_replace("/\{elseif\s+(.+?)\}/ies", "\$this->stripvtags('<?php } elseif(\\1) { ?>','')", $this->tContent);
        $this->tContent = preg_replace("/\{else\}/is", "<?php } else { ?>", $this->tContent);
        $this->tContent = preg_replace("/\{U\((.+?)\)\}/ies", "\$this->parseUrl('$1')", $this->tContent);
        //循环
        for ($i = 0; $i < 6; $i++) {
            $this->tContent = preg_replace("/\{loop\s+(\S+)\s+(\S+)\}(.+?)\{\/loop\}/ies", "\$this->stripvtags('<?php if(is_array(\\1)) { foreach(\\1 as \\2) { ?>','\\3<?php } } ?>')", $this->tContent);
            $this->tContent = preg_replace("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}(.+?)\{\/loop\}/ies", "\$this->stripvtags('<?php if(is_array(\\1)) { foreach(\\1 as \\2 => \\3) { ?>','\\4<?php } } ?>')", $this->tContent);
            $this->tContent = preg_replace("/\{if\s+(.+?)\}(.+?)\{\/if\}/ies", "\$this->stripvtags('<?php if(\\1) { ?>','\\2<?php } ?>')", $this->tContent);
        }
    }

    private  function stripvtags($expr, $statement='') {
        $expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
        $statement = str_replace("\\\"", "\"", $statement);
        return $expr . $statement;
    }

    /**
     * 解析PHP语句
     */
    private function parseEval() {
        $this->tContent = preg_replace("/\{eval\s+(.+?)\}/is", "<?php $1 ?>", $this->tContent);
    }

    /**
     * 解析URL
     */
    private function parseUrl($url) {
        if (is_object(self::$uDispatcher)) {
            return self::$uDispatcher->U($url);
        } else {
            return $url;
        }
    }

}

?>