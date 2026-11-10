<?php
/**
 * Module Collector Item
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

/**
 * @var  object                     $module
 * @var  \Joomla\Registry\Registry  $params
 * @var  object                     $display  {html, collection, item, link}
 */

$suffix = trim((string) $params->get('moduleclass_sfx', ''));
?>
<div class="mod-collector-item collector-template collector-template-item mod-collector-item-<?php echo (int) $module->id; ?><?php echo $suffix !== '' ? ' ' . htmlspecialchars($suffix, ENT_QUOTES, 'UTF-8') : ''; ?>">
	<?php echo $display->html; ?>
</div>
