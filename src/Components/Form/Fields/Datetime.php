<?php

namespace QuarkCMS\QuarkAdmin\Form\Fields;

use QuarkCMS\QuarkAdmin\Form\Item;
use Illuminate\Support\Arr;
use Exception;

class Datetime extends Item
{
    public  $format,
            $showTime;

    function __construct($name,$label = '') {
        $this->component = 'datetime';
        $this->name = $name;

        if(empty($label) || !count($label)) {
            $this->label = $name;
        } else {
            $label = Arr::get($label, 0, ''); //[0];
            $this->label = $label;
        }

        $this->format = 'YYYY-MM-DD HH:mm:ss';
        $showTime['format'] = 'HH:mm:ss';
        $this->showTime = $showTime;
    }

    /**
     * 创建组件
     *
     * @param  string $name
     * @param  string $label
     * @return object
     */
    static function make($name,$label = '')
    {
        $self = new self();

        $self->name = $name;
        if(empty($label)) {
            $self->label = $name;
        } else {
            $self->label = $label;
        }

        // 删除空属性
        $self->unsetNullProperty();
        return $self;
    }

    public function showTime($showTime)
    {
        $this->showTime = $showTime;
        return $this;
    }

    public function format($format)
    {
        $this->format = $format;
        return $this;
    }
}