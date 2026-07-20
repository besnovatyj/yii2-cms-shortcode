<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\widgets;

use Besnovatyj\Shortcode\components\ShortcodeManager;
use Throwable;
use Yii;
use yii\bootstrap5\Widget;
use yii\helpers\Html;

class ShortcodeContent extends Widget
{
    public ?string $content = '';
    private ShortcodeManager $shortcodeManager;

    public function __construct(ShortcodeManager $shortcodeManager, $config = [])
    {
        parent::__construct($config);
        $this->shortcodeManager = $shortcodeManager;
    }

    /**
     * @throws Throwable
     */
    public function run(): string
    {
        if (!Yii::$app->getModule('Shortcode')) {
            return $this->content;
        }

        if (!$this->content) {
            return '';
        }

        return $this->processShortcodes($this->content);
    }

    /**
     * Обработка шорткодов в контенте
     * @param string $content Входной контент
     * @return string Обработанный контент
     * @throws Throwable
     */
    private function processShortcodes(string $content): string
    {
        // Сначала обрабатываем текстовые шорткоды (%shortcode%)
        $content = $this->processTextShortcodes($content);
        // Затем обрабатываем виджетные шорткоды ([widgetName, param1="value1"])
        return $this->processWidgetShortcodes($content);
    }

    /**
     * Обработка текстовых шорткодов в формате %shortcode%
     * @param string $content Входной контент
     * @return string Обработанный контент
     */
    private function processTextShortcodes(string $content): string
    {
        return preg_replace_callback(
            '/%(\w+)%/',
            function ($matches) {
                // было - $shortcode = $matches[1]; и значение шорткода передавалось в метод поиска без оборачивающих процентов.
                $shortcode = $matches[0];
                $replacement = $this->shortcodeManager->getTextReplacement($shortcode);
                return $replacement ?? $matches[0]; // Возвращаем исходный шорткод, если не найден
            },
            $content
        );
    }

    /**
     * Обработка виджетных шорткодов в формате [widgetName, param1="value1"]
     * @param string $content Входной контент
     * @return string Обработанный контент
     * @throws Throwable
     */
    private function processWidgetShortcodes(string $content): string
    {
        // Разделитель между именем шорткода и атрибутами необязателен: допускаются
        // запятая (`[videojs, ...]`), пробел (`[galleryOptima gallery_id=1]`) или
        // полное отсутствие атрибутов (`[contactForm]`). Раньше запятая была
        // обязательной, из-за чего бесатрибутные и пробел-разделённые шорткоды не
        // распознавались и выводились как исходный текст.
        return preg_replace_callback(
            '/\[(\w+)\s*,?\s*([^\]]*)\](?:([\s\S]*?)\[\/\1\])?/',
            function ($matches) {
                $shortcode = $matches[1];
                $attrString = trim($matches[2]);
                $innerContent = $matches[3] ?? '';
                $originalShortcode = $matches[0];

                // Проверка виджетного шорткода
                /** @var $widgetClass \yii\base\Widget */
                $widgetClass = $this->shortcodeManager->getWidgetClass($shortcode);
                if ($widgetClass) {
                    $attributes = $this->parseAttributes($attrString);
                    if (!empty($innerContent)) {
                        $processedInner = $this->processShortcodes($innerContent); // Рекурсивная обработка
                        return $widgetClass::widget($attributes + ['content' => $processedInner]);
                    }
                    return $widgetClass::widget($attributes);
                }

                // Возвращаем исходный шорткод, если виджет не найден
                return $originalShortcode;
            },
            $content
        );
    }

    /**
     * Парсинг атрибутов шорткода
     * @param string $attrString Строка атрибутов
     * @return array Ассоциативный массив атрибутов
     */
    private function parseAttributes(string $attrString): array
    {
        $attributes = [];
        if (empty($attrString)) {
            return $attributes;
        }

        // Регулярное выражение для парсинга атрибутов вида param="value", param='value' или param=value
        preg_match_all(
            '/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^"\'][^\s,]*))/',
            $attrString,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $key = $match[1];
            // Значение может быть в двойных кавычках (2), одинарных кавычках (3) или без кавычек (4)
            $value = !empty($match[2]) ? $match[2] : (!empty($match[3]) ? $match[3] : (!empty($match[4]) ? $match[4] : ''));

            // Проверяем, является ли значение числом
            if (is_numeric($value)) {
                $value = (int)$value; // Преобразуем в целое число, если это число
            } else {
                $value = Html::encode($value); // Экранируем строковые значения
            }

            $attributes[$key] = $value;
        }

        return $attributes;
    }


}
