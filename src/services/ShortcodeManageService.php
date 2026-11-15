<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\services;

use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\forms\backend\ShortcodeForm;
use Besnovatyj\Shortcode\repositories\ShortcodeRepository;
use Throwable;
use Besnovatyj\Shortcode\definitions\ReverseMode;
use yii\db\Exception;
use yii\db\StaleObjectException;

/** CRUD управляемых определений с проверкой эффективного набора. */
class ShortcodeManageService
{
    private ShortcodeRepository $repo;
    private ShortcodeCatalog $catalog;

    /** Зависимости хранения, кэша и эффективного набора. */
    public function __construct(
        ShortcodeRepository $repo,
        ShortcodeCatalog $catalog,
        private readonly ShortcodeRegistry $registry,
    )
    {
        $this->repo = $repo;
        $this->catalog = $catalog;
    }

    /**
     * @throws Exception
     */
    public function create(ShortcodeForm $form): Shortcode
    {
        $this->registry->validateCandidate($form->definition());
        $entity = Shortcode::create(
            $form->shortcode,
            $form->type,
            $form->replacement,
            (string)$form->description,
            (string)$form->example,
            ReverseMode::from($form->reverse_mode),
        );
        $this->repo->save($entity);
        $this->catalog->invalidate();
        return $entity;
    }

    /**
     * @throws Exception
     */
    public function edit($id, ShortcodeForm $form): void
    {
        $this->registry->validateCandidate($form->definition());
        $entity = $this->repo->get($id);
        $entity->edit(
            $form->shortcode,
            $form->type,
            $form->replacement,
            (string)$form->description,
            (string)$form->example,
            ReverseMode::from($form->reverse_mode),
        );
        $this->repo->save($entity);
        $this->catalog->invalidate();
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function remove($id): void
    {
        $this->registry->validateRemoval((int)$id);
        $test = $this->repo->get($id);
        $this->repo->remove($test);
        $this->catalog->invalidate();
    }
}
