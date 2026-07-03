<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\controllers\backend;

use Besnovatyj\Shortcode\forms\backend\ShortcodeForm;
use Besnovatyj\Shortcode\forms\search\ShortcodeSearch;
use Besnovatyj\Shortcode\repositories\ShortcodeRepository;
use Besnovatyj\Shortcode\services\ShortcodeManageService;
use Throwable;
use Yii;
use yii\db\Exception;

use yii\helpers\VarDumper;
use yii\web\Response;

class DefaultController extends \yii\web\Controller
{
    use \Besnovatyj\Kernel\controller\ControllerTrait;

    private ShortcodeManageService $service;
    private ShortcodeRepository $repo;

    public function __construct($id, $module, ShortcodeManageService $service, ShortcodeRepository $repo, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->service = $service;
        $this->repo = $repo;
    }

    public function actionIndex(): string
    {
        $searchModel = new ShortcodeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate(): Response|string
    {
        $form = new ShortcodeForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $shortcode = $this->service->create($form);
                return $this->redirect(['view', 'id' => $shortcode->id]);
            } catch (Exception $e) {
                $this->handleDomainException($e, 'Ошибка');
            }
        }
        return $this->render('create', [
            'model' => $form,
        ]);
    }


    public function actionUpdate(int $id): Response|string
    {
        $shortcode = $this->repo->get($id);

        $form = new ShortcodeForm($shortcode);
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $this->service->edit($shortcode->id, $form);
                return $this->redirect(['view', 'id' => $shortcode->id]);
            } catch (Exception $e) {
                $this->handleDomainException($e, 'Ошибка');
            }
        }
        return $this->render('update', [
            'model' => $form,
            'shortcode' => $shortcode,
        ]);
    }

    public function actionView($id): Response|string
    {
        try {
            $shortcode = $this->repo->get($id);
            return $this->render('view', [
                'shortcode' => $shortcode,
            ]);
        } catch (Throwable $e) {
            Yii::$app->errorHandler->logException($e);
            if (YII_DEBUG) {
                Yii::$app->session->setFlash('error', VarDumper::dumpAsString($e->getMessage()));
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка');
            }
        }
        return $this->redirect(['index']);
    }

    public function actionDelete($id): Response
    {
        try {
            $this->service->remove($id);
        } catch (Throwable $e) {
            Yii::$app->errorHandler->logException($e);
            if (YII_DEBUG) {
                Yii::$app->session->setFlash('error', VarDumper::dumpAsString($e->getMessage()));
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка');
            }
        }
        return $this->redirect(['index']);
    }
}
