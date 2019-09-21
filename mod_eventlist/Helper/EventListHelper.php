<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_eventlist
 *
 * @copyright   Copyright (C) 2018-2019 Ulrich Rueth. All rights reserved.
 * @license     GNU General Public License version 3 or later
 */
namespace RuethInfo\Module\EventList\Site\Helper;

defined('_JEXEC') or die;

// Imports
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Model\ArticlesModel;
use Joomla\Registry\Registry;

class EventListHelper
{
	/**
	 * Get a list of the articles of a certain day sorted by starting time,
	 * articles w/o starting time first.
	 *
	 * @param   Registry       $params  The module parameters.
	 * @param   ArticlesModel  $model   The model.
	 *
	 * @return  array
	 *
	 * @since   3.8
	 */
    public static function getList(Registry $params, ArticlesModel $model)
	 {
	 	// Get the database object
		$db = Factory::getDbo();

		//
		// Step 1: Get all relevant article IDs sorted by (1) day and (2) starttime
		//

		// Get all entries from #__content_eventlist that have data
		$query = $db->getQuery(true)
			->select($db->quoteName(array('article_id', 'data')))
			->from($db->quoteName('#__content_eventlist'))
			->where($db->quoteName('data').' IS NOT NULL');
		$db->setQuery($query);
		$articles = $db->loadAssocList();

		// Create an array with seven empty arrays, one for each day of the week
		$days = array_fill(0, 7, array());

		// Fill the days array with all relevant articles
		foreach($articles as $article) {
			$data = json_decode(json_decode($article['data'], $assoc = true), $assoc = true);

			// We need the "show article" parameter and the weekday set
			// so if there is none, we'll move on with the next article
			if(!((bool)$data['eventlist_show'] && $data['eventlist_weekday'])) continue;

			// Create event info array
			$eventInfo = array();
			$eventInfo['article_id'] = $article['article_id'];
			$eventInfo['starttime'] = $data['eventlist_starttime'];
			if($data['eventlist_endtime']) $eventInfo['endtime'] = $data['eventlist_endtime'];
			if($data['eventlist_comment']) $eventInfo['comment'] = $data['eventlist_comment'];

			$days[(int)$data['eventlist_weekday'] - 1][] = $eventInfo;
		}

		// Sort by starttime
		$sortedDays = array();
		foreach($days as $day) {
			$sorting = array();
			foreach($day as $event) {
				$sorting[$event['article_id']] = $event['starttime'];
			}
			uasort($sorting, function($a, $b)
				{
					$ad = new \DateTime($a);
					$bd = new \DateTime($b);
					if ($ad == $bd) {
      					return 0;
   					}
   					return ($ad < $bd) ? -1 : 1;
				}
			);

			$sortedDay = array();
			foreach($sorting as $articleId => $starttime) {
				foreach($day as $event) {
					if($event['article_id'] == $articleId) {
						$sortedDay[] = $event;
						break;
					}
				}
			}

			$sortedDays[] = $sortedDay;
		}

		//
		// Step 2: Get the required content form the articles and build the return array
		//
		
		// Set application parameters in model
		$app       = Factory::getApplication();
		$appParams = $app->getParams();
		$model->setState('params', $appParams);
		
		// Set filters that don't change for each day
		$model->setState('filter.published', 1);
		$model->setState('filter.category_id', $params->get('eventlist_categories', array()));
		
		// This module does not use tags data
		$model->setState('load_tags', false);
		
		// Access filter
		$access     = !ComponentHelper::getParams('com_content')->get('show_noauth');
		$authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
		$model->setState('filter.access', $access);
		
		$eventList = array();
		$dayCounter = 0;

		// For each day of the week ...
		foreach($sortedDays as $sortedDay) {
			$dayCounter++;
			if(!empty($sortedDay)) {
			    $articleIds = array_column($sortedDay, 'article_id');

				// Set the article id filter
				$model->setState('filter.article_id', $articleIds);

				// Retrieve published content
				$itemsToPublish = $model->getItems();

				// Get non-published items if requested
				if ($params->get('eventlist_showdespitenotpublished', false)) {
					$model->setState('filter.published', 0);
					$itemsToPublish = array_merge($itemsToPublish, $model->getItems());
				}

				$dayEvents = array();

				// Build return array for the day
				foreach($sortedDay as $event) {
					$eventData = array();
					foreach($itemsToPublish as $item) {
						if($event['article_id'] == $item->id) {
							// Only if an article is found in the itemsToPublish, we fill in the eventData
							$eventData['startingtime'] = $event['starttime'];
				          if($event['endtime']) {$eventData['endtime'] = $event['endtime'];} else {$eventData['endtime'] = null;}
				          if($event['comment']) {$eventData['comment'] = $event['comment'];} else {$eventData['comment'] = null;}
				          $eventData['title'] = $item->title;

							// Include URL only for published articles
				          if($item->state == 1) $eventData['url'] = Route::_("index.php?option=com_content&view=article&id=$item->id:$item->alias&catid=$item->catid:$item->category_alias");
							break;
						}
					}
					$dayEvents[] = $eventData;
				}

				// By using the array_filter method, we make sure that there's no empty element in the event list
				// (e.g. when an article has a weekday assigned but should not be publised)
		      $eventList[$dayCounter] = array_filter($dayEvents);
			} else {
				$eventList[$dayCounter] = null;
			}
		}

		// Return the list of events sorted by day and starting time
	   return $eventList;
	 }
}
