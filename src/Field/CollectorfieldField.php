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
 * Choix d'un champ de la collection sélectionnée (rempli par le script de l'administration).
 */
class CollectorfieldField extends FormField
{
    protected $type = 'Collectorfield';

    protected function getInput()
    {
        CollectorData::register();

        $value = htmlspecialchars((string) $this->value, ENT_QUOTES, 'UTF-8');

        return '<select class="form-select" name="' . htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8') . '"'
            . ' id="' . htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8') . '" data-mod-collector-item-field data-value="' . $value . '">'
            . '<option value="">' . Text::_('MOD_COLLECTOR_ITEM_FIELD_REQUIRED_NONE') . '</option>'
            . ($value !== '' ? '<option value="' . $value . '" selected>' . $value . '</option>' : '')
            . '</select>';
    }
}
