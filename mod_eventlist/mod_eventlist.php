<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_eventlist
 *
 * @copyright   Copyright (C) 2018-2019 Ulrich Rueth. All rights reserved.
 * @license     GNU General Public License version 3 or later
 */

// No direct access
defined('_JEXEC') or die;

// Imports
use Joomla\CMS\Helper\ModuleHelper;
use RuethInfo\Module\EventList\Site\Helper\EventListHelper;

// Get articles model
$model = $app->bootComponent('com_content')->getMVCFactory()->createModel('Articles', 'Site', ['ignore_request' => true]);

$eventList = EventListHelper::getList($params, $model);
	
require ModuleHelper::getLayoutPath('mod_eventlist', $params['eventlist_template']);
