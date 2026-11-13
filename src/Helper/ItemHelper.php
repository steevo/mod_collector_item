<?php
/**
 * Module Collector Item
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Module\CollectorItem\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Steevo\Component\Collector\Administrator\Helper\CollectorTemplateHelper;

/**
 * Choix de l'objet à afficher et rendu avec le template du module.
 */
class ItemHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /** Format de date qui fixe le tirage pour chaque période (« display » : nouveau tirage à chaque affichage) */
    protected const PERIODS = ['hour' => 'YmdH', 'day' => 'Ymd', 'week' => 'oW'];

    /**
     * Mode « le plus récent » : critère → tri (le premier objet est retenu).
     *  - ordering   : le dernier dans l'ordre de la collection (numéro de bulletin, de souscription…)
     *  - created    : le dernier créé
     *  - publish_up : le dernier publié (date de début de publication, sinon de création)
     */
    public const LATEST_ORDERS = [
        'ordering'   => 'i.ordering DESC, i.id DESC',
        'created'    => 'i.created DESC, i.id DESC',
        'publish_up' => 'COALESCE(i.publish_up, i.created) DESC, i.id DESC',
    ];

    /**
     * HTML du module, ou null s'il n'y a rien à afficher.
     *
     * @return  object|null  {html, css, collection, item}
     */
    public function getDisplay(Registry $params, CMSApplicationInterface $app, int $moduleId): ?object
    {
        $collection = $this->getCollection((int) $params->get('collection'), $app);

        if (!$collection) {
            return null;
        }

        // le composant définit ses constantes (dossiers des fichiers) au démarrage
        $app->bootComponent('com_collector');
        $app->getLanguage()->load('com_collector', JPATH_SITE);

        $fields = $this->getFields((int) $collection->id);
        $item   = match ((string) $params->get('mode', 'random')) {
            'item'   => $this->getItem($collection, $fields, $app, (int) $params->get('item')),
            'latest' => $this->getLatestItem($collection, $fields, $app, $params),
            default  => $this->getRandomItem($collection, $fields, $app, $params, $moduleId),
        };

        if (!$item) {
            return null;
        }

        $link = Route::_(
            'index.php?option=com_collector&view=item&collection=' . $collection->id . ':' . $collection->alias
            . '&id=' . $item->id . ':' . $item->alias
        );

        $content = (string) $params->get('content', '');

        // sans template : le titre de l'objet, avec un lien vers sa fiche
        if (trim($content) === '') {
            $title   = trim(strip_tags((string) $item->fulltitle)) ?: '#' . $item->id;
            $content = '<p class="mod-collector-item-title"><a href="{link}">'
                . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a></p>';
        }

        $template = (object) ['content' => $content, 'css' => (string) $params->get('css', '')];
        $html     = CollectorTemplateHelper::renderItem($template, $fields, $item, ComponentHelper::getParams('com_collector'), $link);

        CollectorTemplateHelper::addCss($template);

        $wa = $app->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_collector');

        if ($wa->assetExists('style', 'com_collector.theme')) {
            $wa->useStyle('com_collector.theme');
        }

        return (object) ['html' => $html, 'collection' => $collection, 'item' => $item, 'link' => $link];
    }

    /**
     * Collection publiée et visible par l'utilisateur.
     */
    protected function getCollection(int $id, CMSApplicationInterface $app): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'alias', 'name']))
            ->from($db->quoteName('#__collector'))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('state') . ' = 1')
            ->whereIn($db->quoteName('access'), $app->getIdentity()->getAuthorisedViewLevels())
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->wherePublished($query, 'publish_up', 'publish_down');

        return $db->setQuery($query)->loadObject() ?: null;
    }

    /**
     * Champs publiés de la collection, comme sur le site.
     */
    protected function getFields(int $collection): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('f.*, t.type AS type')
            ->from($db->quoteName('#__collector_fields', 'f'))
            ->join('LEFT', $db->quoteName('#__collector_fields_type', 't') . ' ON t.id = f.type')
            ->where('f.collection = :collection')
            ->where('f.state = 1')
            ->order('f.ordering')
            ->bind(':collection', $collection, ParameterType::INTEGER);

        $fields = [];

        foreach ($db->setQuery($query)->loadObjectList() as $field) {
            $field->attribs = (new Registry($field->attribs))->toArray();
            $fields[]       = CollectorField::getInstance($collection, $field);
        }

        return $fields;
    }

    /**
     * Objets visibles de la collection, dans leur version en cours (sans les colonnes des champs).
     */
    protected function baseQuery(object $collection, CMSApplicationInterface $app): DatabaseQuery
    {
        $db    = $this->getDatabase();
        $id    = (int) $collection->id;
        $query = $db->getQuery(true)
            ->from($db->quoteName('#__collector_items', 'i'))
            ->join('INNER', $db->quoteName('#__collector_items_history_' . $id, 'h') . ' ON h.item = i.id')
            ->where('i.collection = :collection')
            ->where('i.state = 1')
            ->where('h.state = 1')
            ->whereIn($db->quoteName('i.access'), $app->getIdentity()->getAuthorisedViewLevels())
            ->bind(':collection', $id, ParameterType::INTEGER);

        $this->wherePublished($query, 'i.publish_up', 'i.publish_down');

        return $query;
    }

    /**
     * Ajoute les colonnes de l'objet et de ses champs, puis le charge.
     */
    protected function load(DatabaseQuery $query, array $fields, int $offset = 0): ?object
    {
        $query->select('i.id, i.alias, i.collection, i.fulltitle, i.hits, i.created, i.modified');

        foreach ($fields as $field) {
            $field->setQuery($query);
        }

        return $this->getDatabase()->setQuery($query, $offset, 1)->loadObject() ?: null;
    }

    protected function getItem(object $collection, array $fields, CMSApplicationInterface $app, int $itemId): ?object
    {
        if ($itemId <= 0) {
            return null;
        }

        $query = $this->baseQuery($collection, $app)
            ->where('i.id = :item')
            ->bind(':item', $itemId, ParameterType::INTEGER);

        return $this->load($query, $fields);
    }

    /**
     * Objet tiré au hasard : à chaque affichage, ou le même pendant toute la période choisie.
     */
    protected function getRandomItem(object $collection, array $fields, CMSApplicationInterface $app, Registry $params, int $moduleId): ?object
    {
        $db    = $this->getDatabase();
        $query = $this->baseQuery($collection, $app);

        $this->whereRequired($query, $fields, $params);

        $count = (int) $db->setQuery((clone $query)->select('COUNT(*)'))->loadResult();

        if ($count === 0) {
            return null;
        }

        $period = (string) $params->get('period', 'display');

        // même tirage pour tous les visiteurs pendant la période ; chaque module a le sien
        $offset = isset(static::PERIODS[$period])
            ? crc32(Factory::getDate()->format(static::PERIODS[$period], true) . '-' . $moduleId) % $count
            : random_int(0, $count - 1);

        return $this->load($query->order('i.id'), $fields, $offset);
    }

    /**
     * Objet le plus récent de la collection, selon le critère choisi (voir LATEST_ORDERS).
     */
    protected function getLatestItem(object $collection, array $fields, CMSApplicationInterface $app, Registry $params): ?object
    {
        $query = $this->baseQuery($collection, $app);
        $by    = (string) $params->get('latest_by', 'ordering');

        $this->whereRequired($query, $fields, $params);

        return $this->load($query->order(static::LATEST_ORDERS[$by] ?? static::LATEST_ORDERS['ordering']), $fields);
    }

    /**
     * Seulement les objets dont un champ est rempli (par exemple une photo).
     */
    protected function whereRequired(DatabaseQuery $query, array $fields, Registry $params): void
    {
        $required = (string) $params->get('required_field', '');

        if ($required === '') {
            return;
        }

        $db = $this->getDatabase();

        foreach ($fields as $field) {
            if (($field->_field->tablecolumn ?? '') === $required) {
                $column = $db->quoteName('h.' . $required);
                $query->where($column . ' IS NOT NULL')->where($column . ' <> ' . $db->quote(''));
                break;
            }
        }
    }

    /**
     * Dates de début et de fin de publication.
     */
    protected function wherePublished(DatabaseQuery $query, string $up, string $down): void
    {
        $db       = $this->getDatabase();
        $nullDate = $db->quote($db->getNullDate());
        $now      = $db->quote(Factory::getDate()->toSql());
        $up       = $db->quoteName($up);
        $down     = $db->quoteName($down);

        $query->where('(' . $up . ' IS NULL OR ' . $up . ' = ' . $nullDate . ' OR ' . $up . ' <= ' . $now . ')')
            ->where('(' . $down . ' IS NULL OR ' . $down . ' = ' . $nullDate . ' OR ' . $down . ' >= ' . $now . ')');
    }
}
