<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Shortcode\components\ShortcodeManager;
use Besnovatyj\Shortcode\definitions\ReverseMode;
use Besnovatyj\Shortcode\definitions\ShortcodeDefinition;
use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\forms\backend\ShortcodeForm;
use Besnovatyj\Shortcode\readModels\ShortcodeListReadModel;
use Besnovatyj\Shortcode\services\ShortcodeManageService;
use Besnovatyj\Shortcode\services\ShortcodeCatalog;
use Besnovatyj\Shortcode\repositories\ShortcodeRepository;
use yii\base\InvalidConfigException;
use Besnovatyj\Shortcode\services\ShortcodeRegistry;
use yii\console\Application;
use yii\di\Container;

$vendor = dirname(__DIR__, 3);
require $vendor . '/autoload.php';
require $vendor . '/yiisoft/yii2/Yii.php';

/** Проверка, не зависящая от zend.assertions. */
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** Проверяет отклонение операции и тип исключения. */
function rejects(callable $action, string $class): void
{
    try {
        $action();
    } catch (Throwable $exception) {
        check($exception instanceof $class, 'Неожиданный тип исключения: ' . $exception::class);
        return;
    }
    throw new RuntimeException('Ожидалось исключение ' . $class);
}

/** Адаптация SQL-генератора к современному SQLite только в тестовом окружении. */
final class TestSqliteSchema extends yii\db\sqlite\Schema
{
    /** Возвращает реальный SQL builder с поддержкой современного DROP COLUMN. */
    public function createQueryBuilder()
    {
        return new class($this->db) extends yii\db\sqlite\QueryBuilder {
            /** SQLite >= 3.35 понимает эту операцию без пересоздания таблицы. */
            public function dropColumn($table, $column)
            {
                return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' DROP COLUMN ' . $this->db->quoteColumnName($column);
            }
        };
    }
}

$config = require dirname(__DIR__) . '/src/config/common.php';
// Настоящий Yii2 и SQLite; тест не подключает конфигурацию/БД рабочего приложения.
$config['id'] = 'shortcode-test';
$config['basePath'] = __DIR__;
$config['runtimePath'] = sys_get_temp_dir() . '/shortcode-test-runtime';
$config['modules']['Shortcode'] = ['class' => yii\base\Module::class];
$config['params'] = ['staticHostName' => 'https://files.example.test', 'frontendHostName' => 'https://site.example.test'];
$config['components']['db'] = ['class' => yii\db\Connection::class, 'dsn' => 'sqlite::memory:'];
$config['components']['cache'] = ['class' => yii\caching\ArrayCache::class];
$config['components']['log'] = ['class' => yii\log\Dispatcher::class, 'targets' => []];
// SQLite движка тестов поддерживает DROP COLUMN, но старый QueryBuilder Yii2 запрещает его.
$config['components']['db']['schemaMap'] = ['sqlite' => TestSqliteSchema::class];
$app = new Application($config);
$app->db->createCommand('CREATE TABLE shortcode_shortcodes (id INTEGER PRIMARY KEY AUTOINCREMENT, shortcode VARCHAR(255) UNIQUE, type VARCHAR(255), replacement TEXT, description TEXT, example TEXT)')->execute();
// Воспроизводим запрос до обновления схемы и строку старого формата.
$app->db->createCommand()->insert('shortcode_shortcodes', [
    'shortcode' => '%legacy%', 'type' => 'text', 'replacement' => 'Legacy',
    'description' => '', 'example' => '',
])->execute();
$legacyRow = $app->db->createCommand('SELECT * FROM shortcode_shortcodes')->queryOne();
$repository = Yii::$container->get(ShortcodeRepository::class);
try {
    $repository->findAllRows();
    throw new RuntimeException('Старая схема должна быть явно отклонена.');
} catch (InvalidConfigException $exception) {
    check(str_contains($exception->getMessage(), 'm261115_000001'), 'Нет указания на нужную миграцию.');
}
rejects(fn () => ShortcodeDefinition::fromRow($legacyRow), InvalidConfigException::class);
require dirname(__DIR__) . '/src/migrations/m261115_000001_add_shortcode_reverse_mode.php';
$migration = new Besnovatyj\Shortcode\migrations\m261115_000001_add_shortcode_reverse_mode(['db' => $app->db, 'compact' => true]);
$migration->safeUp();
// Даже под текущим ключом мог сохраниться результат SELECT * до применения миграции.
$app->cache->set(ShortcodeCatalog::CACHE_KEY, [$legacyRow]);
$rows = Yii::$container->get(ShortcodeCatalog::class)->rows();
check($rows[0]['reverse_mode'] === 'none', 'Старый каталог не перечитан из обновлённой БД.');
check($app->cache->get(ShortcodeCatalog::CACHE_KEY)[0]['reverse_mode'] === 'none', 'Кэш не заменён новым форматом.');
check(ShortcodeDefinition::fromRow($rows[0])->reverseMode === ReverseMode::None, 'Значение миграции потеряно.');


$manager = $app->shortcode;
check($manager === Yii::$container->get(ShortcodeManager::class), 'Компонент и DI должны совпадать.');
$registry = Yii::$container->get(ShortcodeRegistry::class);
$manager->registerText('%asset%', 'https://asset.example.test', ReverseMode::UrlPrefix);
check($registry->all()['%asset%']->replacement === 'https://asset.example.test', 'Runtime-регистрация потеряна.');
rejects(fn () => $manager->registerText('%asset%', 'https://asset.example.test'), LogicException::class);
rejects(fn () => $manager->registerText('%staticHost%', 'https://else.example.test'), LogicException::class);
rejects(fn () => $manager->loadTextShortcodes(['%fresh%' => 'fresh', '%asset%' => 'duplicate']), LogicException::class);
check(!isset($registry->all()['%fresh%']), 'Пакетная регистрация должна быть атомарной.');
rejects(fn () => $manager->registerText('%nested%', '%asset%'), InvalidArgumentException::class);
rejects(fn () => $manager->registerText('%root%', '/', ReverseMode::UrlPrefix), InvalidArgumentException::class);
rejects(fn () => $manager->registerText('%duplicate%', 'https://ASSET.example.test:443', ReverseMode::UrlPrefix), InvalidArgumentException::class);
foreach (['https://host.example.test:', 'https://host.example.test:0443', 'https://127.1', 'https://user:pass@host.example.test', 'https://host.example.test?q=1'] as $invalid) {
    rejects(fn () => new ShortcodeDefinition('%bad%', 'text', $invalid, reverseMode: ReverseMode::UrlPrefix), InvalidArgumentException::class);
}
$manager->registerWidget('sampleWidget', stdClass::class);
check($manager->getWidgetClass('sampleWidget') === stdClass::class, 'Виджетная регистрация сломана.');

$form = new ShortcodeForm();
$form->shortcode = '%staticHost%';
$form->type = 'text';
$form->replacement = 'https://override.example.test';
$form->description = '';
$form->example = '';
$form->reverse_mode = 'none';
check($form->validate(), json_encode($form->getErrors(), JSON_UNESCAPED_UNICODE));
$service = Yii::$container->get(ShortcodeManageService::class);
$entity = $service->create($form);
check($manager->getTextReplacement('%staticHost%') === 'https://override.example.test', 'БД должна переопределять код.');
check($registry->all()['%staticHost%']->reverseMode === ReverseMode::None, 'Reverse ошибочно унаследован.');
check($manager->resolveText('%staticHost%/a.jpg %unknown%') === 'https://override.example.test/a.jpg %unknown%', 'Раскрытие не соответствует определению.');
$items = Yii::$container->get(ShortcodeListReadModel::class)->all();
$selected = array_values(array_filter($items, fn ($item) => $item->shortcode === '%staticHost%'))[0];
check($selected->replacement === $manager->getTextReplacement('%staticHost%') && $selected->reverseMode === ReverseMode::None, 'API/read model расходится с менеджером.');
$edit = new ShortcodeForm($entity);
$edit->reverse_mode = 'url-prefix';
check($edit->validate(), json_encode($edit->getErrors()));
$service->edit($entity->id, $edit);
check($registry->all()['%staticHost%']->reverseMode === ReverseMode::UrlPrefix, 'Изменение reverse не видно в текущем запросе.');
$service->remove($entity->id);
check($manager->getTextReplacement('%staticHost%') === 'https://files.example.test', 'Удаление не вернуло встроенное определение.');
rejects(fn () => new ShortcodeDefinition('%bad%', 'text', 'https://example.test/a/../b', reverseMode: ReverseMode::UrlPrefix), InvalidArgumentException::class);
rejects(fn () => new ShortcodeDefinition('widget', 'widget', stdClass::class, reverseMode: ReverseMode::UrlPrefix), InvalidArgumentException::class);
$migration->safeDown();
check($app->db->getTableSchema('shortcode_shortcodes', true) !== null, 'Откат удалил таблицу.');
check(!isset($app->db->getTableSchema('shortcode_shortcodes', true)->columns['reverse_mode']), 'Откат не удалил столбец.');
// Обратный порядок первого обращения не должен создавать отдельный менеджер.
Yii::$container = new Container();
Yii::configure(Yii::$container, $config['container']);
$fromDi = Yii::$container->get(ShortcodeManager::class);
$app->set('shortcode', ['class' => ShortcodeManager::class]);
check($app->shortcode === $fromDi, 'DI-first создаёт другой компонент.');
echo "OK: singleton, registry, priority, validation, CRUD, read model, migration up/down, pre-migration schema, stale cache recovery.\n";
