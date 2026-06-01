<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\entities;

use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property string $shortcode
 * @property string $type
 * @property string $replacement
 * @property string $description
 * @property string $example
 */
class Shortcode extends ActiveRecord
{
    const string TYPE_TEXT = 'text';
    const string TYPE_WIDGET = 'widget';

    public static function create(string $shortcode, string $type, string $replacement, string $description, string $example): self
    {
        $entity = new static();
        $entity->shortcode = $shortcode;
        $entity->type = $type;
        $entity->replacement = $replacement;
        $entity->description = $description;
        $entity->example = $example;
        return $entity;
    }

    public function edit(string $shortcode, string $type, string $replacement, string $description, string $example): void
    {
        $this->shortcode = $shortcode;
        $this->type = $type;
        $this->replacement = $replacement;
        $this->description = $description;
        $this->example = $example;
    }

    public static function tableName(): string
    {
        return '{{%shortcode_shortcodes}}';
    }

}
