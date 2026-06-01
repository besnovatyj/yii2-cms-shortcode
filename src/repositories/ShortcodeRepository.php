<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\repositories;

use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\repositories\NotFoundException;
use RuntimeException;
use Throwable;
use yii\db\Exception;
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
}
