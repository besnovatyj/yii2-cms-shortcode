<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\readModels;

use Besnovatyj\Shortcode\entities\Shortcode;

/**
 * Неизменяемое представление одного шорткода для справочных интерфейсов
 * (виджет-подсказка {@see \Besnovatyj\Shortcode\widgets\ShortcodesList}).
 *
 * Зачем отдельный тип, а не сущность {@see Shortcode}: в справку попадают не только записи БД,
 * но и шорткоды, зарегистрированные в рантайме через
 * {@see \Besnovatyj\Shortcode\components\ShortcodeManager::registerText()} /
 * {@see \Besnovatyj\Shortcode\components\ShortcodeManager::registerWidget()} — у них нет `id`
 * и нет карточки в админке. Единый DTO избавляет представление от развилок «сущность или массив».
 */
final readonly class ShortcodeItem
{
    /**
     * @param string   $shortcode   Имя шорткода (для текстовых — вместе с обрамляющими `%`)
     * @param string   $type        Тип: {@see Shortcode::TYPE_TEXT} или {@see Shortcode::TYPE_WIDGET}
     * @param string   $replacement Чем заменяется: строка подстановки или класс виджета
     * @param string   $description Описание из карточки шорткода (может быть пустым)
     * @param string   $example     Готовый пример вставки — то, что копируется одной кнопкой
     * @param int|null $id          ID записи в БД; null — шорткод зарегистрирован в коде
     */
    public function __construct(
        public string $shortcode,
        public string $type,
        public string $replacement,
        public string $description,
        public string $example,
        public ?int $id = null,
    ) {
    }

    /**
     * Сборка из строки каталога ({@see \Besnovatyj\Shortcode\services\ShortcodeCatalog::rows()}).
     *
     * Пустой `example` подменяется синтаксически корректной заготовкой, чтобы кнопка «копировать»
     * никогда не отдавала пустую строку.
     *
     * @param array{id:int|string,shortcode:string,type:string,replacement:string,description:?string,example:?string} $row
     */
    public static function fromRow(array $row): self
    {
        $shortcode = (string)$row['shortcode'];
        $type = (string)$row['type'];
        $example = trim((string)($row['example'] ?? ''));

        return new self(
            shortcode: $shortcode,
            type: $type,
            replacement: (string)$row['replacement'],
            description: trim((string)($row['description'] ?? '')),
            example: $example !== '' ? $example : self::defaultExample($shortcode, $type),
            id: (int)$row['id'],
        );
    }

    /**
     * Заготовка примера вставки для шорткода без заполненного поля `example`.
     */
    public static function defaultExample(string $shortcode, string $type): string
    {
        return $type === Shortcode::TYPE_WIDGET
            ? sprintf('[%1$s][/%1$s]', $shortcode)
            : $shortcode;
    }

    public function isWidget(): bool
    {
        return $this->type === Shortcode::TYPE_WIDGET;
    }

    /**
     * Есть ли у шорткода карточка в админке (то есть запись в БД, а не регистрация в коде).
     */
    public function isManaged(): bool
    {
        return $this->id !== null;
    }

    /**
     * Строка, по которой виджет ищет шорткод в модалке (поиск идёт на стороне браузера).
     */
    public function searchIndex(): string
    {
        return mb_strtolower(implode(' ', [
            $this->shortcode,
            $this->description,
            $this->replacement,
            $this->example,
        ]));
    }
}
