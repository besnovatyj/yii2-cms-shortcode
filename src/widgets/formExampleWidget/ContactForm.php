<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\widgets\formExampleWidget;

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
