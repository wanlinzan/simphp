<?php

namespace simphp;
/**
 * 单文件上传类
 *
 * $upload = new Upload();
 * $upload->upload($_FILES['name'])
 * 错误通过异常抛出
 */

class Upload
{

    //上传文件保存目录
    private $uploadDir = '';

    //允许上传的文件后缀,空数组不做限制
    private $exts = array();

    //大小限制,0不做限制
    private $size = 0;

    //上传的目标文件数组
    private $uploadFile = null;

    public function __construct($uploadDir = '', $exts = array(), $size = 0)
    {
        $this->uploadDir = rtrim($uploadDir . '/') . '/';
        if (!file_exists($this->uploadDir)) {
            if (false === mkdir($this->uploadDir, 0700, true)) {
                throw new \Exception('目录创建失败');
            }
        }
        $this->exts = $exts;
        $this->size = $size;
    }

    /**
     * @param array $file
     * @param String $filename 不需要后缀,一般用在用户头像的上传,文件名就是用户的id
     * @return array
     * @throws \Exception
     * 给的是$_FILES['name'],必须是单文件上传模式的表单name
     */
    public function upload($file, $filename = null)
    {
        $this->uploadFile = $file;

        //检查是否上传到temp目录,这个必须作为第一个判断的
        $this->checkError();

        //截取文件后缀并判断
        $this->checkExt();

        //检查大小
        $this->checkSize();

        //是图片类型，但是获取不到文件大小,黑客行为
        $this->checkException();

        //生成文件名
        if (!is_null($filename)) {
            $new_name = $filename . '.' . $this->uploadFile['ext'];
        } else {
            $new_name = str_replace('.', '', uniqid('', true)) . '.' . $this->uploadFile['ext'];
        }


        if (!move_uploaded_file($this->uploadFile['tmp_name'], $this->uploadDir . $new_name)) {
            throw new \Exception('文件上传失败');
        }

        return array(
            'new_name' => $new_name,
            'old_name' => $this->uploadFile['name'],
            'size' => $this->uploadFile['size']
        );
    }


    //检查错误
    private function checkError()
    {
        $error = array(
            UPLOAD_ERR_INI_SIZE => '上传文件超过PHP.INI配置文件允许的大小',
            UPLOAD_ERR_FORM_SIZE => '文件超过表单限制大小',
            UPLOAD_ERR_PARTIAL => '文件只有部分上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败'
        );

        if (array_key_exists($this->uploadFile['error'], $error)) {
            throw new \Exception($error[$this->uploadFile['error']]);
        }
        return $this;
    }

    //检查文件的后缀
    private function checkExt()
    {
        $temp_array = explode('.', $this->uploadFile['name']);
        $this->uploadFile['ext'] = strtolower(array_pop($temp_array));
        if (!empty($this->exts) && !in_array($this->uploadFile['ext'], $this->exts)) {
            throw new \Exception('文件后缀不允许上传');
        }
        return $this;
    }


    //检查文件的大小
    private function checkSize()
    {
        if ($this->size != 0 && $this->uploadFile['size'] > $this->size) {
            throw new \Exception('文件大小超过限制');
        }
        return $this;
    }


    //检查异常
    private function checkException()
    {
        if (strstr(strtolower($this->uploadFile['type']), "image") && !getimagesize($this->uploadFile['tmp_name'])) {
            throw new \Exception('图片不合法');
        }
        if (!is_uploaded_file($this->uploadFile['tmp_name'])) {
            throw new \Exception('非法文件');
        }
        return $this;
    }

}