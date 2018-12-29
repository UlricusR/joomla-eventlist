<?php
/**
 * @copyright Copyright 2018 Ulrich Rueth. All Rights Reserved.
 * @license    GNU General Public License version 3 or later;
 */

defined('_JEXEC') or die;

/**
 * This is a rule to verify the time format of the input form
 */
class JFormRuleTime extends JFormRule
{
	public function test(SimpleXMLElement $element, $value, $group = null, JRegistry $input = null, JForm $form = null) {
		
		// If no value then no validation
		if ($value == '') return true;
		
		// Get plg_contemt_eventlist parameters
		$plugin = JPluginHelper::getPlugin('content', 'eventlist');
		$params = new JRegistry($plugin->params);
		$format = $params['plg_eventlist_timeformat'];
		if ($format == 'free') $format = $params['plg_eventlist_timeformat_free'];
	
    	// Check if format is valid
    	$dt = DateTime::createFromFormat($format, $value);
    	if ($dt instanceof DateTime) {
    		return true;
    	}
    	
    	// Return error message
    	/**
    	$elementName = "unknown element";
    	echo $element->attributes()->name;
    	switch ($element->attributes()->name) {
    		case 'eventlist_starttime':
    			$elementName = JText::_('PLG_CONTENT_EVENTLIST_PARAM_STARTTIME');
    			break;
    		case 'eventlist_endtime':
    			$elementName = JText::_('PLG_CONTENT_EVENTLIST_PARAM_ENDTIME');
    			break;
    	}*/
    	$element->addAttribute('message', JText::_('PLG_EVENTLIST_ERR_TIMEFORMAT').JText::_($element->attributes()->label).'/'.$format.'/'.$value);
    	return false;
    }
}