<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Shortcode\definitions;

/** Разрешённые способы создания текстового шорткода из готового значения. */
enum ReverseMode: string
{
    case None = 'none';
    case UrlPrefix = 'url-prefix';
}
