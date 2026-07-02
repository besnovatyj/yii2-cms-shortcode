<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\migrations;

use Besnovatyj\Kernel\migration\BaseMigration;
use yii\base\NotSupportedException;

/** 'm<YYMMDD_HHMMSS>_<n>' */
class m250226_140100_create_shortcode_shortcodes_table extends BaseMigration
{
    public const string TABLE_NAME = '{{%shortcode_shortcodes}}';

    /**
     * @throws NotSupportedException
     */
    public function safeUp(): void
    {
        parent::safeUp();

        if ($this->existTable(static::TABLE_NAME)) {
            return;
        }

        $this->createTable(static::TABLE_NAME, [
            'id' => $this->primaryKey(),
            'shortcode' => $this->string(255)->notNull()
                ->comment('Шорткод'),
            'type' => $this->string(255)->notNull()
                ->comment('Тип (text, widget)'),
            'replacement' => $this->text()->notNull()
                ->comment('Заменитель (строка или класс виджета)'),
            'description' => $this->text()->null()
                ->comment('Описание'),
            'example' => $this->text()->null()
                ->comment('Пример кода для вставки'),
        ], $this->tableOptions);
        $this->addCommentOnTable(static::TABLE_NAME, 'Модуль шорткодов');

        $this->createIndexes(static::TABLE_NAME, 'shortcode', false, true);
        $this->createIndexes(static::TABLE_NAME, ['shortcode', 'type']);


        $this->batchInsert(static::TABLE_NAME,
            ['id', 'shortcode', 'type', 'replacement', 'description', 'example',],
            [
                [
                    '1',
                    '%test%',
                    'text',
                    'Test Test Test Test Test Test Test',
                    'Тестовый шорткод',
                    '%test%',
                ],
                [
                    '2',
                    'galleryExample',
                    'widget',
                    '\\modules\\shortcode\\widgets\\galleryExampleWidget\\GalleryWidget',
                    'Пример виджета галереи',
                    '[galleryExample, id=123, title=\"Photos\", count=5]Photos[/galleryExample]',
                ],
                [
                    '3',
                    'videojs',
                    'widget',
                    '\\Besnovatyj\\VideoJs\\Player',
                    'Видеоплеер videojs',
                    '[videojs, poster=\"http://files.yii2-cms.loc/origin/blog/1/poster.jpg\" width=1080 height=1920] [source src=\"http://files.yii2-cms.loc/origin/1.mp4\" type=\"video/mp4\" data-res=\"360\"] [source src=\"http://files.yii2-cms.loc/origin/1.mp4\" type=\"video/mp4\" data-res=\"720\"] [/videojs]',
                ],
                [
                    '4',
                    'Ymap',
                    'widget',
                    '\\Besnovatyj\\YandexMap\\widgets\\yandexMap\\YandexMapWidget',
                    'Виджет Яндекс карт',
                    '[Ymap, id=123]Карта[/Ymap]',
                ],
                [
                    '5',
                    'gallery',
                    'widget',
                    '\\themes\\berdramashock\\widgets\\gallery\\GalleryWidget',
                    'Виджет галереи',
                    '[gallery, galleryUuid=b195164c-7526-4127-b5f7-1c3c6652d1c6][/gallery]',
                ],
            ]
        );

        parent::safeUp();
    }

    public function safeDown(): void
    {
        parent::safeDown();
    }
}
