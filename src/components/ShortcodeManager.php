<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\components;

use Besnovatyj\Contracts\shortcode\ShortcodeTextResolver;
use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\services\ShortcodeRegistry;
use Besnovatyj\Shortcode\definitions\ShortcodeDefinition;
use Besnovatyj\Shortcode\definitions\ReverseMode;
use yii\base\Component;
use yii\base\InvalidConfigException;

/** Совместимый фасад общего реестра и однопроходного раскрытия текста. */
class ShortcodeManager extends Component implements ShortcodeTextResolver
{
    /**
     * Каталог приходит из DI-контейнера: компонент поднимается через `Yii::createObject()`,
     * поэтому зависимость в конструкторе резолвится так же, как у сервисов.
     * Теперь компонент получает общий реестр, который использует каталог без второго снимка.
     */
    public function __construct(private readonly ShortcodeRegistry $registry, array $config = [])
    {
        parent::__construct($config);
    }

    /** Инициализация компонента без преждевременного чтения определений. */
    public function init(): void
    {
        // Обе карты читаются из общего кэша каталога — на горячем пути запроса к БД нет.
        // Теперь загрузка лениво выполняется реестром, а обе карты строятся из одного набора.
        parent::init();
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
        $this->registry->register(new ShortcodeDefinition($shortcode, Shortcode::TYPE_WIDGET, $widgetClass));
    }

    /**
     * Регистрация текстового шорткода
     * @param string $shortcode Имя шорткода
     * @param string $replacement Текст для замены
     */
    public function registerText(
        string $shortcode,
        string $replacement,
        ReverseMode $reverseMode = ReverseMode::None,
    ): void
    {
        $this->registry->register(new ShortcodeDefinition($shortcode, Shortcode::TYPE_TEXT, $replacement, reverseMode: $reverseMode));
    }

    /**
     * Получение класса виджета по шорткоду
     * @param string $shortcode Имя шорткода
     * @return string|null Класс виджета или null, если не найден
     */
    public function getWidgetClass(string $shortcode): ?string
    {
        return $this->getWidgetShortcodes()[$shortcode] ?? null;
    }

    /**
     * Получение текста для замены по шорткоду
     * @param string $shortcode Имя шорткода
     * @return string|null Текст замены или null, если не найден
     */
    public function getTextReplacement(string $shortcode): ?string
    {
        return $this->getTextShortcodes()[$shortcode] ?? null;
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
        $replacements = $this->getTextShortcodes();
        if ($replacements === []) {
            return $content;
        }
        $pattern = '~' . implode('|', array_map(static fn (string $name): string => preg_quote($name, '~'), array_keys($replacements))) . '~';
        return preg_replace_callback(
            $pattern,
            function ($matches) use ($replacements) {
                // было - $shortcode = $matches[1]; и значение шорткода передавалось в метод поиска без оборачивающих процентов.
                $shortcode = $matches[0];
                $replacement = $replacements[$shortcode] ?? null;
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
        $definitions = [];
        foreach ($shortcodes as $shortcode => $replacement) {
            $definitions[] = new ShortcodeDefinition((string)$shortcode, Shortcode::TYPE_TEXT, (string)$replacement);
        }
        $this->registry->registerMany($definitions);
    }

    /** Карта эффективных текстовых определений. */
    public function getTextShortcodes(): array
    {
        return $this->replacements(Shortcode::TYPE_TEXT);
    }

    /** Карта эффективных виджетных определений. */
    public function getWidgetShortcodes(): array
    {
        return $this->replacements(Shortcode::TYPE_WIDGET);
    }

    /** Строит карту из общего реестра без отдельного состояния в компоненте. */
    private function replacements(string $type): array
    {
        $result = [];
        foreach ($this->registry->all() as $definition) {
            if ($definition->type === $type) {
                $result[$definition->shortcode] = $definition->replacement;
            }
        }
        return $result;
    }
}
