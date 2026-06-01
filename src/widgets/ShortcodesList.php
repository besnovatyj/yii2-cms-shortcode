<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Shortcode\widgets;

use Besnovatyj\Shortcode\components\ShortcodeManager;
use yii\bootstrap5\Widget;

/**
 * Выводит полный список всех шорткодов.
 * В разных местах админки в качестве справки, например.
 */
class ShortcodesList extends Widget
{
    private ShortcodeManager $shortcodeManager;

    public function __construct(ShortcodeManager $shortcodeManager, $config = [])
    {
        parent::__construct($config);
        $this->shortcodeManager = $shortcodeManager;
    }

    public function run(): string
    {
        return $this->getShortcodes();
    }

    private function getShortcodes(): string
    {
        $html = '<div class="text-break">';
        foreach ($this->shortcodeManager->getWidgetShortcodes() as $shortcode => $replacement) {
            $html .= '<div>';
            $html .= '<span class="badge bg-secondary">' . $shortcode . '</span> - ';
            $html .= '<small>'. $replacement . '</small>';
            $html .= '</div>';
        }
        foreach ($this->shortcodeManager->getTextShortcodes() as $shortcode => $replacement) {
            $html .= '<div>';
            $html .= '<span class="badge bg-secondary">' . $shortcode . '</span> - ';
            $html .= '<small>'. $replacement . '</small>';
            $html .= '</div>';
        }
        return $html . '</div>';
    }

}
