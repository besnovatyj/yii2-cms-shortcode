<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\components;

use Besnovatyj\Contracts\shortcode\ShortcodeTextResolver;
use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\repositories\ShortcodeRepository;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Url;

class ShortcodeManager extends Component implements ShortcodeTextResolver
{
    private array $widgetShortcodes = [];
    private array $textShortcodes = [];

    public function init(): void
    {
        parent::init();

        if (!Yii::$app->getModule('Shortcode')) {
            return;
        }

        $repo = new ShortcodeRepository();

        foreach ($repo->findAll(Shortcode::TYPE_WIDGET) as $item) {
            $this->widgetShortcodes[$item->shortcode] = $item->replacement;
        }

        foreach ($repo->findAll(Shortcode::TYPE_TEXT) as $item) {
            $this->textShortcodes[$item->shortcode] = $item->replacement;
        }

        $this->textShortcodes = array_merge(
            $this->textShortcodes,
            [
                '%homeUrl%' => Url::home(),
                '%staticHost%' => Yii::$app->params['staticHostName'],
                '%frontendHost%' => Yii::$app->params['frontendHostName'],
            ]
        );

    }

    /**
     * Регистрация шорткода для виджета
     * @param string $shortcode Имя шорткода
     * @param string $widgetClass Класс виджета
     * @throws InvalidConfigException Если класс виджета не существует
     */
    public function registerWidget(string $shortcode, string $widgetClass): void
    {
        if (!class_exists($widgetClass)) {
            throw new InvalidConfigException("Widget class '$widgetClass' does not exist.");
        }
        $this->widgetShortcodes[$shortcode] = $widgetClass;
    }

    /**
     * Регистрация текстового шорткода
     * @param string $shortcode Имя шорткода
     * @param string $replacement Текст для замены
     */
    public function registerText(string $shortcode, string $replacement): void
    {
        $this->textShortcodes[$shortcode] = $replacement;
    }

    /**
     * Получение класса виджета по шорткоду
     * @param string $shortcode Имя шорткода
     * @return string|null Класс виджета или null, если не найден
     */
    public function getWidgetClass(string $shortcode): ?string
    {
        return $this->widgetShortcodes[$shortcode] ?? null;
    }

    /**
     * Получение текста для замены по шорткоду
     * @param string $shortcode Имя шорткода
     * @return string|null Текст замены или null, если не найден
     */
    public function getTextReplacement(string $shortcode): ?string
    {
        return $this->textShortcodes[$shortcode] ?? null;
    }

    /**
     * {@inheritdoc}
     *
     * Разбор текстовых шорткодов `%name%` вынесен сюда из {@see \Besnovatyj\Shortcode\widgets\ShortcodeContent},
     * чтобы значения можно было получать и внутри приложения, и в других модулях (например, для URL,
     * хранимых доменно-независимо через `%staticHost%`), а не только при рендере контента виджетом.
     */
    public function resolveText(string $content): string
    {
        return preg_replace_callback(
            '/%(\w+)%/',
            function ($matches) {
                // было - $shortcode = $matches[1]; и значение шорткода передавалось в метод поиска без оборачивающих процентов.
                $shortcode = $matches[0];
                $replacement = $this->getTextReplacement($shortcode);
                return $replacement ?? $matches[0]; // Возвращаем исходный шорткод, если не найден
            },
            $content
        );
    }

    /**
     * Загрузка текстовых шорткодов из конфигурации (например, для админки)
     * @param array $shortcodes Массив соответствий [шорткод => текст]
     */
    public function loadTextShortcodes(array $shortcodes): void
    {
        $this->textShortcodes = array_merge($this->textShortcodes, $shortcodes);
    }

    public function getTextShortcodes(): array
    {
        return $this->textShortcodes;
    }

    public function getWidgetShortcodes(): array
    {
        return $this->widgetShortcodes;
    }

}
