<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_eventlist
 *
 * @copyright   Copyright (C) 2018 Ulrich Rueth, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
 
defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;
//JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

class ModEventListHelper
{
	/**
	 * Get a list of the articles of a certain day sorted by starting time,
	 * articles w/o starting time first.
	 *
	 * @param   \Joomla\Registry\Registry  &$params  The module options.
	 *
	 * @return  array
	 *
	 * @since   3.8
	 */
	 public static function getList($params)
	 {
	 	// Get the database object
		$db = JFactory::getDbo();	 	
		
		// Get all entries from #__content_eventlist that have data
		$query = $db->getQuery(true)
			->select($db->quoteName(array('article_id', 'data')))
			->from($db->quoteName('#__content_eventlist'))
			->where("`data` IS NOT NULL");
		$db->setQuery($query);
		$articles = $db->loadAssocList();
		
		// Create an array with seven empty arrays, one for each day of the week TODO Configure startday
		$days = array(array(), array(), array(), array(), array(), array(), array());
		
		// Fill the days array with all relevant articles
		foreach($articles as $article) {
			$data = json_decode(json_decode($article['data'], $assoc = true), $assoc = true);
			
			// We need at least weekday and starttime for adding the article to the event list, so if there is none, we'll move on with the next article
			if(!($data['eventlist_weekday'] && $data['eventlist_starttime'])) continue;
			
			// Create event info array
			$eventInfo = array();
			$eventInfo['article_id'] = $article['article_id'];
			$eventInfo['starttime'] = $data['eventlist_starttime'];
			if($data['eventlist_endtime']) $eventInfo['endtime'] = $data['eventlist_endtime'];
			if($data['eventlist_comment']) $eventInfo['comment'] = $data['eventlist_comment'];
			
			// Add to the correct day
			$days[(int)$data['eventlist_weekday'] - 1][] = $eventInfo;
		}
		print_r($days);
		
		// Sort by starttime
		$sortedDays = array();
		foreach($days as $day) {
			$sortedDays[] = ArrayHelper::sortObjects($day, 'starttime');
		}
		print_r($sortedDays); return;
		
		// Get the article content and URL for every day
		
		
		$eventList = array();
		
		// For each day of the week ...		
		for ($day = 1; $day <= 7; $day++)
		{
			// Retrieve the article IDs (item_id) sorted by starting date as an associated array with following SQL query:
			// SELECT `item_id`,`value` FROM `jos_fields_values` WHERE `field_id`=$startzeit AND `item_id` IN
			// (SELECT `item_id` FROM `jos_fields_values` WHERE `field_id`=$wochentag AND `value`=$day)
			// ORDER BY `value`
			$subQuery = $db->getQuery(true)
				->select('item_id')
				->from($db->quoteName('#__fields_values'))
				->where(
					$db->quoteName('field_id') . ' = ' . $db->quote($wochentag) . ' AND ' . 
					$db->quoteName('value') . ' = ' . $db->quote($day)
				);
			$query = $db->getQuery(true)
				->select($db->quoteName(array('item_id', 'value')))
	          ->from($db->quoteName('#__fields_values'))
	          ->where(
	          	$db->quoteName('field_id') . ' = ' . $db->quote($startzeit) . ' AND ' .
	           	$db->quoteName('item_id') . ' IN (' . $subQuery . ')'
	          )
	          ->order('value');
			$db->setQuery($query);
			$result = $db->loadAssocList();
			
			if(array_filter($result)) {
				$articleIds = ArrayHelper::getColumn($result, 'item_id');
						
				// Get an instance of the generic articles model
				$model = JModelLegacy::getInstance('Articles', 'ContentModel', array('ignore_request' => true));
		
				// Set application parameters in model
				$app       = JFactory::getApplication();
				$appParams = $app->getParams();
				$model->setState('params', $appParams);
		
				// Set the filters based on the module params
				$model->setState('filter.published', 1);
				$model->setState('filter.article_id', $articleIds);
		
				// This module does not use tags data
				$model->setState('load_tags', false);
		
				// Access filter
				$access     = !JComponentHelper::getParams('com_content')->get('show_noauth');
				$authorised = JAccess::getAuthorisedViewLevels(JFactory::getUser()->get('id'));
				$model->setState('filter.access', $access);
				
				// Retrieve published content
				$publishedItems = $model->getItems();
				
				// Get non-published items if requested
				if ($params['showdespitenotpublished']) {
					$model->setState('filter.published', 0);
					$publishedItems = array_merge($publishedItems, $model->getItems());
				}
				
				$dayEvents = array_fill(0, count($articleIds), null);
				if(array_filter($publishedItems)) {
			
					// Build return array for the day
				    foreach ($publishedItems as $item)
				    {
				       // Get startingtime
						$query = $db->getQuery(true)
			            ->select($db->quoteName('value'))
			            ->from($db->quoteName('#__fields_values'))
			            ->where(
			            		$db->quoteName('field_id') . ' = ' . $db->quote($startzeit) . ' AND ' .
			            		$db->quoteName('item_id') . ' = ' . $db->quote($item->id)
			            	);
			          $db->setQuery($query);
			          $startingtime = $db->loadResult();
			 			
						// Get endtime
						$query = $db->getQuery(true)
			            ->select($db->quoteName('value'))
			            ->from($db->quoteName('#__fields_values'))
			            ->where(
			            		$db->quoteName('field_id') . ' = ' . $db->quote($endzeit) . ' AND ' .
			            		$db->quoteName('item_id') . ' = ' . $db->quote($item->id)
			            	);
			          $db->setQuery($query);
			          $endtime = $db->loadResult();
			          
			          // Get comment
						$query = $db->getQuery(true)
			            ->select($db->quoteName('value'))
			            ->from($db->quoteName('#__fields_values'))
			            ->where(
			            		$db->quoteName('field_id') . ' = ' . $db->quote($kommentar) . ' AND ' .
			            		$db->quoteName('item_id') . ' = ' . $db->quote($item->id)
			            	);
			          $db->setQuery($query);
			          $comment = $db->loadResult();
			          
			          // Add event to return array
			          $eventData = array();
			          $eventData['startingtime'] = $startingtime;
			          if($endtime) {$eventData['endtime'] = $endtime;} else {$eventData['endtime'] = null;}
			          if($comment) {$eventData['comment'] = $comment;} else {$eventData['comment'] = null;}
			          
						// Include URL only for published articles			          
			          if($item->state == 1) $eventData['url'] = JRoute::_("index.php?option=com_content&view=article&id=$item->id:$item->alias&catid=$item->catid:$item->category_alias");
			          
			          $eventData['title'] = $item->title;
			          
			          // Sort eventData into right position
			          foreach($articleIds as $position => $articleId) {
			          	if($articleId == $item->id) {$dayEvents[$position] = $eventData;}
			          }
					}
				} else {
					$dayEvents[] = null;
				}
		      $eventList[$day] = array_filter($dayEvents);
			} else {
				$eventList[$day] = null;
			}
		} 
		
		// Return the list of events sorted by day and starting time
	   return $eventList;
	 }
}