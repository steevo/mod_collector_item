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
 * Choix d'un objet de la collection sélectionnée, avec une recherche par titre.
 * La liste est remplie par le script de l'administration (media/mod_collector_item/js/admin.js),
 * qui interroge le composant (task=template.items) : une collection peut compter des milliers d'objets.
 */
class CollectoritemField extends FormField
{
    protected $type = 'Collectoritem';

    protected function getInput()
    {
        CollectorData::register();

        $id    = htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8');
        $value = (int) $this->value;

        return '<div class="mod-collector-item-choice" data-mod-collector-item-choice>'
            . '<input type="search" class="form-control mb-2" id="' . $id . '_search" data-mod-collector-item-search'
            . ' placeholder="' . htmlspecialchars(Text::_('MOD_COLLECTOR_ITEM_FIELD_ITEM_SEARCH'), ENT_QUOTES, 'UTF-8') . '"'
            . ' aria-label="' . htmlspecialchars(Text::_('MOD_COLLECTOR_ITEM_FIELD_ITEM_SEARCH'), ENT_QUOTES, 'UTF-8') . '">'
            . '<select class="form-select" name="' . htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8') . '" id="' . $id . '"'
            . ' data-value="' . $value . '">'
            . '<option value="' . $value . '" selected>' . ($value ? '#' . $value : Text::_('MOD_COLLECTOR_ITEM_FIELD_ITEM_SELECT')) . '</option>'
            . '</select>'
            . '<div class="form-text" data-mod-collector-item-more hidden></div>'
            . '</div>';
    }
}
