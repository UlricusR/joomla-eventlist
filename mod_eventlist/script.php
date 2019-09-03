<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_eventlist
 *
 * @copyright   Copyright (C) 2018-2019 Ulrich Rueth. All rights reserved.
 * @license     GNU General Public License version 3 or later
 */

// No direct access to this file
defined('_JEXEC') or die;

// Imports
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Script file of EventList module
 */
 
class mod_eventListInstallerScript
{
	/**
	 * Method to install the extension
	 * $parent is the class calling this method
	 *
	 * @return void
	 */
	function install($parent) 
	{
		echo '<p>The module has been installed</p>';
	}

	/**
	 * Method to uninstall the extension
	 * $parent is the class calling this method
	 *
	 * @return void
	 */
	function uninstall($parent) 
	{
		echo '<p>The module has been uninstalled</p>';
	}

	/**
	 * Method to update the extension
	 * $parent is the class calling this method
	 *
	 * @return void
	 */
	function update($parent) 
	{
		echo '<p>The module has been updated to version ' . $parent->manifest->version . '</p>';
	}

	/**
	 * Method to run before an install/update/uninstall method
	 * $parent is the class calling this method
	 * $type is the type of change (install, update or discover_install)
	 *
	 * @return void
	 */
	function preflight($type, $parent) 
	{
		//echo '<p>Anything here happens before the installation/update/uninstallation of the module</p>';
		if (!PluginHelper::getPlugin('content', 'eventlist')) {
			// Get a handle to the Joomla! application object
			$application = Factory::getApplication();

			// Add a message to the message queue
			$application->enqueueMessage(Text::_('ERR_PLUGIN_NOT_FOUND'), 'warning');
		}
	}

	/**
	 * Method to run after an install/update/uninstall method
	 * $parent is the class calling this method
	 * $type is the type of change (install, update or discover_install)
	 *
	 * @return void
	 */
	function postflight($type, $parent) 
	{
		//echo '<p>Anything here happens after the installation/update/uninstallation of the module</p>';
	}
}