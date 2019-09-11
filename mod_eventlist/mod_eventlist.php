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
use Joomla\Registry\Registry;

// Include the eventlist functions only once
JLoader::register('ModEventlistHelper', __DIR__ . '/helper.php');
include __DIR__ . '/helper.php';

// Get mod_eventlist parameters
$module = ModuleHelper::getModule('mod_eventlist');
$params = new Registry($module->params);

$eventList = ModEventListHelper::getList($params);
	
require ModuleHelper::getLayoutPath('mod_eventlist', $params['eventlist_template']);
