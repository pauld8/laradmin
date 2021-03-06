<?php

namespace Shemi\Laradmin\Models;

use Illuminate\Support\Collection;
use Shemi\Laradmin\Data\Model;

use \Illuminate\Database\Eloquent\Model as EloquentModel;
use Shemi\Laradmin\Managers\DynamicsManager;
use Shemi\Laradmin\Models\Contracts\Buildable as BuildableContract;
use Shemi\Laradmin\Models\Traits\Buildable;
use Shemi\Laradmin\Models\Traits\HasBrowseSettings;
use Shemi\Laradmin\Models\Traits\InteractsWithFormField;
use Shemi\Laradmin\Models\Traits\InteractsWithMedia;
use Shemi\Laradmin\Models\Traits\InteractsWithRelationship;
use Shemi\Laradmin\Models\Traits\HasTemplateOptions;
use Shemi\Laradmin\Transformers\Builder\FieldTransformer;
use Shemi\Laradmin\Transformers\FieldDefaultValueTransformer;
use Shemi\Laradmin\Transformers\Response\ModelTransformer;

/**
 * Shemi\Laradmin\Models\Field
 *
 * @property string $label
 * @property string $key
 * @property string $full_key
 * @property string $validation_key
 * @property string|null $value_manipulation
 * @property Field|null $parent
 * @property boolean $show_label
 * @property boolean $is_repeater_like
 * @property mixed $default_value
 * @property mixed $nullable
 * @property string $type
 * @property array $validation
 * @property array $visibility
 * @property array $options
 * @property array $template_options
 * @property array $browse_settings
 * @property array $relationship
 * @property boolean $is_sub_field
 * @property boolean $is_password
 * @property boolean $is_support_sub_fields
 * @property boolean $is_repeater_sub_field
 * @property Collection|null $fields
 * @property string $form_prefix
 * @property boolean $read_only
 * @property Collection $raw_fields
 */
class Field extends Model implements BuildableContract
{

    use InteractsWithRelationship,
        InteractsWithMedia,
        HasBrowseSettings,
        HasTemplateOptions,
        InteractsWithFormField,
        Buildable;

    const OBJECT_TYPE = 'field';

    protected $dataable = false;

    protected $keyType = 'string';

    protected $jsonIgnore = ['parent'];

    public static $dateTypes = ['date', 'datetime', 'time'];

    public static $forcedHiddenLabelTypes = ['switch', 'checkbox'];

    public static $repeaterTypes = ['repeater'];

    public static $numericTypes = ['number', 'float'];

    /**
     * @var Panel $_panel
     */
    protected $_panel;

    /**
     * @var Type $_type
     */
    protected $_type;

    public $parent = null;

    public $is_sub_field = false;

    protected $fillable = [
        'id',
        'label',
        'key',
        'show_label',
        'default_value',
        'read_only',
        'nullable',
        'type',
        'validation',
        'visibility',
        'options',
        'template_options',
        'browse_settings',
        'relationship',
        'fields',
        'media'
    ];

    public static function fromArray($attributes, Panel $panel, Model $type)
    {
        $model = new static;

        $model->setPanel($panel);
        $model->setType($type);

        $localAttributes = [
            'parent' => null,
            'is_sub_field' => false

        ];

        foreach ($localAttributes as $attribute => $defaultValue) {
            $model->{$attribute} = array_get($attributes, $attribute, $defaultValue);

            array_forget($attributes, $attribute);
        }

        $model->setRawAttributes((array) $attributes, true);

        return $model;
    }

    /**
     * @param array|string $views
     * @return bool
     */
    public function isVisibleOn($views)
    {
        if(! isset($this->visibility) || empty($this->visibility)) {
            return false;
        }

        foreach ((array) $views as $view) {
            if(in_array($view, $this->visibility)) {
                return true;
            }
        }

        return false;
    }

    public function getShowLabelAttribute($value)
    {
        if(! $this->getPanel()->is_supporting_fields_labels) {
            return false;
        }

        if(in_array($this->type, static::$forcedHiddenLabelTypes)) {
            return false;
        }

        return $value !== null ? $value : true;
    }

    public function getFullKeyAttribute()
    {
        $prefix = "";

        if($this->parent) {
            $prefix = $this->parent->full_key.".";
        }

        return $prefix.$this->key;
    }

    public function getValueManipulationAttribute($value)
    {
        if($value) {
            return $value;
        }

        return $this->getTemplateOption('transform');
    }

    public function getIsRepeaterSubFieldAttribute()
    {
        return $this->is_sub_field &&
            $this->parent &&
            $this->parent->is_repeater_like;
    }

    public function getIsSupportSubFieldsAttribute()
    {
        return $this->formField()->isSupportingSubFields();
    }

    public function getValidationKeyAttribute()
    {
        return $this->formField()->getValidationKey($this, $this->parent);
    }

    public function getIsPasswordAttribute()
    {
        return $this->field_type === 'password';
    }

    public function getFormPrefixAttribute()
    {
        return $this->formField()->getFormPrefix($this);
    }

    public function getFieldsAttribute($value)
    {
        return collect($value)->transform(function($rawField) {
            $rawField['is_sub_field'] = true;
            $rawField['parent'] = $this;

            return static::fromArray($rawField, $this->getPanel(), $this->getType());
        });
    }

    /**
     * @return Collection
     */
    public function getSubFields()
    {
        $localFields = $this->fields;

        if($this->is_repeater_like || ! $this->has_relationship_type) {
            return $localFields;
        }

        $relationType = $this->relationship_type;
        $exclude = array_get($this->relationship, 'exclude', []);

        return $relationType->fields
            ->reject(function(Field $field) use ($exclude) {
                return in_array($field->key, $exclude) || $field->read_only;
            })
            ->map(function(Field $field) use ($localFields) {
                $field = $localFields->where('key', $field->key)->first() ?: $field;

                $field->is_sub_field = true;
                $field->parent = $this;

                return $field;
            });
    }

    /**
     * @param $value
     * @return array
     * @throws \Shemi\Laradmin\Exceptions\ManagerDoesNotExistsException
     * @throws \Exception
     */
    public function getOptionsAttribute($value)
    {
        if($this->isInBuilderMode()) {
            return $value ?: [];
        }

        $dynamicsManager = laradmin()->dynamics();

        if(is_string($value) && $dynamicsManager->validate($value)) {
            return $dynamicsManager->call($value, true);
        }

        if(is_array($value)) {
            return $value;
        }

        return [];
    }

    public function getIsRepeaterLikeAttribute()
    {
        return in_array($this->type, static::$repeaterTypes);
    }

    public function getIsNumericAttribute()
    {
        return in_array($this->field_type, static::$numericTypes);
    }

    public function getModelCastType(EloquentModel $model)
    {
        return trim(strtolower($model->getCasts()[$this->key]));
    }

    public function getDefaultValue()
    {
        return FieldDefaultValueTransformer::transform($this);
    }

    /**
     * @param $value
     * @return array
     * @throws \Exception
     * @throws \Shemi\Laradmin\Exceptions\ManagerDoesNotExistsException
     */
    public function getDefaultValueAttribute($value)
    {
        if(! $this->isInBuilderMode() && is_string($value)) {
            $dynamics = laradmin()->dynamics();

            if($dynamics->validate($value)) {
                return $dynamics->call($value);
            }
        }

        return $value;
    }

    /**
     * @param EloquentModel $model
     * @param null $key
     * @return mixed
     * @throws \Exception
     */
    public function getModelValue(EloquentModel $model, $key = null)
    {
        $key = $key ?: $this->key;

        $value = (new ModelTransformer)
            ->transform($this, $key, $model, true);

        return $this->transformResponse($value);
    }

    public function isDate()
    {
        return in_array($this->type, static::$dateTypes);
    }

    public function getVueFilter()
    {
        if($this->isDate()) {
            return "date()";
        }

        return null;
    }

    public static function isValidField($field)
    {
        if ($field instanceof Field) {
            return true;
        }

        $field = (array) $field;

        if(isset($field['object_type'])) {
            return $field['object_type'] === static::OBJECT_TYPE;
        }

        return is_array($field) && array_key_exists('key', $field) && ! empty($field['key']);
    }

    public function toBuilder()
    {
        return $this->builderMode(function() {
            return FieldTransformer::transform($this);
        });
    }

    /**
     * @return Panel
     */
    public function getPanel(): Panel
    {
        return $this->_panel;
    }

    /**
     * @param Panel $panel
     * @return Field
     */
    public function setPanel(Panel $panel)
    {
        $this->_panel = $panel;

        return $this;
    }

    /**
     * @return Type
     */
    public function getType(): Model
    {
        return $this->_type;
    }

    /**
     * @param Model $type
     * @return Field
     */
    public function setType(Model $type)
    {
        $this->_type = $type;

        return $this;
    }


}