<?php
namespace simphp;
/**
 * 注意：嵌套的模板要删除主模板缓存文件才能更新
 */
class View
{

    /**
     * @var array 目录配置
     */
    private $_config = [
        'dir' => './',
        'cache_dir' => './',
        'postfix' => '.html'
    ];

    /**
     * 模板变量
     * @var array
     */
    private $_var = [];


    public function __construct($config = [])
    {
        $this->_config = array_merge($this->_config, $config);
        //自动创建模板缓存目录
        if (!is_dir($this->_config['cache_dir'])) {
            if (false === mkdir($this->_config['cache_dir'], 0777, true)) {
                throw new \Exception('文件夹权限不足，创建模板缓存目录失败');
            }
        }
    }

    /**
     * 模板变量赋值
     * @param $name
     * @param $value
     */
    public function assign($name, $value)
    {
        $this->_var[$name] = $value;
    }

    /**
     * 生成缓存文件
     * @param $filename
     * @return string
     */
    public function create($filename)
    {
        $templateFilename = $this->_config['dir'] . $filename . $this->_config['postfix'];
        $cacheTemplateFilename = $this->_config['cache_dir'] . md5($filename) . $this->_config['postfix'];
        file_exists($templateFilename) or exit('模板文件不存在：' . $templateFilename);
        if (!file_exists($cacheTemplateFilename) || filemtime($cacheTemplateFilename) < filemtime($templateFilename)) {

            $content = $this->getFileContent($templateFilename);

            $content = $this->stripNote($content);

            $content = $this->explainTag($content);

            $content = $this->compress($content);

            file_put_contents($cacheTemplateFilename, $content);
        }
        return $cacheTemplateFilename;
    }

    /**
     * 渲染模板
     * @param $filename
     */
    public function display($filename)
    {
        $filename = $this->create($filename);
        //导入变量到当前符号表
        extract($this->_var);
        include $filename;
    }

    /**
     * 获取变量渲染后的字符串
     * @param $filename
     * @return string
     */
    public function fetch($filename)
    {
        ob_start();
        $this->display($filename);
        return ob_get_clean();
    }


    /**
     * 获取模板文件的内容
     * @param null $tplFile
     * @return mixed|string
     */
    private function getFileContent($tplFile = null)
    {
        //抓取内容
        $tplContent = file_get_contents($tplFile);
        //匹配包含的文件名
        preg_match_all('#<{include\s+(\w+?)\s*}>#', $tplContent, $includes);
        //如果存在包含，递归获取
        if (count($includes[1]) > 0) {
            $replacement = array();
            foreach ($includes[1] as $v) {
                $tempPath = $this->_config['dir'] . $v . $this->_config['postfix'];
                file_exists($tempPath) or exit('模板文件不存在：' . $tempPath);
                $replacement[] = $this->getFileContent($tempPath);
            }
            return str_replace($includes[0], $replacement, $tplContent);
        }
        return $tplContent;
    }

    /**
     * 解析模板标签
     * @param $content
     * @return mixed
     */
    private function explainTag($content)
    {
        $pattern = array(
            '#<{\$(.*?)}>#s',//输出变量
            '#<{\|(.*?)}>#s',//输出返回值
            '#<{foreach\((.*?)\)}>#s',//解析foreach
            '#<{/foreach}>#s',//foreach结束标签
            '#<{for\((.*?)\)}>#s',//标签for
            '#<{/for}>#s',//for结束标签
            '#<{if\((.*?)\)}>#s',//标签if
            '#<{else if\((.*?)\)}>#s',//else if标签
            '#<{/if}>#s',//if结束标签
            '#<{else}>#s',//if结束标签
            '#__ROOT__#',
            '#__VIEW__#',
            '#__UPLOAD__#',
        );
        $replacement = array(
            '<?php echo $${1};?>',
            '<?php echo ${1};?>',
            '<?php foreach(${1}){?>',
            '<?php } ?>',
            '<?php for(${1}){?>',
            '<?php } ?>',
            '<?php if(${1}){?>',
            '<?php }else if(${1}){?>',
            '<?php } ?>',
            '<?php }else{ ?>',
            '<?php echo ${0} ?>',
            '<?php echo ${0} ?>',
            '<?php echo ${0} ?>',
        );
        return preg_replace($pattern, $replacement, $content);
    }

    /**
     * 去除注释html网页注释
     * @param $content
     * @return mixed
     */
    public function stripNote($content)
    {
        $patten = array(
            '#\<!--.*?--\>#s'
        );
        return preg_replace($patten, '', $content);
    }

    /**
     * 去除网页冗余代码 ?><?php
     * @param $content
     * @return string
     */
    public function compress($content)
    {
        $patten = array(
            '#\?\>\s*\<\?php#s',
            //'#(\r|\n|\r\n)#s'
        );
        return preg_replace($patten, '', $content);
    }
}