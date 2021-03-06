<?php

namespace Shemi\Laradmin\FormFields;

use Illuminate\Support\HtmlString;
use Shemi\Laradmin\Contracts\FormFieldContract;
use Shemi\Laradmin\FormFields\Traits\Buildable;
use Shemi\Laradmin\Models\Field;
use Shemi\Laradmin\Data\Model;
use Shemi\Laradmin\Models\Setting;
use Shemi\Laradmin\Traits\Renderable;

abstract class FormFormField implements FormFieldContract
{
    use Renderable,
        Buildable;

    protected $name;

    protected $codename;

    protected $subFieldsSupported = false;

    public function __construct()
    {
        $this->registerBlueprintMacros();
    }

    /**
     * @param Field $field
     * @param Model $type
     * @param $data
     *
     * @return HtmlString
     * @throws \Throwable
     */
    public function handle(Field $field, Model $type, $data)
    {
        $content = $this->createContent($field, $type, $data);

        return $this->render($content);
    }

    /**
     * @return string
     */
    public function getCodename()
    {
        if (empty($this->codename)) {
            $name = class_basename($this);

            if (ends_with($name, 'Field')) {
                $name = substr($name, 0, -strlen('Field'));
            }

            $this->codename = snake_case($name);
        }

        return $this->codename;
    }

    /**
     * @return string
     */
    public function getName()
    {
        if (empty($this->name)) {
            $this->name = ucwords(str_replace('_', ' ', $this->getCodename()));
        }

        return $this->name;
    }

    public function transformRequest(Field $field, $data)
    {
        if($field->nullable != false) {
            return $data === $field->nullable ? null : $data;
        }

        return $data;
    }

    public function transformResponse(Field $field, $data)
    {
        return $data;
    }

    public function getValidationRoles(Field $field)
    {
        if(! $field->validation || empty($field->validation)) {
            return false;
        }

        return ["{$field->key}" => $field->validation];
    }

    public function isSupportingSubFields()
    {
        return (boolean) $this->subFieldsSupported;
    }

    public function getFormPrefix(Field $field)
    {
        if($field->is_repeater_sub_field) {
            return "props.row.";
        }

        if($field->parent) {
            return rtrim("{$field->parent->form_prefix}{$field->parent->key}.", '.').'.';
        }

        return "form.";
    }

    public function getValidationKey(Field $field, Field $parent = null)
    {
        if($field->is_repeater_sub_field) {
            return "{$parent->validation_key}.'+ props.index +'.{$field->key}";
        }

        if($parent) {
            return "{$parent->key}.{$field->key}";
        }

        return $field->key;
    }

    public function getSettingsValueType(Field $field)
    {
        return Setting::TYPE_STRING;
    }

}