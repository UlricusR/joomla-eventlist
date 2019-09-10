<?php
/**
 * @copyright Copyright 2018-2019 Ulrich Rueth. All Rights Reserved.
 * @license    GNU General Public License version 3 or later
 */

defined('_JEXEC') or die;
jimport('joomla.plugin.plugin');

// Imports
use Joomla\CMS\Factory;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;

/**
 * This is a custom plugin class to add additional fields to com_content to allow it to be used for capturing recurring events
 */
class plgContentEventlist extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 */
	protected $autoloadLanguage = true;

	/** var string Name of the plugin */
	protected $plg_name;

	/** var array List of fields to look for in the $attribs */
	protected $eventfields;

	/** var string Categories */
	protected $categories;

	/** var boolean Limit plugin to selected categories */
	protected $limit_to_categories;

	/** var boolean Include child categories */
	protected $include_child_categories;

	public function __construct(& $subject, $config)
	{
		// We only want to use this with com_content
		$jinput = Factory::getApplication()->input;
		$option = $jinput->get('option');
		if ($option <> 'com_content')
		{
			return true;
		}

		parent::__construct($subject, $config);

		$this->plg_name = 'eventlist';
		$this->eventfields = $this->setEventFields();

		// Get the plugin parameters
		$this->categories	 = $this->params->get('plg_eventlist_categories');
		$this->limit_to_categories = $this->params->get('plg_eventlist_limittocategories');
		$this->include_child_categories = $this->params->get('plg_eventlist_includechildcategories');
}

	/**
	 * Set the values for the eventlist array
	 * These should correspond to the fields in extras/eventparams.xml
	 * These should not use the same name as any com_content attribs fields
	 *
	 * @return array
	 */
	protected function setEventFields()
	{
		$eventfields = array (
			'eventlist_show',
			'eventlist_contactperson',
			'eventlist_email',
			'eventlist_phone',
			'eventlist_audience',
			'eventlist_location',
			'eventlist_weekday',
			'eventlist_starttime',
			'eventlist_endtime',
			'eventlist_comment',
		);

		return $eventfields;
	}

	/**
	 * Prepare the form to add to the article edit
	 *
	 * @param object $form
	 * @param object $data
	 *
	 * @return bool
	 */
	public function onContentPrepareForm($form, $data)
	{
		// If the category id is set, then check if the plugin should be limited to a specific category
		if (!empty($data->catid))
		{
			//$this->getChildCategories($data->catid);
			if ($this->limit_to_categories && $this->categories && !$this->checkCategory($data->catid))
			{
				return true;
			}
		}

		if (!($form instanceof Form))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}

		// Check that we are manipulating a valid form
		$name = $form->getName();

		if (!in_array($name, array('com_content.article')))
		{
			return true;
		}

		// Add the extra fields to the form
		Form::addFormPath(dirname(__FILE__) . '/extras');
		$form->loadFile('eventparams', false);

		return true;
	}

	/**
	 * Runs on content preparation.
	 * Called after the data for a JForm has been retrieved.
	 *
	 * @param	string	$context	The context for the data
	 * @param	object	$data		An object containing the data for the form.
	 *
	 * @return boolean
	 */
	public function onContentPrepareData($context, $data)
	{
		if (is_object($data))
		{
			$articleId = isset($data->id) ? $data->id : 0;

			if ($articleId > 0)
			{
				try {
					// Load the data from the database
					$db = Factory::getDbo();
					$query = $db->getQuery(true);
					$query->select('data');
					$query->from('#__content_eventlist');
					$query->where('article_id = '.$db->Quote($articleId));
					$db->setQuery($query);
					$results = $db->loadAssoc();

					$eventdata = ($results && count($results) == 1) ? json_decode(json_decode($results['data'])) : new stdClass();

					// Merge the data
					$data->attribs = array();

					foreach ($this->eventfields as $eventfield)
					{
						$data->attribs[$eventfield] = isset($eventdata->$eventfield) ? $eventdata->$eventfield : '';
					}
				} catch (Exception $e) {
					$this->_subject->setError($e->getMessage());
					return false;
				}
			}
			else
			{
				// Load the form
				Form::addFormPath(dirname(__FILE__).'/extras');
				$form = new Form('com_content.article');
				$form->loadFile('eventparams', false);

				// Merge the default values
				$data->attribs = array();
				foreach ($form->getFieldset('attribs') as $field)
				{
					$data->attribs[] = array($field->fieldname, $field->value);
				}
			}
		}

		return true;
	}

	/**
	 * Fires after content save post event hook to save custom data into #__content_eventlist
	 *
	 * @param $context
	 * @param $data
	 * @param $isNew
	 *
	 * @return bool
	 */
	public function onContentAfterSave($context, $data, $isNew)
	{
		// Check if we are manipulating a valid form
		if (!in_array($context, array('com_content.article')))
		{
			return true;
		}

		// Get the article id or set to 0 if new article (it should have an id at this point)
		$articleId = isset($data->id) ? $data->id : 0;

		// Get the attributes
		$attribs = json_decode($data->attribs);

		// Pull out the extra fields to insert into the table
		$eventattribs = array();
		foreach ($this->eventfields as $eventfield)
		{
			$eventattribs[$eventfield] = isset($attribs->$eventfield) ? $attribs->$eventfield : '';
		}
		$eventattribs = json_encode($eventattribs);

		// Get the database object
		$db = Factory::getDbo();

		// Check for an existing entry
		$db->setQuery('SELECT COUNT(*) FROM #__content_eventlist WHERE article_id = '.$articleId);
		$res = $db->loadResult();

		// Updating or adding
		if (!empty($res)) // updating record
		{
			$this->updateRecord($eventattribs, $articleId);
		}
		else // Adding a new record
		{
			$this->insertRecord($eventattribs, $articleId);
		}
	}

	/**
	* Remove the data when the article is deleted
	*
	* Method is called before (after?) article data is deleted from the database
	*
	* @param string The context for the content passed to the plugin.
	* @param object The data relating to the content that was deleted.
	*
	* @return bool
	* @throws Exception
	*/
	public function onContentAfterDelete($context, $data)
	{
		 // get the article id
		$articleId = isset($data->id) ? (int) $data->id : 0;

		if ($articleId)
		{
			try
			{
				$db = Factory::getDbo();

				$db->setQuery('DELETE FROM #__content_eventlist WHERE article_id = '.$articleId );

				if (!$db->execute()) {
					throw new Exception($db->getErrorMsg());
				}
			}
			catch (Exception $e)
			{
				$this->_subject->setError($e->getMessage());

				return false;
			}
		}

		return true;
	}

	/**
	 * The first stage in preparing the content for output
	 *
	 * @param $context
	 * @param $article
	 * @param $params
	 * @param $page
	 *
	 * @return string
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		// Get article attribs
		$attribs = json_decode($article->attribs);

		// Extract the eventdata attribs
		$eventdata = array();
		foreach ($this->eventfields as $eventfield)
		{
			if (!empty(trim($attribs->$eventfield))) $eventdata[$eventfield] = $attribs->$eventfield;
		}

		// Check if there are more than eventdata_show
		if (!count($eventdata))
		{
			return;
		}
		if (count($eventdata) == 1 && array_key_exists('eventlist_show', $eventdata)) {
			return;
		}

		// Add CSS for table
		$doc = Factory::getDocument();
		$doc->addStyleSheet(Uri::base(true).'/plugins/content/eventlist/extras/eventinfo.css');

		// Load form & fieldset
		$file = __DIR__ . '/extras/eventparams.xml';
		$form = new Form('eventparams');
		$form->loadFile($file);
		$fieldSet = $form->getFieldset('eventinfo');
		//$form = JForm::getInstance('eventparams', dirname(__FILE__) . '/extras/eventparams.xml');
		//$fieldSet = $form->getFieldSet('eventinfo');

		// Get xml and check if it's valid
		$xml = $form->getXml();
		if (!($xml instanceof \SimpleXMLElement)) return;

		// Get weekday options
		#$group = 'attribs';
		$fieldName = 'eventlist_weekday';
		#$attribsGroupAsXML = $xml->xpath('//fields[@name="' . $group . '" and not(ancestor::field/form/*)]');
		$fieldAsXMLArray = $xml->xpath('//field[@name="' . $fieldName . '" and not(ancestor::field/form/*)]');
		$options = $fieldAsXMLArray[0]->xpath('option');
		$weekdays = [];
		foreach ($options as $option) {
		    $value = (string) $option['value'];
		    $text  = trim((string) $option) != '' ? trim((string) $option) : $value;
		    $weekdays[$value] = $text;
		}

		// Get plg_contemt_eventlist parameters
		$plugin = PluginHelper::getPlugin('content', 'eventlist');
		$pluginparams = new Registry($plugin->params);

		// Construct and populate a result table on the fly
		$table = '<table class="eventlist_infobox">';
		if ($pluginparams['plg_eventlist_displaystyle'] == 'title')
			$table .= '<thead><tr><th colspan="2">'.
				$pluginparams['plg_eventlist_displaytitle'].
				'</th></tr></thead>';
		$table .= '<tbody>';
		$rownr = 0;
		foreach ($eventdata as $attr => $value) {
			// Don't display the eventlist_show field
			if ($attr == 'eventlist_show') continue;

			// Initiate new table row
			$table .= '<tr class="row'.($rownr % 2).'">';

			// Get the field's local label and the local weekday string
			$label = "";
			foreach ($fieldSet as $field) {
				$fieldName = $field->getAttribute('name');
				if ($fieldName == $attr) {
					$label = Text::_($field->getAttribute('label'));
					if ($fieldName == 'eventlist_weekday') {
						$value = Text::_($weekdays[(int)$value]);
					}
					break;
				}
			}

			// Populate table row
			$table .= '<td class="eventinfo_label">'.$label.'</td>';
			$table .= '<td class="eventinfo_value">'.$value.'</td>';

			// Close table row and increase row number
			$table .= '</tr>';
			$rownr++;
		}
		$table .= '</tbody></table>';

		// Wrap table in a classed <div>
		$suffix = $this->params->get('eventparamsclass_sfx', 'eventparams');
		$html = '<div class="'.$suffix.'">'.$table.'</div>';

		$article->text .= $html;
	}

	 /**
	* Insert a new record into the database
	*
	* @param attribs our extra fields in object form
	* @param articleId the article id we are relating the fields to
	*
	* @return bool
	* @throws Exception
	*/
	public function insertRecord($attribs, $articleId)
	{
		// Get a db connection.
		$db = Factory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Insert columns.
		$columns = array('article_id', 'data', 'created', 'created_by');

		$user = Factory::getUser();
		$created_by = $user->id;
		$created = Factory::getDate()->toSql();

		// Insert values.
		$values = array(
			$articleId,
			$db->quote(json_encode($attribs)),
			$db->quote($created),
			$created_by,
		);

		// insert query
		$query
			->insert($db->quoteName('#__content_eventlist'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));

		// set the query
		$db->setQuery($query);

		// execute, throw an exception if we have a problem
		if (!$db->execute()) {
			throw new Exception($db->getErrorMsg());
		}

		return true;
	}


	/**
	* Update record function
	*
	* @param attribs requires object of attributes from form
	* @param articleId id of the article we are relating to
	*
	* @return bool
	* @throws Exception
	*/
	protected function updateRecord($attribs, $articleId)
	{
		$db = Factory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		$conditions = array(
			'article_id='.$articleId,
		);

		$user = Factory::getUser();
		$modified_by = $user->id;
		$modified = Factory::getDate()->toSql();

		// Fields to update.
		$fields = array(
			'data='.$db->quote(json_encode($attribs)),
			'modified='.$db->quote($modified),
			'modified_by='.$modified_by,
		);

		// update query
		$query->update($db->quoteName('#__content_eventlist'))->set($fields)->where($conditions);

		// set the query
		$db->setQuery($query);

		// execute, throw an exception if we have a problem
		if (!$db->execute()) {
			throw new Exception($db->getErrorMsg());
		}

		return true;
	}

	/**
	 * Check the article category against the category selected by the plugin
	 *
	 * @param integer	$article_cat		The category of the article
	 *
	 * @return boolean
	 */
	protected function checkCategory($article_cat)
	{
		if (in_array($article_cat, $this->categories))
		{
			return true;
		}

		// Need to check if the current category's parent or grand-parent
		// is the category selected for this plugin
		if ($this->include_child_categories)
		{
			$parents = $this->getParentCategories($article_cat);
			foreach ($this->categories as $currentcategory) {
				if (in_array($currentcategory, $parents)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get a list of the category children
	 *
	 * @param integer	$catId	The id of the category to check
	 *
	 * @return array
	 */
	protected function getChildCategories($catId)
	{
		jimport('joomla.application.categories');
		$allcategories = Categories::getInstance('Content');
		$cat		= $allcategories->get($catId);
		$children	= $cat->getChildren();
		$childCats	= array();

		foreach ($children as $child)
		{
			$childCats[] = $child->id;
		}

		return $childCats;
	}

	/**
	 * Get a list of the category parent(s)
	 *
	 * @param integer	$catId	The id of the category to check
	 *
	 * @return array
	 */
	protected function getParentCategories($catId)
	{
		$parentCats	= array();

		jimport('joomla.application.categories');
		$allcategories = Categories::getInstance('Content');
		$cat		= $allcategories->get($catId);

		// Check the parent_id. If it is an integer > 0, update the array and
		// check for a parent_id of the parent... Only going up 2 levels...
		if ((int)$cat->parent_id)
		{
			$parentCats[]	= $cat->parent_id;
			$parent 		= $cat->getParent();
			if ((int) $parent->parent_id)
			{
				$parentCats[] = $parent->parent_id;
			}
		}

		return $parentCats;
	}

}
