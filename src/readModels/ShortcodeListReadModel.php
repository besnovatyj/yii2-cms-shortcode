<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\readModels;

use Besnovatyj\Shortcode\components\ShortcodeManager;
use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\Module;
use Besnovatyj\Shortcode\repositories\ShortcodeRepository;
use Yii;

/**
 * Сводный список всех доступных приложению шорткодов для справочных интерфейсов.
 *
 * Источников два, и они дополняют друг друга:
 *  - БД (через {@see ShortcodeRepository}) — даёт описание, пример вставки и `id` для ссылки на карточку;
 *  - {@see ShortcodeManager} — даёт шорткоды, зарегистрированные в рантайме (`%homeUrl%`, `%staticHost%`
 *    и всё, что модули регистрируют кодом). У них нет карточки в админке, но в подсказке они нужны.
 *
 * При совпадении имени побеждает запись из БД: она содержательнее (описание + пример).
 */
final class ShortcodeListReadModel
{
    public function __construct(
        private readonly ShortcodeRepository $repository,
        private readonly ShortcodeManager $manager,
    ) {
    }

    /**
     * Все шорткоды: сначала виджетные, затем текстовые, внутри группы — по алфавиту.
     *
     * @return ShortcodeItem[]
     */
    public function all(): array
    {
        $items = [];

        // Тот же guard, что и в ShortcodeManager::init(): без зарегистрированного модуля таблицы
        // может не быть (виджет-подсказку способен вызвать сторонний модуль).
        if (Yii::$app->getModule(Module::moduleId()) !== null) {
            foreach ($this->repository->findAllOrdered() as $entity) {
                $items[$entity->shortcode] = ShortcodeItem::fromEntity($entity);
            }
        }

        $items = $this->appendRuntime($items, $this->manager->getWidgetShortcodes(), Shortcode::TYPE_WIDGET);
        $items = $this->appendRuntime($items, $this->manager->getTextShortcodes(), Shortcode::TYPE_TEXT);

        $items = array_values($items);
        usort($items, static fn(ShortcodeItem $a, ShortcodeItem $b): int => [
            $a->isWidget() ? 0 : 1,
            mb_strtolower($a->shortcode),
        ] <=> [
            $b->isWidget() ? 0 : 1,
            mb_strtolower($b->shortcode),
        ]);

        return $items;
    }

    /**
     * То же самое, но разложенное по типам — в таком виде это нужно представлению.
     *
     * @return array{widget: ShortcodeItem[], text: ShortcodeItem[]}
     */
    public function grouped(): array
    {
        $groups = [Shortcode::TYPE_WIDGET => [], Shortcode::TYPE_TEXT => []];

        foreach ($this->all() as $item) {
            $groups[$item->isWidget() ? Shortcode::TYPE_WIDGET : Shortcode::TYPE_TEXT][] = $item;
        }

        return $groups;
    }

    /**
     * Добавление зарегистрированных в коде шорткодов, которых ещё нет в списке.
     *
     * @param array<string, ShortcodeItem> $items      Уже собранные элементы, ключ — имя шорткода
     * @param array<string, string>        $registered Карта `шорткод => замена` из менеджера
     * @param string                       $type       Тип добавляемых элементов
     * @return array<string, ShortcodeItem>
     */
    private function appendRuntime(array $items, array $registered, string $type): array
    {
        foreach ($registered as $shortcode => $replacement) {
            $shortcode = (string)$shortcode;
            if (isset($items[$shortcode])) {
                continue;
            }

            $items[$shortcode] = new ShortcodeItem(
                shortcode: $shortcode,
                type: $type,
                replacement: (string)$replacement,
                description: '',
                example: ShortcodeItem::defaultExample($shortcode, $type),
            );
        }

        return $items;
    }
}
