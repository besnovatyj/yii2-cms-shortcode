### Как использовать виджет с системой шорткодов

1. **Конфигурация компонента `ShortcodeManager`** (в `config/web.php`):
   ```php
   'components' => [
       'shortcodeManager' => [
           'class' => 'modules\shortcode\components\ShortcodeManager',
       ],
   ],
   ```

2. **Регистрация виджета**:
   Зарегистрируйте `GalleryWidget` в вашем коде (например, в контроллере или bootstrap-файле):
   ```php
   \Yii::$app->shortcode->registerWidget('gallery', 'common\widgets\GalleryWidget');
   ```

3. **Подключение стилей**:
   Убедитесь, что файл `gallery.css` подключен в вашем шаблоне. Например, в `layouts/main.php`:
   ```php
   <?php
   use yii\helpers\Html;
   $this->registerCssFile('/css/gallery.css');
   ?>
   ```

4. **Использование шорткода**:
   Используйте виджет через шорткод в контенте:
   ```php
   echo \modules\shortcode\widgets\ShortcodeContent::widget([
       'content' => 'Check out our [gallery, galleryId=1, title="Summer Photos", count=4]Description of the gallery[/gallery].',
   ]);
   ```

   **Результат**:
    - Шорткод `[gallery, galleryId=1, title="Summer Photos", count=4]Description of the gallery[/gallery]` вызовет `GalleryWidget`.
    - Виджет отобразит:
        - Заголовок: `Summer Photos`.
        - Четыре изображения (эмулированные URL, например, `https://example.com/images/gallery1/image1.jpg`).
        - Внутренний контент: `Description of the gallery` (экранированный).

### Пример вывода (HTML)
```html
<div class="gallery">
    <h2>Summer Photos</h2>
    <div class="gallery-images">
        <img src="https://example.com/images/gallery1/image1.jpg" alt="Image 1 in gallery 1" class="gallery-image">
        <img src="https://example.com/images/gallery1/image2.jpg" alt="Image 2 in gallery 1" class="gallery-image">
        <img src="https://example.com/images/gallery1/image3.jpg" alt="Image 3 in gallery 1" class="gallery-image">
        <img src="https://example.com/images/gallery1/image4.jpg" alt="Image 4 in gallery 1" class="gallery-image">
    </div>
    <div class="gallery-content">Description of the gallery</div>
</div>
```

### Особенности виджета
1. **Поддержка атрибутов**:
    - `galleryId`: Числовой идентификатор галереи (преобразуется в `int` благодаря `parseAttributes`).
    - `title`: Строковый заголовок (экранируется через `Html::encode`).
    - `count`: Количество изображений (числовой параметр, минимум 1).
2. **Внутренний контент**:
    - Если шорткод содержит внутренний контент (например, `Description of the gallery`), он отображается в блоке `<div class="gallery-content">`.
3. **Эмуляция данных**:
    - Метод `getImages` эмулирует загрузку изображений. В реальном проекте замените его на запрос к базе данных или модели (например, `Gallery::find()->where(['galleryId' => $galleryId])->limit($count)->all()`).
4. **Безопасность**:
    - Все строковые параметры экранируются через `Html::encode` для предотвращения XSS.
    - Числовые параметры валидируются и преобразуются в `int`.

### Интеграция с системой шорткодов
- Виджет полностью совместим с вашей системой шорткодов, так как принимает параметры, переданные через шорткод `[gallery, ...]`, и обрабатывает внутренний контент, если он есть.
- Благодаря доработанному методу `parseAttributes` виджет корректно обрабатывает числовые значения (например, `galleryId=123`, `count=5`) и строковые значения без кавычек (например, `title=Photos`).

### Дополнительные рекомендации
1. **Реальные данные**:
    - Подключите модель для загрузки изображений из базы данных. Например:
      ```php
      private function getImages(int $galleryId, int $count): array
      {
          $images = GalleryImage::find()->where(['gallery_id' => $galleryId])->limit($count)->all();
          return array_map(function ($image) {
              return ['url' => $image->url, 'alt' => $image->alt];
          }, $images);
      }
      ```
2. **Кэширование**:
    - Если галерея редко меняется, используйте фрагментное кэширование:
      ```php
      if ($this->beginCache("gallery_{$galleryId}_{$count}", ['duration' => 3600])) {
          echo $output;
          $this->endCache();
      }
      ```
3. **Расширенные параметры**:
    - Добавьте поддержку дополнительных атрибутов, таких как `class` для кастомизации стилей или `size` для изменения размера изображений.
