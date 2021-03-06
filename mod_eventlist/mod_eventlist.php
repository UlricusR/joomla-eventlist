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

// Include the eventlist functions only once
JLoader::register('ModEventlistHelper', __DIR__ . '/helper.php');
include __DIR__ . '/helper.php';

$eventList = ModEventListHelper::getList($params);
	
require JModuleHelper::getLayoutPath('mod_eventlist', $params['eventlist_template']);
