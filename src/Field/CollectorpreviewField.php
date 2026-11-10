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
 * Aperçu du template, rendu par le composant avec un objet de la collection.
 */
class CollectorpreviewField extends FormField
{
    protected $type = 'Collectorpreview';

    protected function getInput()
    {
        CollectorData::register();

        $title = htmlspecialchars(Text::_('MOD_COLLECTOR_ITEM_PREVIEW'), ENT_QUOTES, 'UTF-8');

        return '<div class="mod-collector-item-preview w-100">'
            . '<div class="d-flex flex-wrap align-items-center gap-2 mb-2">'
            . '<p class="small text-muted mb-0 flex-grow-1">' . Text::_('MOD_COLLECTOR_ITEM_PREVIEW_HELP') . '</p>'
            . '<button type="button" class="btn btn-outline-secondary btn-sm" data-mod-collector-item-reroll hidden>'
            . '<span class="icon-refresh" aria-hidden="true"></span> ' . Text::_('MOD_COLLECTOR_ITEM_PREVIEW_OTHER') . '</button>'
            . '<span class="spinner-border spinner-border-sm text-secondary" role="status" data-mod-collector-item-wait hidden>'
            . '<span class="visually-hidden">' . Text::_('JGLOBAL_LOADING') . '</span></span>'
            . '</div>'
            . '<iframe title="' . $title . '" sandbox="allow-same-origin" data-mod-collector-item-frame'
            . ' class="w-100 border rounded bg-white" style="min-height:150px;height:250px"></iframe>'
            . '</div>';
    }
}
