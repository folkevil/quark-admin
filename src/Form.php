<?php

namespace QuarkCMS\QuarkAdmin;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use QuarkCMS\QuarkAdmin\Helper;
use Closure;
use Validator;

class Form
{
    public $form;

    public $model;

    public $request;

    /**
     * Available fields.
     *
     * @var array
     */
    public static $availableFields = [
        'id' => Form\Fields\ID::class,
        'text' => Form\Fields\Text::class,
        'radio' => Form\Fields\Radio::class,
        'image' => Form\Fields\Image::class,
        'tree' => Form\Fields\Tree::class,
    ];

    /**
     * Create a new form instance.
     *
     * @param $model
     * @param \Closure $callback
     */
    public function __construct($model = null)
    {
        $this->model = $model;
        $layout['labelCol']['span'] = 3;
        $layout['wrapperCol']['span'] = 21;
        $this->form['layout'] = $layout;

        // 设置默认表单行为
        $this->setDefaultAction();

        // 初始化表单数据
        $this->initRequestData();
    }

    /**
     * form title.
     *
     * @param string $url
     *
     * @return bool|mixed
     */
    public function title($title)
    {
        $this->form['title'] = $title;
        return $this;
    }

    /**
     * form layout.
     *
     * @param string $url
     *
     * @return bool|mixed
     */
    public function layout($layout)
    {
        $this->form['layout'] = $layout;
        return $this;
    }

    /**
     * form disableSubmit.
     *
     * @return bool
     */
    public function disableSubmit()
    {
        $this->form['disableSubmit'] = true;
        return $this;
    }

    /**
     * form disableReset.
     *
     * @return bool
     */
    public function disableReset()
    {
        $this->form['disableReset'] = true;
        return $this;
    }

    /**
     * form default action.
     *
     *
     * @return bool|mixed
     */
    protected function setDefaultAction()
    {
        $action = \request()->route()->getName();
        $action = Str::replaceFirst('api/','',$action);
        if($this->isCreating()) {
            $this->form['action'] = Str::replaceLast('/create','/store',$action);
        }

        if($this->isEditing()) {
            $action = 
            $this->form['action'] = Str::replaceLast('/edit','/update',$action);
        }
    }

    /**
     * form default action.
     *
     *
     * @return bool|mixed
     */
    protected function initRequestData()
    {
        if(Str::endsWith(\request()->route()->getName(), ['/store', '/update'])) {
            $data = request()->all();
            unset($data['actionUrl']);
            $this->request = $data;
        }
    }

    /**
     * form ajax.
     *
     * @param string $ajax
     *
     * @return bool|mixed
     */
    public function ajax($url)
    {
        $this->form['url'] = $url;
        return $this;
    }

    /**
     * form action.
     *
     * @param string $url
     *
     * @return bool|mixed
     */
    public function setAction($url)
    {
        $this->form['action'] = $url;
        return $this;
    }

    /**
     * form store.
     *
     * @return bool
     */
    public function store()
    {
        $data = $this->request;

        foreach ($this->form['items'] as $key => $value) {
            if($value->rules) {
                $rules[$value->name] = $value->rules;
                $validator = Validator::make($data,$rules,$value->ruleMessages);
                if ($validator->fails()) {
                    $errors = $validator->errors()->getMessages();
                    foreach($errors as $key => $value) {
                        $errorMsg = $value[0];
                    }

                    return Helper::error($errorMsg);
                }
            }

            if($value->creationRules) {
                $creationRules[$value->name] = $value->creationRules;
                $validator = Validator::make($data,$creationRules,$value->creationRuleMessages);
                if ($validator->fails()) {
                    $errors = $validator->errors()->getMessages();
                    foreach($errors as $key => $value) {
                        $errorMsg = $value[0];
                    }
                    
                    return Helper::error($errorMsg);
                }
            }
        }

        $result = $this->model->create($data);

        if($result) {
            return Helper::success('操作成功！','',$result);
        } else {
            return Helper::error('操作失败！');
        }
    }

    /**
     * form edit.
     *
     * @return bool
     */
    public function edit($id)
    {
        $data = $this->model->findOrFail($id);

        foreach ($this->form['items'] as $key => $item) {
            if($item->component == 'image') {
                $image['id'] = $data[$item->name];
                $image['name'] = Helper::getPicture($data[$item->name],0,'name');
                $image['size'] = Helper::getPicture($data[$item->name],0,'size');
                $image['url'] = Helper::getPicture($data[$item->name],0,'path');
                $data[$item->name] = $image;
            }
        }

        $this->form['data'] = $data;

        return $this;
    }

    /**
     * form update.
     *
     * @return bool
     */
    public function update()
    {
        $data = $this->request;

        foreach ($this->form['items'] as $key => $value) {
            if($value->rules) {

                foreach ($value->rules as &$rule) {
                    if (is_string($rule)) {
                        $rule = str_replace('{{id}}', $data['id'], $rule);
                    }
                }

                $rules[$value->name] = $value->rules;
                $validator = Validator::make($data,$rules,$value->ruleMessages);
                if ($validator->fails()) {

                    $errors = $validator->errors()->getMessages();
                    foreach($errors as $key => $value) {
                        $errorMsg = $value[0];
                    }

                    return Helper::error($errorMsg);
                }
            }

            if($value->updateRules) {

                foreach ($value->updateRules as &$rule) {
                    if (is_string($rule)) {
                        $rule = str_replace('{{id}}', $data['id'], $rule);
                    }
                }

                $updateRules[$value->name] = $value->updateRules;
                $validator = Validator::make($data,$updateRules,$value->updateRuleMessages);
                if ($validator->fails()) {
                    $errors = $validator->errors()->getMessages();
                    foreach($errors as $key => $value) {
                        $errorMsg = $value[0];
                    }
                    
                    return Helper::error($errorMsg);
                }
            }
        }

        // 清除空数据
        foreach($data as $key => $value) {
            if($value == '') {
                unset($data[$key]);
            }
        }

        $result = $this->model->where('id',$data['id'])->update($data);

        if($result) {
            return Helper::success('操作成功！','',$result);
        } else {
            return Helper::error('操作失败！');
        }
    }

    /**
     * form destroy.
     *
     * @return bool
     */
    public function destroy()
    {
        $id = request('id');

        if(empty($id)) {
            return $this->error('参数错误！');
        }

        $result = $this->model->destroy($id);
        return $result;
    }

    /**
     * Indicates if current form page is creating.
     *
     * @return bool
     */
    public function isCreating(): bool
    {
        return Str::endsWith(\request()->route()->getName(), ['/create', '/store']);
    }

    /**
     * Indicates if current form page is editing.
     *
     * @return bool
     */
    public function isEditing(): bool
    {
        return Str::endsWith(\request()->route()->getName(), '/edit', '/update');
    }

    /**
     * 保存前回调
     *
     * @return bool
     */
    public function saving(Closure $callback = null)
    {
        $callback($this);
    }

    /**
     * Find field class.
     *
     * @param string $method
     *
     * @return bool|mixed
     */
    public static function findFieldClass($method)
    {
        $class = Arr::get(static::$availableFields, $method);

        if (class_exists($class)) {
            return $class;
        }

        return false;
    }

    public function __call($method, $arguments)
    {
        if ($className = static::findFieldClass($method)) {

            $column = Arr::get($arguments, 0, ''); //[0];
            $element = new $className($column, array_slice($arguments, 1));
            $this->form['items'][] = $element;

            return $element;
        }
    }

    /**
     * 解析成前端验证规则
     *
     * @param array $rules
     *
     * @return array
     */
    protected function parseRules($rules,$messages)
    {
        $result = false;

        foreach ($rules as $key => $value) {

            if(strpos($value,':') !== false) {
                $arr = explode(':',$value);
                $rule = $arr[0];
            } else {
                $rule = $value;
            }

            $data = false;

            switch ($rule) {
                case 'required':
                    // 必填
                    $data['required'] = true;
                    $data['message'] = $messages['required'];
                    break;

                case 'min':
                    // 最小字符串数
                    $data['min'] =  (int)$arr[1];
                    $data['message'] = $messages['min'];
                    break;

                case 'max':
                    // 最大字符串数
                    $data['max'] =  (int)$arr[1];
                    $data['message'] = $messages['max'];
                    break;

                case 'email':
                    // 必须为邮箱
                    $data['type'] = 'email';
                    $data['message'] = $messages['email'];
                    break;

                case 'numeric':
                    // 必须为数字
                    $data['type'] = 'number';
                    $data['message'] = $messages['numeric'];
                    break;

                case 'url':
                    // 必须为url
                    $data['type'] = 'url';
                    $data['message'] = $messages['url'];
                    break;

                case 'integer':
                    // 必须为整数
                    $data['type'] = 'integer';
                    $data['message'] = $messages['integer'];
                    break;

                case 'date':
                    // 必须为日期
                    $data['type'] = 'date';
                    $data['message'] = $messages['date'];
                    break;

                case 'boolean':
                    // 必须为布尔值
                    $data['type'] = 'boolean';
                    $data['message'] = $messages['boolean'];
                    break;

                default:
                    $data = false;
                    break;
            }

            if($data) {
                $result[] = $data;
            }
        }

        return $result;
    }

    /**
     * 设置前端验证规则
     *
     * @param array $rules
     *
     * @return array
     */
    protected function setFrontendRules()
    {
        if(isset($this->form['items'])) {
            foreach ($this->form['items'] as $key => $item) {
                $frontendRules = [];
                $rules = false;
                $creationRules = false;
                $updateRules = false;
    
                if(!empty($item->rules)) {
                    $rules = $this->parseRules($item->rules,$item->ruleMessages);
                }
    
                if($this->isCreating() && !empty($item->creationRules)) {
                    $creationRules = $this->parseRules($item->creationRules,$item->creationRuleMessages);
                }
    
                if($this->isEditing() && !empty($item->updateRules)) {
                    $updateRules = $this->parseRules($item->updateRules,$item->updateRuleMessages);
                }
    
                if($rules) {
                    $frontendRules = Arr::collapse([$frontendRules, $rules]);
                }
    
                if($creationRules) {
                    $frontendRules = Arr::collapse([$frontendRules, $creationRules]);
                }
    
                if($updateRules) {
                    $frontendRules = Arr::collapse([$frontendRules, $updateRules]);
                }
    
                $item->frontendRules = $frontendRules;
                $this->form['items'][$key] = $item;
            }
        }
    }

    /**
     * 表单默认值，只有初始化以及重置时生效
     *
     * @param array $rules
     *
     * @return array
     */
    protected function initialValues()
    {
        if(isset($this->form['items'])) {
            foreach ($this->form['items'] as $key => $item) {
                $data[$item->name] = $item->defaultValue;
            }
            $this->form['initialValues'] = $data;
        }
    }

    public function render()
    {
        // 设置前端验证规则
        $this->setFrontendRules();

        // 设置表单默认值
        $this->initialValues();

        return $this->form;
    }
}
