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
                    'galleryExample',
                    'widget',
                    '\\Besnovatyj\\Shortcode\\widgets\\galleryExampleWidget\\GalleryWidget',
                    'Пример виджета галереи',
                    '[galleryExample, id=1, title="Photos", count=5]Photos[/galleryExample]',
                ],
                [
                    '2',
                    'videojs',
                    'widget',
                    '\\Besnovatyj\\VideoJs\\Player',
                    'Видеоплеер VideoJs',
                    '[videojs, poster="/preview.png" title="Вертикальное видео" aspectRatio="9:16" width=1080 height=1920 controls=1 autoplay=0 muted=1 playsinline=1 preload="metadata" lazyVideo=1] [source src="%staticHost%/optima/files/videos/ChemiCos_2025.mp4" type="video/mp4" data-res="1080"] [source src="%staticHost%/optima/files/videos/ChemiCos_2025.webm" type="video/webm" data-res="720"] [/videojs] ',
                ],
                [
                    '3',
                    'Ymap',
                    'widget',
                    '\\Besnovatyj\\YandexMap\\widgets\\yandexMap\\YandexMapWidget',
                    'Виджет Яндекс карт',
                    '[Ymap, id=1]Карта[/Ymap]',
                ],
                [
                    '4',
                    'contactForm',
                    'widget',
                    '\\Besnovatyj\\Contact\\widgets\\compose\\ComposeWidget',
                    'Виджет модуля Contact',
                    '[contactForm][/contactForm]',
                ],
                [
                    '5',
                    'galleryBerdrama',
                    'widget',
                    '\\themes\\berdramashock\\widgets\\gallery\\GalleryWidget',
                    'Виджет галереи berdramashock',
                    '[galleryBerdrama, gallery_id=1][/galleryBerdrama]',
                ],
                [
                    '6',
                    'galleryOptima',
                    'widget',
                    '\\themes\\optima\\widgets\\gallery\\GalleryWidget',
                    'Виджет галереи optima',
                    '[galleryOptima, gallery_id=1][/galleryOptima]',
                ],
                [
                    '7',
                    'contactFormOptima',
                    'widget',
                    '\themes\optima\widgets\contact\ContactWidget',
                    'Виджет отправки писем optima',
                    '[contactFormOptima, gallery_id=1][/contactFormOptima]',
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
