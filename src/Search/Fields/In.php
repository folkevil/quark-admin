<?php

namespace QuarkCMS\QuarkAdmin\Search\Fields;

use Illuminate\Support\Arr;
use QuarkCMS\QuarkAdmin\Search\Item;

class In extends Item
{
    /**
     * 初始化
     *
     * @param  string  $name
     * @param  string  $label
     * @return void
     */
    public function __construct($name,$label = '') {
        $this->component = 'input';
        $this->name = $name;
        $this->operator = 'in';

        if(empty($label) || !count($label)) {
            $this->label = $name;
        } else {
            $label = Arr::get($label, 0, ''); //[0];
            $this->label = $label;
        }

        $this->placeholder = '请选择'.$this->label;

        $style['width'] = 157;
        $this->style = $style;
    }
}
