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
		
		//		
		// Step 1: Get all relevant article IDs sorted by (1) day and (2) starttime
		//
		
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
		
		// Sort by starttime
		$sortedDays = array();
		foreach($days as $day) {
			$sortedDays[] = ArrayHelper::sortObjects($day, 'starttime');
		}
		
		//
		// Step 2: Get the required content form the articles and build the return array
		//
		
		$eventList = array();
		$dayCounter = 0;
		
		// For each day of the week ...		
		foreach($sortedDays as &$sortedDay) {
			$dayCounter++;
			if(!empty($sortedDay)) {
				$articleIds = array_column($sortedDay, 'article_id');
						
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
				$itemsToPublish = $model->getItems();
				
				// Get non-published items if requested
				if ($params['showdespitenotpublished']) {
					$model->setState('filter.published', 0);
					$itemsToPublish = array_merge($itemsToPublish, $model->getItems());
				}
				
				$dayEvents = array_fill(0, count($itemsToPublish), null);
			
				// Build return array for the day
			    foreach ($itemsToPublish as $item)
			    {
		          // Add event to return array
		          $eventData = array();
		          $key = 0;
		          foreach($sortedDay as $event) {
		          	if($event['article_id'] == $item->id) break;
		          	$key++;
		          }
		          $eventData['startingtime'] = $sortedDay[$key]['starttime'];
		          if($sortedDay[$key]['endtime']) {$eventData['endtime'] = $sortedDay[$key]['endtime'];} else {$eventData['endtime'] = null;}
		          if($sortedDay[$key]['comment']) {$eventData['comment'] = $sortedDay[$key]['comment'];} else {$eventData['comment'] = null;}
		          
					// Include URL only for published articles			          
		          if($item->state == 1) $eventData['url'] = JRoute::_("index.php?option=com_content&view=article&id=$item->id:$item->alias&catid=$item->catid:$item->category_alias");
		          
		          $eventData['title'] = $item->title;
		          
		          // Sort eventData into right position
		          foreach($articleIds as $position => $articleId) {
		          	if($articleId == $item->id) {$dayEvents[$position] = $eventData;}
		          }
				}
		      $eventList[$dayCounter] = array_filter($dayEvents);
			} else {
				$eventList[$dayCounter] = null;
			}
		} 
		
		// Return the list of events sorted by day and starting time
	   return $eventList;
	 }
}