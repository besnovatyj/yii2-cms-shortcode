<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\controllers\backend;

use Besnovatyj\Shortcode\services\ShortcodeCacheClearService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Контроллер интеграции модуля шорткодов с модулем очистки (ClearManager).
 *
 * Эндпойнты объявлены в config/config.php (`params.endpoints.clear.catalog`) — их читает
 * {@see \Besnovatyj\ClearManager\services\EndpointCollectorService}; жёсткой зависимости от ClearManager
 * нет (интеграция по конвенции параметров). Формат ответа и обработка ошибок повторяют
 * {@see \Besnovatyj\RouteAlias\controllers\backend\ClearController}:
 *  - все экшены — только POST и только AJAX, ответ в JSON;
 *  - ожидаемые сбои бросаются {@see ServerErrorHttpException}, прочие исключения всплывают к ErrorHandler.
 */
class ClearController extends Controller
{
    private ShortcodeCacheClearService $service;

    public function __construct($id, $module, ShortcodeCacheClearService $service, array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->service = $service;
    }

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['*' => ['POST']],
            ],
        ];
    }

    /**
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        // Формат ставим до parent::beforeAction — чтобы ошибки фильтров (verb) тоже ушли как JSON.
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!Yii::$app->getRequest()->getIsAjax()) {
            throw new BadRequestHttpException('Ожидается AJAX-запрос.');
        }
        return true;
    }

    /**
     * Состояние кэша каталога шорткодов.
     */
    public function actionGetData(): array
    {
        return ['status' => 'success', 'data' => $this->service->getData()];
    }

    /**
     * Сбрасывает кэш каталога шорткодов.
     *
     * @throws ServerErrorHttpException
     */
    public function actionClearData(): array
    {
        if (!$this->service->clearData()) {
            throw new ServerErrorHttpException('Не удалось сбросить кэш каталога шорткодов.');
        }
        return ['status' => 'success', 'message' => 'Кэш каталога шорткодов сброшен'];
    }
}
