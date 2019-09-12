<?php
/**
 * @copyright Copyright 2018-2019 Ulrich Rueth. All Rights Reserved.
 * @license    GNU General Public License version 3 or later
 */

defined('_JEXEC') or die;

// Imports
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;

/**
 * This is a rule to verify the time format of the input form
 */
class JFormRuleTime extends FormRule
{
	public function test(SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null) {
		
		// If no value then no validation
		if ($value == '') return true;
		
		// Get plg_contemt_eventlist parameters
		$plugin = PluginHelper::getPlugin('content', 'eventlist');
		$params = new Registry($plugin->params);
		$format = $params['plg_eventlist_timeformat'];
		if ($format == 'free') $format = $params['plg_eventlist_timeformat_free'];
	
    	// Check if format is valid
    	$dt = DateTime::createFromFormat($format, $value);
    	if ($dt instanceof DateTime) {
    		return true;
    	}
    	
    	// Return error message
    	$element->addAttribute('message', Text::_('PLG_CONTENT_EVENTLIST_ERR_TIMEFORMAT').Text::_($element->attributes()->label).'/'.$format.'/'.$value);
    	return false;
    }
}