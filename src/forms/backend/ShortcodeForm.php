<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\forms\backend;

use Besnovatyj\Shortcode\entities\Shortcode;
use yii\base\Model;

class ShortcodeForm extends Model
{
    public $shortcode;
    public $type;
    public $replacement;
    public $description;
    public $example;
    private Shortcode|null $_shortcode = null;

    public function __construct(Shortcode $shortcode = null, $config = [])
    {
        if ($shortcode) {
            $this->shortcode = $shortcode->shortcode;
            $this->type = $shortcode->type;
            $this->replacement = $shortcode->replacement;
            $this->description = $shortcode->description;
            $this->example = $shortcode->example;
            $this->_shortcode = $shortcode;
        }
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['shortcode', 'type', 'replacement'], 'required'],
            [['description', 'example'], 'string'],
            [['shortcode', 'type', 'replacement'], 'string', 'max' => 255],
            ['type', 'in', 'range' => [Shortcode::TYPE_TEXT, Shortcode::TYPE_WIDGET]],
            ['shortcode', 'validateShortcodeUnique'],
            ['replacement', 'validateClassExists', 'when' => function ($model) {
                return $model->type == Shortcode::TYPE_WIDGET;
            }, 'whenClient' => "function (attribute, value) {
                    return $('#replacement').val() == " . Shortcode::TYPE_WIDGET . ";
                }"],
        ];
    }

    public function validateShortcodeUnique($attribute, $params): void
    {
        /** В компоненте доступны абсолютно все шорткоды, а не только из базы данных. */
        $shortcodes = \Yii::$app->shortcode->textShortcodes;
        $currentShortcode = $this->$attribute;

        // Проверяем, существует ли шорткод в массиве
        if (isset($shortcodes[$currentShortcode])) {
            // Если это редактирование существующего шорткода, проверяем, не совпадает ли он сам с собой
            if ($this->_shortcode && $this->_shortcode->shortcode === $currentShortcode) {
                return;
            }
            $this->addError($attribute, 'Шорткод уже существует.');
        }
    }

    public function validateClassExists($attribute, $params): void
    {
        // Заменяем слеши на обратные слеши для namespace
        $className = str_replace('/', '\\', $this->$attribute);
        if (!class_exists($className)) {
            $this->addError($attribute, 'Указанный класс не существует.');
        }
    }

    public function attributeLabels(): array
    {
        return [
            'shortcode' => 'Шорткод',
            'type' => 'Тип',
            'replacement' => 'Заменитель',
            'description' => 'Описание',
            'example' => 'Пример кода для вставки',
        ];
    }

    public function typesList(): array
    {
        return [
            Shortcode::TYPE_TEXT => Shortcode::TYPE_TEXT,
            Shortcode::TYPE_WIDGET => Shortcode::TYPE_WIDGET,
        ];
    }
}
