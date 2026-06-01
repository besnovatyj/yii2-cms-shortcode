<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\widgets\formExampleWidget;

use Besnovatyj\Shortcode\widgets\formExampleWidget\ContactForm;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

class ContactFormWidget extends Widget
{
    /** @var string Заголовок формы */
    public $title = 'Contact Us';

    /** @var string Email получателя */
    public $recipient = 'admin@example.com';

    /** @var string Внутренний контент (опционально) */
    public $content;

    /** @var string Ключ сайта для reCAPTCHA */
    public $recaptchaSiteKey = 'YOUR_RECAPTCHA_SITE_KEY'; // Замените на ваш ключ

    /** @var string Секретный ключ для reCAPTCHA */
    private $recaptchaSecretKey = 'YOUR_RECAPTCHA_SECRET_KEY'; // Замените на ваш ключ

    public function init()
    {
        parent::init();
        // Регистрация скрипта reCAPTCHA
        $this->view->registerJsFile('https://www.google.com/recaptcha/api.js', ['async' => true, 'defer' => true]);
    }

    public function run(): string
    {
        $title = Html::encode($this->title);
        $recipient = Html::encode($this->recipient);

        // Формирование HTML для формы
        $output = '<div class="contact-form">';
        $output .= '<h2>' . $title . '</h2>';

        // Обработка формы
        $form = ActiveForm::begin([
            'action' => Url::to(['site/contact']),
            'method' => 'post',
            'options' => ['class' => 'contact-form-inner', 'data-recaptcha' => true],
        ]);

        $output .= $form->field($model = new ContactForm(), 'name')->textInput(['placeholder' => 'Your Name']);
        $output .= $form->field($model, 'email')->input('email', ['placeholder' => 'Your Email']);
        $output .= $form->field($model, 'message')->textarea(['rows' => 5, 'placeholder' => 'Your Message']);
        $output .= Html::hiddenInput('recipient', $recipient);

        // Кнопка отправки с поддержкой reCAPTCHA
        $output .= Html::submitButton('Send Message', [
            'class' => 'btn btn-primary g-recaptcha',
            'data-sitekey' => $this->recaptchaSiteKey,
            'data-callback' => 'onSubmit',
        ]);

        ActiveForm::end();

        // Добавление внутреннего контента, если он есть
        if (!empty($this->content)) {
            $output .= '<div class="form-content">' . Html::encode($this->content) . '</div>';
        }

        $output .= '</div>';

        // Регистрация JavaScript для обработки reCAPTCHA
        $this->registerJs();

        return $output;
    }

    /**
     * Регистрация JavaScript для обработки Invisible reCAPTCHA
     */
    private function registerJs(): void
    {
        $js = <<<JS
        function onSubmit(token) {
            document.querySelector('.contact-form-inner').submit();
        }
        JS;
        $this->view->registerJs($js);
    }

    /**
     * Проверка reCAPTCHA на сервере (вызывается в контроллере)
     * @param string $recaptchaResponse Токен reCAPTCHA
     * @return bool Результат проверки
     */
    public static function verifyRecaptcha($recaptchaResponse): bool
    {
        $secretKey = 'YOUR_RECAPTCHA_SECRET_KEY'; // Замените на ваш ключ
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secretKey,
            'response' => $recaptchaResponse,
            'remoteip' => Yii::$app->request->userIP,
        ];

        $response = file_get_contents($url . '?' . http_build_query($data));
        $result = json_decode($response, true);

        return $result['success'] ?? false;
    }
}
