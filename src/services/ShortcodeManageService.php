<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\services;

use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\forms\backend\ShortcodeForm;
use Besnovatyj\Shortcode\repositories\ShortcodeRepository;
use Throwable;
use yii\db\Exception;
use yii\db\StaleObjectException;

class ShortcodeManageService
{
    private ShortcodeRepository $repo;

    public function __construct(ShortcodeRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @throws Exception
     */
    public function create(ShortcodeForm $form): Shortcode
    {
        $entity = Shortcode::create(
            $form->shortcode,
            $form->type,
            $form->replacement,
            $form->description,
            $form->example,
        );
        $this->repo->save($entity);
        return $entity;
    }

    /**
     * @throws Exception
     */
    public function edit($id, ShortcodeForm $form): void
    {

        $entity = $this->repo->get($id);
        $entity->edit(
            $form->shortcode,
            $form->type,
            $form->replacement,
            $form->description,
            $form->example,
        );
        $this->repo->save($entity);
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function remove($id): void
    {
        $test = $this->repo->get($id);
        $this->repo->remove($test);
    }
}
