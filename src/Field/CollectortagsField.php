<?php
/**
 * Module Collector Item
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Module\CollectorItem\Site\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

/**
 * Aide à la saisie du template : balises de la collection, modèle de départ et rappel de la syntaxe.
 */
class CollectortagsField extends FormField
{
    protected $type = 'Collectortags';

    protected function getInput()
    {
        CollectorData::register();

        $syntax = [
            '{champ}'                => 'COM_COLLECTOR_TEMPLATE_SYNTAX_FIELD',
            '{champ:label}'          => 'COM_COLLECTOR_TEMPLATE_SYNTAX_LABEL',
            '{champ:text}'           => 'COM_COLLECTOR_TEMPLATE_SYNTAX_TEXT',
            '{champ:url}'            => 'COM_COLLECTOR_TEMPLATE_SYNTAX_URL',
            '{champ:value}'          => 'COM_COLLECTOR_TEMPLATE_SYNTAX_VALUE',
            '{if:champ}…{else}…{/if}' => 'COM_COLLECTOR_TEMPLATE_SYNTAX_IF',
            '{ifnot:champ}…{/if}'    => 'COM_COLLECTOR_TEMPLATE_SYNTAX_IFNOT',
        ];

        $html = '<div class="mod-collector-item-tags">'
            . '<p class="small text-muted mb-2">' . Text::_('COM_COLLECTOR_TEMPLATE_TAGS_HELP') . '</p>'
            . '<div class="d-flex flex-wrap gap-1 mb-2" data-mod-collector-item-tags></div>'
            . '<p class="small text-muted mb-2" data-mod-collector-item-tags-empty>' . Text::_('MOD_COLLECTOR_ITEM_TAGS_SELECT_COLLECTION') . '</p>'
            . '<button type="button" class="btn btn-secondary btn-sm" data-mod-collector-item-starter>'
            . '<span class="icon-wand" aria-hidden="true"></span> ' . Text::_('COM_COLLECTOR_TEMPLATE_STARTER') . '</button>'
            . '<div class="alert alert-warning mt-2 mb-0" role="status" data-mod-collector-item-unknown hidden>'
            . '<span class="icon-warning" aria-hidden="true"></span> ' . Text::_('COM_COLLECTOR_TEMPLATE_UNKNOWN_TAGS')
            . ' <span data-mod-collector-item-unknown-list></span></div>'
            . '<details class="mt-2"><summary class="small">' . Text::_('COM_COLLECTOR_TEMPLATE_SYNTAX') . '</summary>'
            . '<dl class="small mb-0 mt-2">';

        foreach ($syntax as $tag => $key) {
            $html .= '<dt><code>' . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') . '</code></dt><dd>' . Text::_($key) . '</dd>';
        }

        return $html . '</dl></details></div>';
    }
}
