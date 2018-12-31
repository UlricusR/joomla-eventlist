<?php 
// No direct access
defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

//print_r($eventList);

if(array_filter($eventList)) {
	
	// Load language file, set default language to en-GB
	$language =& JFactory::getLanguage();
	$language->setDefault('en-GB');
	$extension = 'mod_eventlist';
	$base_dir = JPATH_SITE;
	$language->load($extension, $base_dir, $language->getTag(), true);

	// Define weekdays
	$weekdays = explode(',', $language->_("MOD_EVENTLIST_WEEKDAYS"));
	
	// Iterate through days
	foreach($eventList as $dayid => $day) {
		if(!empty($day)) {
			// Echo day
			echo '<p><strong>'.$weekdays[$dayid-1].'</strong></p>';
			
			// Echo events of the day
			echo "<ul>";
			foreach($day as $event) {
				echo "<li>";
				if($event['startingtime']) echo $event['startingtime'];
				if($event['endtime']) echo "-".$event['endtime'];
				if($event['startingtime']) echo " Uhr";
				if($event['comment']) {echo " (".$event['comment'].")";}
				if($event['startingtime']) echo ": ";
				if ($event['url']) echo "<a href=".$event['url'].">".$event['title']."</a>";
				else echo $event['title'];
				echo "</li>";
			}
			echo "</ul>";
		}
	}
}