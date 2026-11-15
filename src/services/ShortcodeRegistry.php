<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\services;

use Besnovatyj\Shortcode\definitions\ReverseMode;
use Besnovatyj\Shortcode\definitions\ShortcodeDefinition;
use Besnovatyj\Shortcode\Module;
use InvalidArgumentException;
use LogicException;
use Yii;

/** Единый реестр запроса: уникальные программные определения и полные переопределения БД. */
final class ShortcodeRegistry
{
    /** @var array<string, ShortcodeDefinition>|null */
    private ?array $programmatic = null;

    /** Каталог отвечает только за кэш БД, поставщик — за встроенные определения. */
    public function __construct(
        private readonly ShortcodeCatalog $catalog,
        private readonly BuiltinShortcodeDefinitions $builtins,
    ) {
    }

    /** Регистрирует определение; даже совпадающая повторная регистрация является ошибкой. */
    public function register(ShortcodeDefinition $definition): void
    {
        $this->registerMany([$definition]);
    }

    /** Атомарная пакетная регистрация: при ошибке ни одно определение не добавляется. */
    public function registerMany(array $definitions): void
    {
        $programmatic = $this->programmatic();
        foreach ($definitions as $definition) {
            if (isset($programmatic[$definition->shortcode])) {
                throw new LogicException("Программный шорткод {$definition->shortcode} уже зарегистрирован; переопределение разрешено только через БД.");
            }
            $programmatic[$definition->shortcode] = $definition;
        }
        $this->validate(array_replace($programmatic, $this->database()));
        $this->programmatic = $programmatic;
    }

    /* Историческое описание прежнего объединения; теперь оно выполняется в ShortcodeRegistry. */
    /**
     * Добавление зарегистрированных в коде шорткодов, которых ещё нет в списке.
     *
     * @param array<string, ShortcodeItem> $items      Уже собранные элементы, ключ — имя шорткода
     * @param array<string, string>        $registered Карта `шорткод => замена` из менеджера
     * @param string                       $type       Тип добавляемых элементов
     * @return array<string, ShortcodeItem>
     */

    /** Эффективный набор; БД имеет приоритет независимо от времени регистрации. */
    public function all(): array
    {
        $items = array_replace($this->programmatic(), $this->database());
        $this->validate($items);
        return $items;
    }

    /** Проверяет предполагаемую запись формы, заменив прежнюю запись с тем же ID. */
    public function validateCandidate(ShortcodeDefinition $candidate): void
    {
        $database = $this->database($candidate->id);
        $database[$candidate->shortcode] = $candidate;
        $this->validate(array_replace($this->programmatic(), $database));
    }

    /** Проверяет набор без изменяемой/удаляемой записи, включая открывающиеся определения кода. */
    public function validateRemoval(int $id): void
    {
        $database = $this->database($id);
        $this->validate(array_replace($this->programmatic(), $database));
    }

    /** Имена программного слоя не зависят от наличия переопределения в БД. */
    private function programmatic(): array
    {
        if ($this->programmatic === null) {
            $items = [];
            foreach ($this->builtins->all() as $definition) {
                if (isset($items[$definition->shortcode])) {
                    throw new LogicException("Повторное встроенное определение {$definition->shortcode}.");
                }
                $items[$definition->shortcode] = $definition;
            }
            $this->programmatic = $items;
        }
        return $this->programmatic;
    }

    /** Строки БД кэширует каталог; локального устаревающего снимка в реестре нет. */
    private function database(?int $excludedId = null): array
    {
        if (Yii::$app->getModule(Module::moduleId()) === null) {
            return [];
        }
        $items = [];
        foreach ($this->catalog->rows() as $row) {
            if ($excludedId !== null && (int)$row['id'] === $excludedId) {
                continue;
            }
            $definition = ShortcodeDefinition::fromRow($row);
            if (isset($items[$definition->shortcode])) {
                throw new LogicException("Повторное определение БД {$definition->shortcode}.");
            }
            $items[$definition->shortcode] = $definition;
        }
        return $items;
    }

    /** Неоднозначное обратное преобразование не зависит от порядка элементов. */
    private function validate(array $definitions): void
    {
        $prefixes = [];
        foreach ($definitions as $definition) {
            if ($definition->reverseMode === ReverseMode::None) {
                continue;
            }
            $key = $definition->reverseKey();
            if (isset($prefixes[$key])) {
                throw new InvalidArgumentException("Одинаковый URL-префикс у {$prefixes[$key]} и {$definition->shortcode}.");
            }
            $prefixes[$key] = $definition->shortcode;
        }
    }
}
