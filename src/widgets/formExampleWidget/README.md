Пример виджета `ContactFormWidget`, который отображает форму обратной связи и использует **Invisible reCAPTCHA** для
проверки перед отправкой. Виджет будет интегрирован с системой шорткодов и поддерживать формат
`[contactForm, title="Contact Us", recipient="admin@example.com"]`. Форма будет отправлять данные на указанный email, а
Invisible reCAPTCHA обеспечит защиту от ботов без необходимости явного взаимодействия пользователя.

### Предположения и зависимости

1. **Invisible reCAPTCHA**:
    - Используется Google reCAPTCHA v2 Invisible.
    - Требуется регистрация сайта в Google reCAPTCHA для получения `siteKey` и
      `secretKey` (https://www.google.com/recaptcha/admin).
    - Для простоты я покажу, как интегрировать reCAPTCHA с использованием JavaScript и серверной проверки.
2. **Отправка email**:
    - Форма будет отправлять данные на указанный email через Yii2 `mailer`.
    - Предполагается, что компонент `mailer` настроен в конфигурации Yii2.
3. **Стилизация**:
    - Добавлю минимальные CSS-стили для формы.
4. **Шорткод**:
    - Виджет будет поддерживать атрибуты `title` (заголовок формы) и `recipient` (email получателя).

### Код виджета

```php
<?php

namespace common\widgets;

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

        $output .= $form->field($model = new \common\models\ContactForm(), 'name')->textInput(['placeholder' => 'Your Name']);
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
```

### Модель для формы

```php
<?php

namespace common\models;

use Yii;
use yii\base\Model;

class ContactForm extends Model
{
    public $name;
    public $email;
    public $message;
    public $recipient;

    public function rules()
    {
        return [
            [['name', 'email', 'message', 'recipient'], 'required'],
            ['email', 'email'],
            [['name', 'message', 'recipient'], 'string'],
        ];
    }

    /**
     * Отправка email
     * @return bool Успешность отправки
     */
    public function sendEmail(): bool
    {
        if ($this->validate()) {
            return Yii::$app->mailer->compose()
                ->setTo($this->recipient)
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setSubject('Contact Form Submission from ' . $this->name)
                ->setTextBody("From: {$this->name}\nEmail: {$this->email}\nMessage: {$this->message}")
                ->send();
        }
        return false;
    }
}
```

### Контроллер для обработки формы

```php
<?php

namespace frontend\controllers;

use Yii;
use common\models\ContactForm;
use common\widgets\ContactFormWidget;

class SiteController extends \yii\web\Controller
{
    use \common\components\controller\ControllerTrait;

    public function actionContact()
    {
        $model = new ContactForm();
        
        if (Yii::$app->request->isPost) {
            // Проверка reCAPTCHA
            $recaptchaResponse = Yii::$app->request->post('g-recaptcha-response');
            if (!ContactFormWidget::verifyRecaptcha($recaptchaResponse)) {
                Yii::$app->session->setFlash('error', 'reCAPTCHA verification failed.');
                return $this->refresh();
            }

            // Загрузка данных и отправка email
            if ($model->load(Yii::$app->request->post()) && $model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Thank you for your message!');
                return $this->refresh();
            } else {
                Yii::$app->session->setFlash('error', 'Failed to send message.');
            }
        }

        return $this->render('contact', ['model' => $model]);
    }
}
```

### CSS для стилизации формы

```css
.contact-form {
    margin: 20px 0;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    max-width: 500px;
}

.contact-form h2 {
    font-size: 1.5em;
    margin-bottom: 15px;
}

.contact-form-inner .form-group {
    margin-bottom: 15px;
}

.contact-form-inner input,
.contact-form-inner textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 3px;
}

.contact-form-inner textarea {
    resize: vertical;
}

.contact-form-inner .btn {
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.contact-form-inner .btn:hover {
    background-color: #0056b3;
}

.form-content {
    margin-top: 10px;
    font-style: italic;
}
```

### Как использовать виджет с системой шорткодов

1. **Настройка reCAPTCHA**:
    - Зарегистрируйте ваш сайт в Google reCAPTCHA (https://www.google.com/recaptcha/admin).
    - Замените `YOUR_RECAPTCHA_SITE_KEY` и `YOUR_RECAPTCHA_SECRET_KEY` в `ContactFormWidget.php` на ваши ключи.

2. **Настройка mailer** (в `config/web.php`):
   ```php
   'components' => [
       'mailer' => [
           'class' => 'yii\swiftmailer\Mailer',
           'useFileTransport' => false, // Установите false для реальной отправки
           'transport' => [
               'class' => 'Swift_SmtpTransport',
               'host' => 'smtp.example.com',
               'username' => 'your-username',
               'password' => 'your-password',
               'port' => '587',
               'encryption' => 'tls',
           ],
       ],
       'shortcode' => [
           'class' => 'modules\shortcode\components\ShortcodeManager',
       ],
   ],
   'params' => [
       'senderEmail' => 'noreply@example.com',
       'senderName' => 'Your Site',
   ],
   ```

3. **Регистрация виджета**:
   Зарегистрируйте `ContactFormWidget` в вашем коде:
   ```php
   \Yii::$app->shortcode->registerWidget('contactForm', 'common\widgets\ContactFormWidget');
   ```

4. **Подключение стилей**:
   В шаблоне (например, `layouts/main.php`) подключите CSS:
   ```php
   $this->registerCssFile('/css/contact-form.css');
   ```

5. **Использование шорткода**:
   ```php
   echo \modules\shortcode\widgets\ShortcodeContent::widget([
       'content' => 'Get in touch via [contactForm, title="Contact Us", recipient="admin@example.com"]Fill out the form below[/contactForm].',
   ]);
   ```

   **Результат**:
    - Шорткод `[contactForm, title="Contact Us", recipient="admin@example.com"]Fill out the form below[/contactForm]`
      вызовет `ContactFormWidget`.
    - Виджет отобразит форму с заголовком `Contact Us`, отправляющую данные на `admin@example.com`, с Invisible
      reCAPTCHA и внутренним контентом `Fill out the form below`.

### Пример вывода (HTML)

```html

<div class="contact-form">
    <h2>Contact Us</h2>
    <form action="/site/contact" method="post" class="contact-form-inner" data-recaptcha="true">
        <div class="form-group">
            <label for="contactform-name">Name</label>
            <input type="text" id="contactform-name" name="ContactForm[name]" placeholder="Your Name">
        </div>
        <div class="form-group">
            <label for="contactform-email">Email</label>
            <input type="email" id="contactform-email" name="ContactForm[email]" placeholder="Your Email">
        </div>
        <div class="form-group">
            <label for="contactform-message">Message</label>
            <textarea id="contactform-message" name="ContactForm[message]" rows="5"
                      placeholder="Your Message"></textarea>
        </div>
        <input type="hidden" name="recipient" value="admin@example.com">
        <button type="submit" class="btn btn-primary g-recaptcha" data-sitekey="YOUR_RECAPTCHA_SITE_KEY"
                data-callback="onSubmit">Send Message
        </button>
    </form>
    <div class="form-content">Fill out the form below</div>
</div>
```

### Особенности виджета

1. **Invisible reCAPTCHA**:
    - Проверка выполняется автоматически при нажатии кнопки «Send Message».
    - Серверная проверка в `actionContact` использует `verifyRecaptcha` для валидации токена.
2. **Поддержка атрибутов**:
    - `title`: Заголовок формы (экранируется).
    - `recipient`: Email получателя (экранируется).
3. **Внутренний контент**:
    - Если передан контент (например, `Fill out the form below`), он отображается под формой.
4. **Безопасность**:
    - Все строковые параметры экранируются через `Html::encode`.
    - Модель `ContactForm` валидирует входные данные.
    - reCAPTCHA предотвращает спам.
5. **Интеграция с шорткодами**:
    - Виджет полностью совместим с вашей системой шорткодов, обрабатывая атрибуты и внутренний контент.

### Дополнительные рекомендации

1. **Настройка reCAPTCHA**:
    - Убедитесь, что ключи reCAPTCHA корректны. Для тестирования можно использовать локальный домен в Google reCAPTCHA
      Admin.
2. **Обработка ошибок**:
    - Добавьте отображение ошибок формы в представлении `contact.php`:
      ```php
      <?php if (Yii::$app->session->hasFlash('error')): ?>
          <div class="alert alert-danger"><?= Yii::$app->session->getFlash('error') ?></div>
      <?php endif; ?>
      ```
3. **Кастомизация формы**:
    - Добавьте дополнительные поля в модель `ContactForm` (например, `phone`) или атрибуты в шорткод (например,
      `buttonText`).
4. **Логирование**:
    - Логируйте отправленные сообщения:
      ```php
      if ($model->sendEmail()) {
          Yii::info("Contact form sent to {$model->recipient}", __METHOD__);
      }
      ```

