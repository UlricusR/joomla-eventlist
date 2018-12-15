<?php 
// No direct access
defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

//print_r($eventList);

if(array_filter($eventList)) {
	// Iterate through days
	foreach($eventList as $dayid => $day) {
		if(!empty($day)) {
			// Echo day
			switch($dayid) {
				case 1:
					echo "<p><strong>Sonntag</strong></p>";
					break;
				case 2:
					echo "<p><strong>Montag</strong></p>";
					break;
				case 3:
					echo "<p><strong>Dienstag</strong></p>";
					break;
				case 4:
					echo "<p><strong>Mittwoch</strong></p>";
					break;
				case 5:
					echo "<p><strong>Donnerstag</strong></p>";
					break;
				case 6:
					echo "<p><strong>Freitag</strong></p>";
					break;
				case 7:
					echo "<p><strong>Samstag</strong></p>";
					break;
			}
			
			// Echo events of the day
			echo "<ul>";
			foreach($day as $event) {
				echo "<li>";
				echo $event['startingtime'];
				if($event['endtime']) {echo "-".$event['endtime'];}
				echo " Uhr";
				if($event['comment']) {echo " (".$event['comment'].")";}
				echo ": ";
				echo "<a href=".$event['url'].">".$event['title']."</a>";
				echo "</li>";
			}
			echo "</ul>";
		}
	}
}