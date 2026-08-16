<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\readModels\ShortcodeItem;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

/** @var View            $this */
/** @var ShortcodeItem[] $widgets     Виджетные шорткоды */
/** @var ShortcodeItem[] $texts       Текстовые шорткоды */
/** @var int             $total       Общее количество */
/** @var string          $modalId     Уникальный ID модалки (по $this->id виджета) */
/** @var string          $buttonLabel Подпись кнопки-триггера */
/** @var string          $buttonClass CSS-классы кнопки-триггера */
/** @var string          $buttonIcon  Класс иконки кнопки-триггера */
/** @var bool            $showCounter Показывать счётчик в кнопке */
/** @var string          $modalTitle  Заголовок модалки */
/** @var string          $routePrefix Префикс маршрутов админки модуля, например `/Shortcode/backend/default/` */
/** @var string|null     $indexUrl    Ссылка на главную модуля шорткодов (null — скрыта/нет прав) */
/** @var bool            $canView     Есть ли право открывать карточку шорткода */
/** @var bool            $canUpdate   Есть ли право редактировать шорткод */

/**
 * Разметка одного шорткода. Вынесена в замыкание, чтобы обе группы (виджетные и текстовые)
 * рендерились одинаково, без дублирования шаблона.
 */
$renderItem = static function (ShortcodeItem $item) use ($canView, $canUpdate, $routePrefix): string {
    $viewUrl = $canView && $item->isManaged() ? Url::to([$routePrefix . 'view', 'id' => $item->id]) : null;
    $updateUrl = $canUpdate && $item->isManaged() ? Url::to([$routePrefix . 'update', 'id' => $item->id]) : null;

    ob_start();
    ?>
    <div class="sc-item card mb-2"
         data-sc-item
         data-sc-item-type="<?= Html::encode($item->type) ?>"
         data-sc-search="<?= Html::encode($item->searchIndex()) ?>">
        <div class="card-body p-2">

            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button"
                        class="btn btn-sm btn-outline-secondary font-monospace py-0"
                        data-sc-copy="<?= Html::encode($item->shortcode) ?>"
                        title="Скопировать имя шорткода">
                    <?= Html::encode($item->shortcode) ?>
                </button>

                <span class="badge rounded-pill <?= $item->isWidget() ? 'text-bg-primary' : 'text-bg-success' ?>">
                    <?= $item->isWidget() ? 'виджет' : 'текст' ?>
                </span>

                <?php if (!$item->isManaged()): ?>
                    <span class="badge rounded-pill text-bg-warning"
                          title="Шорткод зарегистрирован в коде, записи в модуле шорткодов у него нет">
                        в коде
                    </span>
                <?php endif; ?>

                <div class="ms-auto d-flex align-items-center gap-2">
                    <?php if ($updateUrl !== null): ?>
                        <a class="sc-link" href="<?= Html::encode($updateUrl) ?>" target="_blank" rel="noopener"
                           title="Редактировать шорткод">
                            <i class="bi bi-pencil"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($viewUrl !== null): ?>
                        <a class="sc-link" href="<?= Html::encode($viewUrl) ?>" target="_blank" rel="noopener"
                           title="Открыть карточку шорткода в модуле">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-2 small">
                <?php if ($item->description !== ''): ?>
                    <?= Html::encode($item->description) ?>
                <?php elseif ($updateUrl !== null): ?>
                    <span class="text-muted fst-italic">Описание не задано —
                        <a href="<?= Html::encode($updateUrl) ?>" target="_blank" rel="noopener">добавить</a>
                    </span>
                <?php else: ?>
                    <span class="text-muted fst-italic">Описание не задано</span>
                <?php endif; ?>
            </div>

            <div class="sc-example position-relative mt-2">
                <code class="sc-example-code d-block border rounded bg-body-tertiary text-body small"
                      data-sc-select
                      title="Клик выделяет пример целиком"><?= Html::encode($item->example) ?></code>
                <button type="button"
                        class="btn btn-sm btn-primary sc-copy-btn position-absolute top-0 end-0 m-1"
                        data-sc-copy="<?= Html::encode($item->example) ?>"
                        title="Скопировать пример вставки">
                    <i class="bi bi-clipboard"></i><span class="d-none d-sm-inline ms-1">Копировать</span>
                </button>
            </div>

            <div class="sc-replacement text-muted font-monospace text-truncate mt-1"
                 title="<?= Html::encode($item->replacement) ?>">
                <i class="bi bi-arrow-return-right"></i> <?= Html::encode($item->replacement) ?>
            </div>

        </div>
    </div>
    <?php
    return (string)ob_get_clean();
};

/**
 * Группа шорткодов с прилипающим заголовком. Скрывается целиком, если фильтр не оставил в ней ничего.
 *
 * @param ShortcodeItem[] $items
 */
$renderGroup = static function (string $type, string $title, array $items) use ($renderItem): string {
    if ($items === []) {
        return '';
    }

    ob_start();
    ?>
    <section class="sc-group" data-sc-group="<?= Html::encode($type) ?>">
        <h6 class="sc-group-title d-flex align-items-center gap-2 py-2 mb-2 position-sticky top-0">
            <?= Html::encode($title) ?>
            <span class="badge rounded-pill text-bg-light border" data-sc-group-count><?= count($items) ?></span>
        </h6>
        <?php foreach ($items as $item): ?>
            <?= $renderItem($item) ?>
        <?php endforeach; ?>
    </section>
    <?php
    return (string)ob_get_clean();
};

$config = [
    'copiedLabel' => 'Скопировано',
    'failedLabel' => 'Не удалось',
];
?>

<span class="shortcodes-list-widget">
    <button type="button"
            class="<?= Html::encode($buttonClass) ?>"
            data-bs-toggle="modal"
            data-bs-target="#<?= Html::encode($modalId) ?>">
        <i class="<?= Html::encode($buttonIcon) ?> me-1"></i><?= Html::encode($buttonLabel) ?>
        <?php if ($showCounter): ?>
            <span class="badge rounded-pill text-bg-secondary ms-1"><?= $total ?></span>
        <?php endif; ?>
    </button>
</span>

<div class="modal fade shortcodes-list-modal"
     id="<?= Html::encode($modalId) ?>"
     tabindex="-1"
     aria-labelledby="<?= Html::encode($modalId) ?>-label"
     aria-hidden="true"
     data-config="<?= Html::encode(Json::encode($config)) ?>">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="<?= Html::encode($modalId) ?>-label">
                    <i class="bi bi-braces-asterisk me-2"></i><?= Html::encode($modalTitle) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>

            <div class="sc-toolbar d-flex flex-wrap align-items-center gap-2 px-3 py-2 border-bottom bg-body-tertiary">
                <div class="input-group input-group-sm sc-search">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search"
                           class="form-control"
                           placeholder="Поиск по имени, описанию, примеру…"
                           aria-label="Поиск шорткода"
                           data-sc-search-input>
                </div>

                <div class="btn-group btn-group-sm" role="group" aria-label="Фильтр по типу">
                    <input type="radio" class="btn-check" name="<?= Html::encode($modalId) ?>-type"
                           id="<?= Html::encode($modalId) ?>-type-all" value="" checked data-sc-type>
                    <label class="btn btn-outline-secondary" for="<?= Html::encode($modalId) ?>-type-all">Все</label>

                    <input type="radio" class="btn-check" name="<?= Html::encode($modalId) ?>-type"
                           id="<?= Html::encode($modalId) ?>-type-widget"
                           value="<?= Shortcode::TYPE_WIDGET ?>" data-sc-type>
                    <label class="btn btn-outline-primary"
                           for="<?= Html::encode($modalId) ?>-type-widget">Виджеты</label>

                    <input type="radio" class="btn-check" name="<?= Html::encode($modalId) ?>-type"
                           id="<?= Html::encode($modalId) ?>-type-text"
                           value="<?= Shortcode::TYPE_TEXT ?>" data-sc-type>
                    <label class="btn btn-outline-success"
                           for="<?= Html::encode($modalId) ?>-type-text">Текстовые</label>
                </div>

                <?php if ($indexUrl !== null): ?>
                    <a class="btn btn-sm btn-outline-secondary ms-auto"
                       href="<?= Html::encode($indexUrl) ?>" target="_blank" rel="noopener"
                       title="Открыть модуль шорткодов в новой вкладке">
                        <i class="bi bi-gear me-1"></i>Модуль шорткодов
                    </a>
                <?php endif; ?>
            </div>

            <div class="modal-body pt-1">
                <?php if ($total === 0): ?>
                    <div class="alert alert-warning mb-0">
                        Шорткоды пока не заведены.
                        <?php if ($indexUrl !== null): ?>
                            <a href="<?= Html::encode($indexUrl) ?>" target="_blank" rel="noopener">Добавить первый</a>.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?= $renderGroup(Shortcode::TYPE_WIDGET, 'Виджетные шорткоды', $widgets) ?>
                    <?= $renderGroup(Shortcode::TYPE_TEXT, 'Текстовые шорткоды', $texts) ?>
                    <div class="alert alert-light border text-center mb-0 d-none" data-sc-empty>
                        Ничего не найдено — попробуйте изменить запрос.
                    </div>
                <?php endif; ?>
            </div>

            <div class="modal-footer justify-content-between">
                <small class="text-muted">
                    Кликните по примеру, чтобы выделить его целиком, или скопируйте кнопкой.
                </small>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>

        </div>
    </div>
</div>
