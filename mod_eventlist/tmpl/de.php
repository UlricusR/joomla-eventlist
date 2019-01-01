<?php 
// No direct access
defined('_JEXEC') or die;

if(array_filter($eventList)) {
	
	// Load language file, set default language to en-GB
	$language =& JFactory::getLanguage();
	$language->setDefault('de-DE');
	$extension = 'mod_eventlist';
	$base_dir = JPATH_SITE;
	$language->load($extension, $base_dir, $language->getTag(), true);

	// Add CSS
	$doc = JFactory::getDocument();
	$doc->addStyleSheet(JURI::base(true).'/modules/mod_eventlist/css/eventlist.css');

	// Define weekdays
	$weekdays = explode(',', $language->_("MOD_EVENTLIST_WEEKDAYS"));
	
	// Iterate through days
	foreach($eventList as $dayid => $day) {
		if(!empty($day)) {
			// Echo day
			echo '<p class="eventlist_weekday">'.$weekdays[$dayid-1].'</p>';
			
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
}