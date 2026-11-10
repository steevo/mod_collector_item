<?php
/**
 * Module Collector Item
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Module\CollectorItem\Site\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

/**
 * Affiche un objet d'une collection (choisi ou tiré au hasard) avec le template du module.
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    protected function getLayoutData()
    {
        $data = parent::getLayoutData();

        $display = $this->getHelperFactory()->getHelper('ItemHelper')
            ->getDisplay($data['params'], $this->getApplication(), (int) $data['module']->id);

        // rien à afficher (collection non visible, aucun objet) : pas de module du tout
        if ($display === null) {
            return false;
        }

        $data['display'] = $display;

        return $data;
    }
}
