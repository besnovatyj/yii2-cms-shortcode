<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\controllers\backend;

use Besnovatyj\Shortcode\readModels\ShortcodeItem;
use Besnovatyj\Shortcode\readModels\ShortcodeListReadModel;
use Yii;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * JSON-эндпоинт списка шорткодов для пикеров в редакторах контента.
 *
 * Потребитель — плагин `shortcodes` виджета Jodit (пакет besnovatyj/yii2-cms-jodit), по образцу
 * эндпоинта дерева сниппетов {@see \Besnovatyj\Snippets\controllers\backend\ApiController}.
 * Отдаёт плоский список: пикеру нужны только имя, тип, описание и готовый пример вставки.
 *
 * Данные берутся из {@see ShortcodeListReadModel}, то есть включают и шорткоды, зарегистрированные
 * в коде, и читаются из кэша каталога — запроса к БД на обращение нет. Только чтение (GET),
 * поэтому CSRF не требуется.
 */
class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function __construct(
        $id,
        $module,
        private readonly ShortcodeListReadModel $readModel,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'only' => ['list'],
                'formats' => ['application/json' => Response::FORMAT_JSON],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['list' => ['GET']],
            ],
        ];
    }

    /**
     * Список шорткодов: `{ items: [{ shortcode, type, description, example, replacement }] }`.
     *
     * @return array{items: array<int, array<string, string>>}
     */
    public function actionList(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'items' => array_map($this->itemToArray(...), $this->readModel->all()),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function itemToArray(ShortcodeItem $item): array
    {
        return [
            'shortcode' => $item->shortcode,
            'type' => $item->type,
            'description' => $item->description,
            'example' => $item->example,
            'replacement' => $item->replacement,
        ];
    }
}
