<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\migrations;

use Besnovatyj\Kernel\migration\BaseMigration;
use Besnovatyj\Shortcode\definitions\ReverseMode;

/** Добавляет политику обратного преобразования, не изменяя существующий контент. */
final class m261115_000001_add_shortcode_reverse_mode extends BaseMigration
{
    public const string TABLE_NAME = '{{%shortcode_shortcodes}}';

    /** Добавляет настройку всем существующим определениям с выключенным reverse. */
    public function safeUp(): void
    {
        $this->addColumn(self::TABLE_NAME, 'reverse_mode', $this->string(32)->notNull()->defaultValue(ReverseMode::None->value));
    }

    /** Откатывает только новый столбец: BaseMigration::safeDown удаляет всю таблицу. */
    public function safeDown(): void
    {
        $this->dropColumn(self::TABLE_NAME, 'reverse_mode');
    }
}
