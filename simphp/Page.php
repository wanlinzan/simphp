<?php
namespace simphp;

class Page
{

    //记录总条数
    private $count = 0;

    //总页数
    private $pageTotal = 0;

    //当前页码
    private $pageCurrent = 1;

    //每页显示的记录条数
    private $pageSize = 10;

    //查询数据用的limit
    private $limit = '';

    //href链接的模板
    private $hrefTpl = 'index.php?page=%s';

    //显示多少个页码
    private $hrefSize = 5;

    //分页类操作的字符串(核心)
    private $hrefStr = '';

    /**
     * @var array
     * 模板
     *                     <li><a href="#"><i class="fa-angle-left"></i></a></li>
     * <li><a href="#">1</a></li>
     * <li class="active"><a href="#">2</a></li>
     * <li><a href="#">3</a></li>
     * <li class="disabled"><a href="#">4</a></li>
     * <li><a href="#">5</a></li>
     * <li><a href="#">6</a></li>
     * <li><a href="#"><i class="fa-angle-right"></i></a></li>
     */
    private $tpl = array(
        'first' => '<li><a href="%s"><i class="fa-angle-left"></i></a></li>',
        'prev' => '<li><a href="%s"><i class="fa-angle-left"></i></a></li>',
        'current' => '<li class="disabled"><a href="%s">%s</a></li>',
        'next' => '<li><a href="%s"><i class="fa-angle-right"></i></a></li>',
        'last' => '<li><a href="%s"><i class="fa-angle-right"></i></a></li>',
        'default' => '<li><a href="%s">%s</a></li>'
    );


    public function __construct($hrefTpl = '', $count = 0, $pageCurrent = 1, $pageSize = 10, $hrefSize = 5)
    {
        $this->count = $count;
        $this->pageSize = max($pageSize, 1);
        //计算总页数
        $this->pageTotal = ceil($this->count / $this->pageSize);
        //修正currentPage
        $this->pageCurrent = max(1, min($this->pageTotal, $pageCurrent));
        //计算limit
        $this->limit = $this->pageSize * ($this->pageCurrent - 1) . ',' . $this->pageSize;
        $this->hrefTpl = $hrefTpl;
        $this->hrefSize = $hrefSize;
    }

    public function show()
    {
        $this->first()->prev()->current()->next()->last();
//        $this->prev()->current()->next();
        return $this->hrefStr;
    }

    //获取数据库查询的limit
    public function limit()
    {
        return $this->limit;
    }

    public function tpl($tpl)
    {
        $this->tpl = $tpl;
        return $this;
    }


    private function first()
    {
        if ($this->pageCurrent >= 2) {
            $href = sprintf($this->hrefTpl, 1);
            $this->hrefStr .= sprintf($this->tpl['first'], $href);
        }
        return $this;
    }

    private function prev()
    {
        //还没有到第一页,也就是还有上一页
        if ($this->pageCurrent >= 2) {
            $href = sprintf($this->hrefTpl, $this->pageCurrent - 1);
            $this->hrefStr .= sprintf($this->tpl['prev'], $href);
        }
        return $this;
    }

    private function current()
    {
        $i = max(1, $this->pageCurrent - floor($this->hrefSize / 2));
        $end = min($i + $this->hrefSize, $this->pageTotal);

        for (; $i <= $end; $i++) {
            if ($i == $this->pageCurrent) {
                $href = sprintf($this->hrefTpl, $this->pageCurrent);
                $this->hrefStr .= sprintf($this->tpl['current'], $href, $this->pageCurrent);
            } else {
                $href = sprintf($this->hrefTpl, $i);
                $this->hrefStr .= sprintf($this->tpl['default'], $href, $i);
            }
        }

        if (empty($this->hrefStr)) {
            $href = sprintf($this->hrefTpl, $this->pageCurrent);
            $this->hrefStr .= sprintf($this->tpl['current'], $href, $this->pageCurrent);
        }

        return $this;
    }

    private function next()
    {
        if ($this->pageCurrent < $this->pageTotal) {
            $href = sprintf($this->hrefTpl, $this->pageCurrent + 1);
            $this->hrefStr .= sprintf($this->tpl['next'], $href, $this->pageCurrent + 1);
        }
        return $this;
    }

    private function last()
    {
        if ($this->pageCurrent < $this->pageTotal) {
            $href = sprintf($this->hrefTpl, $this->pageTotal);
            $this->hrefStr .= sprintf($this->tpl['last'], $href);
        }
        return $this;
    }


}