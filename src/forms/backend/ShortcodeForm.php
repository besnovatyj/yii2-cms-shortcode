<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\forms\backend;

use Besnovatyj\Shortcode\entities\Shortcode;
use yii\base\Model;
use Besnovatyj\Shortcode\definitions\ReverseMode;
use Besnovatyj\Shortcode\definitions\ShortcodeDefinition;
use Besnovatyj\Shortcode\services\ShortcodeRegistry;
use InvalidArgumentException;
use ValueError;
use Yii;

/** Форма полного переопределения, независимая от программного источника имени. */
class ShortcodeForm extends Model
{
    public $shortcode;
    public $type;
    public $replacement;
    public $description;
    public $example;
    public $reverse_mode = 'none';
    private Shortcode|null $_shortcode = null;

    /** Заполняет форму редактируемой записью. */
    public function __construct(?Shortcode $shortcode = null, $config = [])
    {
        if ($shortcode) {
            $this->shortcode = $shortcode->shortcode;
            $this->type = $shortcode->type;
            $this->replacement = $shortcode->replacement;
            $this->description = $shortcode->description;
            $this->example = $shortcode->example;
            $this->reverse_mode = $shortcode->reverse_mode;
            $this->_shortcode = $shortcode;
        }
        parent::__construct($config);
    }

    /** Правила формы и эффективного набора определений. */
    public function rules(): array
    {
        return [
            [['shortcode', 'type', 'replacement', 'reverse_mode'], 'required'],
            [['description', 'example'], 'string'],
            [['shortcode', 'type', 'replacement'], 'string', 'max' => 255],
            ['type', 'in', 'range' => [Shortcode::TYPE_TEXT, Shortcode::TYPE_WIDGET]],
            ['shortcode', 'validateShortcodeUnique'],
            ['reverse_mode', 'in', 'range' => array_column(ReverseMode::cases(), 'value')],
            ['reverse_mode', 'validateDefinition'],
            ['replacement', 'validateClassExists', 'when' => function ($model) {
                return $model->type == Shortcode::TYPE_WIDGET;
            }, 'whenClient' => "function (attribute, value) {
                    return $('#shortcodeform-type').val() === 'widget';
                }"],
        ];
    }

    /** Проверяет уникальность только среди управляемых записей. */
    public function validateShortcodeUnique($attribute, $params): void
    {
        /** В компоненте доступны абсолютно все шорткоды, а не только из базы данных. */
        // Прежние пояснения сохранены; теперь программный слой не участвует в уникальности БД.
        // Проверяем, существует ли шорткод в массиве
        // Если это редактирование существующего шорткода, проверяем, не совпадает ли он сам с собой
        $query = Shortcode::find()->where(['shortcode' => $this->$attribute]);
        if ($this->_shortcode !== null) {
            $query->andWhere(['<>', 'id', $this->_shortcode->id]);
        }
        if ($query->exists()) {
            $this->addError($attribute, 'Шорткод уже существует в базе данных.');
        }
    }

    /** Проверяет кандидат после применения приоритетов кода и БД. */
    public function validateDefinition($attribute, $params): void
    {
        if ($this->hasErrors()) {
            return;
        }
        try {
            Yii::$container->get(ShortcodeRegistry::class)->validateCandidate($this->definition());
        } catch (InvalidArgumentException | ValueError $exception) {
            $this->addError($attribute, $exception->getMessage());
        }
    }

    /** Определение формы; ID позволяет исключить старое имя при переименовании. */
    public function definition(): ShortcodeDefinition
    {
        return new ShortcodeDefinition(
            (string)$this->shortcode,
            (string)$this->type,
            (string)$this->replacement,
            (string)$this->description,
            (string)$this->example,
            ReverseMode::from((string)$this->reverse_mode),
            $this->_shortcode === null ? null : (int)$this->_shortcode->id,
        );
    }

    /** Проверяет существование класса виджетного шорткода. */
    public function validateClassExists($attribute, $params): void
    {
        // Заменяем слеши на обратные слеши для namespace
        $className = str_replace('/', '\\', $this->$attribute);
        if (!class_exists($className)) {
            $this->addError($attribute, 'Указанный класс не существует.');
        }
    }

    /** Подписи полей. */
    public function attributeLabels(): array
    {
        return [
            'shortcode' => 'Шорткод',
            'type' => 'Тип',
            'replacement' => 'Заменитель',
            'description' => 'Описание',
            'example' => 'Пример кода для вставки',
            'reverse_mode' => 'Обратное преобразование',
        ];
    }

    /** Доступные типы. */
    public function typesList(): array
    {
        return [
            Shortcode::TYPE_TEXT => Shortcode::TYPE_TEXT,
            Shortcode::TYPE_WIDGET => Shortcode::TYPE_WIDGET,
        ];
    }
}
