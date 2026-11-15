<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\entities;

use yii\db\ActiveRecord;
use Besnovatyj\Shortcode\definitions\ReverseMode;

/**
 * @property integer $id
 * @property string $shortcode
 * @property string $type
 * @property string $replacement
 * @property string $description
 * @property string $reverse_mode
 * @property string $example
 */
class Shortcode extends ActiveRecord
{
    const string TYPE_TEXT = 'text';
    const string TYPE_WIDGET = 'widget';

    /** Создаёт управляемое определение. */
    public static function create(
        string $shortcode,
        string $type,
        string $replacement,
        string $description,
        string $example,
        ReverseMode $reverseMode = ReverseMode::None,
    ): self
    {
        $entity = new static();
        $entity->shortcode = $shortcode;
        $entity->type = $type;
        $entity->replacement = $replacement;
        $entity->description = $description;
        $entity->example = $example;
        $entity->reverse_mode = $reverseMode->value;
        return $entity;
    }

    /** Обновляет полное определение, включая политику обратного преобразования. */
    public function edit(
        string $shortcode,
        string $type,
        string $replacement,
        string $description,
        string $example,
        ReverseMode $reverseMode = ReverseMode::None,
    ): void
    {
        $this->shortcode = $shortcode;
        $this->type = $type;
        $this->replacement = $replacement;
        $this->description = $description;
        $this->example = $example;
        $this->reverse_mode = $reverseMode->value;
    }

    /** Таблица управляемых определений. */
    public static function tableName(): string
    {
        return '{{%shortcode_shortcodes}}';
    }

}
