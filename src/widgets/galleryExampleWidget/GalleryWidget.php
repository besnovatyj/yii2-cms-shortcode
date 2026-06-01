<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\widgets\galleryExampleWidget;

use yii\base\Widget;
use yii\helpers\Html;

class GalleryWidget extends Widget
{
    /** @var int ID галереи */
    public $galleryId;

    /** @var string Заголовок галереи */
    public $title = 'Gallery';

    /** @var int Количество изображений для отображения */
    public $count = 3;

    /** @var string Внутренний контент (опционально) */
    public $content;

    public function run(): string
    {
        // Валидация параметров
        $id = (int)($this->galleryId ?? 0);
        $title = Html::encode($this->title);
        $count = max(1, (int)($this->count ?? 3)); // Минимальное количество изображений: 1

        // Формирование HTML для галереи
        $output = '<div class="gallery">';
        $output .= '<h4>' . $title . '</h4>';

        // Эмуляция загрузки изображений (в реальном проекте можно использовать модель или данные из базы)
        $images = $this->getImages($id, $count);
        $output .= '<div class="gallery-images">';
        foreach ($images as $image) {
            $output .= Html::img($image['url'], ['alt' => Html::encode($image['alt']), 'class' => 'gallery-image']);
        }
        $output .= '</div>';

        // Добавление внутреннего контента, если он есть
        if (!empty($this->content)) {
            $output .= '<div class="gallery-content">' . Html::encode($this->content) . '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Получение изображений для галереи с использованием сервиса Lorem Picsum
     * @param int $id ID галереи
     * @param int $count Количество изображений
     * @return array Массив изображений
     */
    private function getImages(int $id, int $count): array
    {
        $images = [];
        for ($i = 1; $i <= $count; $i++) {
            // Используем Lorem Picsum для генерации случайных изображений
            // Формат: https://picsum.photos/id/{уникальный_ид}/800/600
            $imageId = ($id * 100) + $i; // Уникальный ID для изображения
            $images[] = [
                'url' => "https://picsum.photos/id/{$imageId}/250/200",
                'alt' => "Placeholder image {$i} in gallery {$id}",
            ];
        }
        return $images;
    }
}
