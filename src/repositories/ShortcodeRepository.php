<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\repositories;

use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\repositories\NotFoundException;
use RuntimeException;
use Throwable;
use yii\db\Exception;
use yii\base\InvalidConfigException;
use yii\db\StaleObjectException;

class ShortcodeRepository
{
    public function get(int $id): Shortcode
    {
        if (!$entity = Shortcode::findOne($id)) {
            throw new NotFoundException('Shortcode is not found.');
        }
        return $entity;
    }

    /**
     * @throws Exception
     */
    public function save(Shortcode $entity): void
    {
        if (!$entity->save()) {
            throw new RuntimeException('Shortcode saving error.');
        }
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function remove(Shortcode $entity): void
    {
        if (!$entity->delete()) {
            throw new RuntimeException('Shortcode removing error.');
        }
    }

    public function findAll(string $type): array
    {
        return Shortcode::findAll(['type' => $type]);
    }

    /**
     * Все шорткоды строками-массивами: сначала по типу, внутри — по имени.
     *
     * Массивы, а не AR-объекты, потому что результат кладётся в кэш каталога
     * ({@see \Besnovatyj\Shortcode\services\ShortcodeCatalog}) — модели там хранить незачем.
     *
     * @return list<array{id:int|string,shortcode:string,type:string,replacement:string,reverse_mode:string,description:?string,example:?string}>
     */
    public function findAllRows(): array
    {
        try {
            // Явный контракт проекции: старая схема должна дать ошибку SQL до записи в кэш,
            // а не неполную строку SELECT *, приводящую позднее к Undefined array key.
            return Shortcode::find()
                ->select(['id', 'shortcode', 'type', 'replacement', 'reverse_mode', 'description', 'example'])
                ->orderBy(['type' => SORT_ASC, 'shortcode' => SORT_ASC])
                ->asArray()
                ->all();
        } catch (Exception $exception) {
            $schema = Shortcode::getDb()->getTableSchema(Shortcode::tableName(), true);
            if ($schema !== null && !isset($schema->columns['reverse_mode'])) {
                throw new InvalidConfigException(
                    'В таблице шорткодов отсутствует reverse_mode. '
                    . 'Примените миграцию Shortcode m261115_000001_add_shortcode_reverse_mode через modman.',
                    0,
                    $exception,
                );
            }
            throw $exception;
        }
    }
}
