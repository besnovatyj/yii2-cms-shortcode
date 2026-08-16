<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\widgets;

use Besnovatyj\Kernel\security\AccessHelper;
use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\Module;
use Besnovatyj\Shortcode\readModels\ShortcodeListReadModel;
use Besnovatyj\Shortcode\widgets\shortcodesList\assets\Assets;
use yii\bootstrap5\Widget;
use yii\helpers\Url;

/**
 * Справочник шорткодов: кнопка, открывающая модалку со всеми доступными шорткодами.
 *
 * Подключается в любом месте админки, где редактируется контент. В модалке по каждому шорткоду
 * показаны описание, готовый пример вставки с кнопкой «копировать» и — мелкой подсказкой — то,
 * чем шорткод заменяется (строка или класс виджета). Смысл в том, чтобы копировать пример прямо
 * отсюда, не уходя в модуль шорткодов; ссылки на модуль и на карточку конкретного шорткода
 * оставлены для случаев, когда всё-таки нужно посмотреть или поправить запись.
 *
 * В список попадают и шорткоды, зарегистрированные в коде (см. {@see ShortcodeListReadModel}),
 * они помечены отдельным бейджем и не имеют ссылки на карточку.
 *
 * Пример использования:
 * ```php
 * echo \Besnovatyj\Shortcode\widgets\ShortcodesList::widget();
 * echo \Besnovatyj\Shortcode\widgets\ShortcodesList::widget([
 *     'buttonLabel' => 'Справка по шорткодам',
 *     'buttonClass' => 'btn btn-outline-secondary',
 * ]);
 * ```
 *
 * Класс намеренно остался в `widgets/` (а не переехал в подпапку `widgets/shortcodesList/`),
 * чтобы не ломать FQCN, на который уже ссылаются вьюхи других модулей; вьюха и ассеты виджета
 * лежат рядом по конвенции пакета.
 */
class ShortcodesList extends Widget
{
    /** @var string Подпись кнопки, открывающей модалку */
    public string $buttonLabel = 'Шорткоды';

    /** @var string CSS-классы кнопки — чтобы виджет вписывался в панель кнопок вызывающей страницы */
    public string $buttonClass = 'btn btn-outline-info btn-sm';

    /** @var string Класс иконки кнопки (Bootstrap Icons) */
    public string $buttonIcon = 'bi bi-braces-asterisk';

    /** @var bool Показывать ли в кнопке счётчик доступных шорткодов */
    public bool $showCounter = true;

    /** @var string Заголовок модального окна */
    public string $modalTitle = 'Доступные шорткоды';

    /** @var bool Показывать ли в модалке кнопку перехода на главную модуля шорткодов */
    public bool $showModuleLink = true;

    public function __construct(
        private readonly ShortcodeListReadModel $readModel,
        $config = [],
    ) {
        parent::__construct($config);
    }

    public function run(): string
    {
        Assets::register($this->view);

        $groups = $this->readModel->grouped();

        return $this->render('shortcodes-list', [
            'widgets' => $groups[Shortcode::TYPE_WIDGET],
            'texts' => $groups[Shortcode::TYPE_TEXT],
            'total' => count($groups[Shortcode::TYPE_WIDGET]) + count($groups[Shortcode::TYPE_TEXT]),
            'modalId' => 'shortcodes-list-' . $this->id,
            'buttonLabel' => $this->buttonLabel,
            'buttonClass' => $this->buttonClass,
            'buttonIcon' => $this->buttonIcon,
            'showCounter' => $this->showCounter,
            'modalTitle' => $this->modalTitle,
            'routePrefix' => self::route(''),
            'indexUrl' => $this->showModuleLink && AccessHelper::checkRoute(self::route('index'))
                ? Url::to([self::route('index')])
                : null,
            'canView' => AccessHelper::checkRoute(self::route('view')),
            'canUpdate' => AccessHelper::checkRoute(self::route('update')),
        ]);
    }

    /**
     * Абсолютный маршрут действия админки модуля шорткодов.
     */
    private static function route(string $action): string
    {
        return '/' . Module::moduleId() . '/backend/default/' . $action;
    }
}
