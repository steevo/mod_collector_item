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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Steevo\Component\Collector\Administrator\Helper\CollectorTemplateHelper;

/**
 * Données de l'édition du module (collections, objets, champs, balises), envoyées une seule fois
 * au script de l'administration, qui adapte les listes à la collection choisie sans recharger la page.
 */
abstract class CollectorData
{
    protected static bool $loaded = false;

    public static function register(): void
    {
        if (static::$loaded) {
            return;
        }

        static::$loaded = true;

        $app = Factory::getApplication();
        $app->getLanguage()->load('com_collector', JPATH_ADMINISTRATOR);
        $app->getLanguage()->load('mod_collector_item', JPATH_SITE);

        $db          = Factory::getContainer()->get(DatabaseInterface::class);
        $collections = [];

        foreach ($db->setQuery($db->getQuery(true)->select('id')->from('#__collector'))->loadColumn() as $id) {
            $collections[(int) $id] = ['fields' => [], 'tags' => []];

            foreach (CollectorTemplateHelper::getTags((int) $id, CollectorTemplateHelper::CLIENT_ITEM) as [$tag, $label]) {
                $collections[(int) $id]['tags'][] = ['tag' => $tag, 'label' => $label];
            }
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName(['collection', 'field', 'tablecolumn']))
            ->from($db->quoteName('#__collector_fields'))
            ->order($db->quoteName(['collection', 'ordering']));

        foreach ($db->setQuery($query)->loadObjectList() as $field) {
            if (isset($collections[(int) $field->collection]) && $field->tablecolumn !== '') {
                $collections[(int) $field->collection]['fields'][] = ['column' => $field->tablecolumn, 'label' => Text::_($field->field)];
            }
        }

        // les objets (parfois des milliers) sont cherchés à la demande par le composant
        $doc = $app->getDocument();
        $doc->addScriptOptions('mod_collector_item', [
            'collections' => $collections,
            'preview'     => Route::_('index.php?option=com_collector&task=template.preview', false),
            'items'       => Route::_('index.php?option=com_collector&task=template.items&format=raw', false),
            'token'       => Session::getFormToken(),
        ]);

        Text::script('MOD_COLLECTOR_ITEM_FIELD_ITEM_SELECT');
        Text::script('MOD_COLLECTOR_ITEM_ITEM_MORE');
        Text::script('MOD_COLLECTOR_ITEM_FIELD_REQUIRED_NONE');
        Text::script('MOD_COLLECTOR_ITEM_UNPUBLISHED_SUFFIX');
        Text::script('MOD_COLLECTOR_ITEM_STARTER_CONFIRM');
        Text::script('MOD_COLLECTOR_ITEM_ITEM_NO_MATCH');

        $doc->getWebAssetManager()->registerAndUseScript('mod_collector_item.admin', 'mod_collector_item/admin.js', [], ['defer' => true], ['core']);
    }
}
