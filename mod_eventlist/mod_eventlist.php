<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_eventlist
 *
 * @copyright   Copyright (C) 2018 Ulrich Rueth, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

// Include the eventlist functions only once
JLoader::register('ModEventlistHelper', __DIR__ . '/helper.php');

// Get mod_eventlist parameters
$module = JModuleHelper::getModule('mod_eventlist');
$params = new JRegistry($module->params);

$eventList = modEventListHelper::getList($params);
//print_r($eventList);
	
require JModuleHelper::getLayoutPath('mod_eventlist');
