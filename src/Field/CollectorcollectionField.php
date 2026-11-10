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

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;

/**
 * Choix de la collection affichée par le module.
 */
class CollectorcollectionField extends ListField
{
    protected $type = 'Collectorcollection';

    protected function getInput()
    {
        CollectorData::register();

        return parent::getInput();
    }

    protected function getOptions()
    {
        $options = parent::getOptions();
        $db      = $this->getDatabase();
        $query   = $db->getQuery(true)
            ->select('id AS value, name AS text, state')
            ->from($db->quoteName('#__collector'))
            ->order('name');

        foreach ($db->setQuery($query)->loadObjectList() as $collection) {
            if ((int) $collection->state !== 1) {
                $collection->text .= ' ' . Text::_('MOD_COLLECTOR_ITEM_UNPUBLISHED_SUFFIX');
            }

            unset($collection->state);
            $options[] = $collection;
        }

        return $options;
    }
}
