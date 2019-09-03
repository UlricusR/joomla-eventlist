<?php 
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * @package     Joomla.Site
 * @subpackage  mod_eventlist
 *
 * @copyright   Copyright (C) 2018-2019 Ulrich Rueth. All rights reserved.
 * @license     GNU General Public License version 3 or later
 */
 
// No direct access
defined('_JEXEC') or die;

if($eventList <> null && array_filter($eventList)) {
	
	// Load language file, set default language to en-GB
	$language =& Factory::getLanguage();
	$language->setDefault('en-GB');
	$extension = 'mod_eventlist';
	$base_dir = JPATH_SITE;
	$language->load($extension, $base_dir, $language->getTag(), true);

	// Add CSS
	$doc = Factory::getDocument();
	$doc->addStyleSheet(Uri::base(true).'/modules/mod_eventlist/css/eventlist.css');

	// Define weekdays; 0 = Sunday to 6 = Saturday
	$weekdays = explode(',', $language->_("MOD_EVENTLIST_WEEKDAYS"));
	
	// Iterate through $eventList, where 1 = Sunday and 7 = Saturday,
	$dayoffset = (int)$params['eventlist_weekstarts'];
	$dayid = 0 + $dayoffset;
	while($dayid < 7 + $dayoffset) {
		$day = $eventList[$dayid + 1];
		createEventlist($weekdays[$dayid], $day, $params);
		$dayid++;
	}
	
	// Append Sunday as last day
	if ($dayoffset == 1) createEventlist($weekdays[0], $eventList[1], $params);
}

function createEventlist($weekday, $day, $params) {
	if(!empty($day)) {
		// Echo day
		echo '<p class="eventlist_weekday">'.$weekday.'</p>';
		// Echo events of the day
		echo '<ul class="eventlist">';
		foreach($day as $event) {
			echo '<li>';
			if($event['startingtime']) echo $event['startingtime'];
			if($event['endtime']) echo '-'.$event['endtime'];
			if($event['startingtime']) echo ' Uhr';
			if($event['comment']) echo ' ('.$event['comment'].')';
			if($event['startingtime']) echo ': ';
			if ($event['url']) echo '<a href="'.$event['url'].'">'.$event['title'].'</a>';
			else echo $event['title'];
			echo '</li>';
		}
		echo '</ul>';
	}
}
