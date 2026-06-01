### Пример использования

    (NB: Модули тоже могут регистрировать свои шорткоды, например, в методе Module::init())

1. **Конфигурация компонента** (в `config/web.php`):
   ```php
   'components' => [
       'shortcode' => [
           'class' => 'modules\shortcode\components\ShortcodeManager',
       ],
   ],
   ```

2. **Регистрация шорткодов**:
   ```php
   // Регистрация виджетного шорткода
   \Yii::$app->shortcode->registerWidget('gallery', 'common\widgets\GalleryWidget');

   // Регистрация текстовых шорткодов
   \Yii::$app->shortcode->registerText('homeUrl', 'https://example.com');
   \Yii::$app->shortcode->registerText('siteName', 'My Site');
   ```

3. **Использование в контенте**:
   ```php
   echo \modules\shortcode\widgets\ShortcodeContent::widget([
       'content' => 'Visit %siteName% at %homeUrl% or %unknown%. See our [gallery, id=123, title="Photos", count=5]Photos[/gallery] or [unknown, id="1"].',
   ]);
   ```
   **Результат**:
    - `%siteName%` → `My Site`.
    - `%homeUrl%` → `https://example.com`.
    - `%unknown%` → `%unknown%` (исходная строка, так как шорткод не зарегистрирован).
    - `[gallery, id=123, title="Photos", count=5]Photos[/gallery]` → Вызов `GalleryWidget` с параметрами `id => 123` (число), `title => "Photos"`, `count => 5` (число) и внутренним контентом `Photos`.
    - `[unknown, id="1"]` → `[unknown, id="1"]` (исходная строка, так как виджет не зарегистрирован).

### Дополнительные замечания
1. **Числовые значения**:
    - В текущей реализации числовые значения преобразуются в `int`. Если нужны числа с плавающей точкой (например, `price=19.99`), можно изменить условие в `parseAttributes` на:
      ```php
      if (is_numeric($value)) {
          $value = strpos($value, '.') !== false ? (float)$value : (int)$value;
      }
      ```
2. **Админка**:
    - Для управления текстовыми шорткодами создайте модель и контроллер, чтобы загружать шорткоды через `loadTextShortcodes`.
3. **Логирование**:
    - Если нужно отслеживать неизвестные шорткоды, добавьте логирование:
      ```php
      if ($replacement === null) {
          \Yii::warning("Unknown text shortcode: $shortcode");
          return $matches[0];
      }
      ```
4. **Производительность**:
    - Для больших текстов рассмотрите кэширование результатов обработки шорткодов с помощью `\Yii::$app->cache`.
