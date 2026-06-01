<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\widgets\formExampleWidget;

use Besnovatyj\Shortcode\widgets\formExampleWidget\ContactForm;
use Besnovatyj\Shortcode\widgets\formExampleWidget\ContactFormWidget;
use Yii;


class SiteController extends \yii\web\Controller
{
    public function actionContact()
    {
        $model = new ContactForm();

        if (Yii::$app->request->isPost) {
            // Проверка reCAPTCHA
            $recaptchaResponse = Yii::$app->request->post('g-recaptcha-response');
            if (!ContactFormWidget::verifyRecaptcha($recaptchaResponse)) {
                Yii::$app->session->setFlash('error', 'reCAPTCHA verification failed.');
                return $this->goReferer();
            }

            // Загрузка данных и отправка email
            if ($model->load(Yii::$app->request->post()) && $model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Thank you for your message!');
                return $this->goReferer();
            } else {
                Yii::$app->session->setFlash('error', 'Failed to send message.');
            }
        }

        return $this->render('contact', ['model' => $model]);
    }
}
