<?php
/**
 *  This file is part of Wigii.
 *  Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
 *  
 *  Wigii is free software: you can redistribute it and/or modify it 
 *  under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, 
 *  or (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *  See the GNU General Public License for more details.
 *
 *  A copy of the GNU General Public License is available in the Readme folder of the source code.  
 *  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2016  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/**
 * Wigii List Context
 * Created by LWR on 2 nov. 2009
 * Enhanced by CWE on 09.10.2015 to constraint search space with a list of included/excluded groups.
 * Modified by CWE on 07.02.2018 to handle Wigii NCD module views
 * Modified by Medair(CWE) 12.03.2018 to handle listFilterExp config parameter to always predefine as filtering LogExp on the module view.
 */
class ListContext extends ListFilter {

	// Advanced search fields
	
	const GroupByField = "__groupBySearch";
	const SortByField = "__sortBySearch";
	const TextSearchField = "__textSearch";
	//next three names are also used in the workZone.js, if change then update the js file too
	const DefaultTextSearchField = "__defaultTextSearch";
	const AdvancedSearchField = "__advancedSearch";
	const TextAdvancedSearchField = "__textAdvancedSearch";
	const DefaultTextAdvancedSearchField = "__defaultTextAdvancedSearch";
	const GroupSearchField = "__groupSearch";
	const TextGroupSearchField = "__textGroupSearch";
	const DefaultTextGroupSearchField = "__defaultTextGroupSearch";
	const ReverseSelectSearchField = "__reverseSelectSearch";
	const LimitFilterInGroupSearchField = "limitFilterInGroup";
	const ExcludeGroupsSearchField = "excludeGroups";
	
	// Module views management
	
	const listView = "list";
	const listViewTemplate = "elementList.tpl.php";
	const calendarView = "calendar";
	const calendarViewTemplate = "elementCalendar.tpl.php";
	const blogView = "blog";
	const blogViewTemplate = "elementBlog.tpl.php";
	const wncdView = "wncd";
	const wncdViewTemplate = "elementWncd.tpl.php";
	
	public function setCrtViewToList(){
		$this->setCrtView(ListContext::listView);
	}
	public function isCrtViewList(){
		$this->getCrtView() == ListContext::listView;
	}
	public function setCrtViewToCalendar(){
		$this->setCrtView(ListContext::calendarView);
	}
	public function isCrtViewCalendar(){
		return $this->getCrtView() == ListContext::calendarView;
	}
	public function setCrtViewToBlog(){
		$this->setCrtView(ListContext::blogView);
	}
	public function isCrtViewBlog(){
		return $this->getCrtView() == ListContext::blogView;
	}
	public function isCrtViewWncd() {
		return ($this->getCrtView() == ListContext::wncdView) || 
		($this->getCrtTemplate() == ListContext::wncdViewTemplate);
	}
	private $viewsTemplate;
	public function getViewsTemplate(){
		if(!isset($this->viewsTemplate)) {
			$this->viewsTemplate = array(
				/* standard system views */
				ListContext::listView=>ListContext::listViewTemplate,
				ListContext::calendarView=>ListContext::calendarViewTemplate,
				ListContext::blogView=>ListContext::blogViewTemplate,
				/* customized views */
				ListContext::wncdView=>ListContext::wncdViewTemplate
			);
		}
		return $this->viewsTemplate;
	}
	private $crtView;
	public function getCrtTemplate(){
		$temp = $this->getViewsTemplate();
		return $temp[$this->crtView];
	}
	public function getCrtView(){
		return $this->crtView;
	}
	public function setCrtView($view){
		$this->crtView = $view;
	}	
	public function getCrtViewActivityName(){
		return $this->getCrtView()."View";
	}
	private $viewsParam;
	public function getCrtViewParams($param=null){
		if(!isset($this->viewsParam)) return null;
		if($this->viewsParam[$this->getCrtView()]==null) return null;
		if($param==null) return $this->viewsParam[$this->getCrtView()];
		return $this->viewsParam[$this->getCrtView()][$param];
	}
	public function setCrtViewParams($value, $param){
		if(!isset($this->viewsParam)) $this->viewParam = array();
		if(!isset($this->viewsParam[$this->getCrtView()])) $this->viewsParam[$this->getCrtView()] = array();
		$this->viewsParam[$this->getCrtView()][$param] = $value;
	}
	
	/**
	 * Gets customer UI pref
	 */
	public function getListViewUIPref($fieldName, $key) {
		if(!isset($this->viewsParam)) return null;
		if($this->viewsParam[$this->getCrtView()]==null) return null;
		if($this->viewsParam[$this->getCrtView()][(string) $fieldName]==null) return null;
		return $this->viewsParam[$this->getCrtView()][(string) $fieldName][$key];
	}
	
	/**
	 * Saves customer UI pref like for example the new width of a colunm
	 */
	public function setListViewUIPref($fieldName, $key, $value) {
		if(!isset($this->viewsParam)) $this->viewParam = array();
		if(!isset($this->viewsParam[$this->getCrtView()])) $this->viewsParam[$this->getCrtView()] = array();
		if(!isset($this->viewsParam[$this->getCrtView()][$fieldName])) $this->viewsParam[$this->getCrtView()][$fieldName] = array();
		$this->viewsParam[$this->getCrtView()][$fieldName][$key] = $value;
	}

	/**
	 *@return Array returns the list of possible views defined in the config 
	 */
	public function getAvailableTemplates($p, $module, $configS){
		$fa = $configS->m($p, $module);
		$returnValue = $this->getViewsTemplate();
		foreach($this->getViewsTemplate() as $view=>$template){
			if($view == ListContext::wncdView) {
				unset($returnValue[$view]);
				// Looks for all activities with isWncdView=1
				$wncdActivities = $fa->xpath('/'.$fa->getName().'/*[contains(name(),"View") and @isWncdView="1"]');
				if($wncdActivities) {
					foreach($wncdActivities as $wncdActivity) {
						$returnValue[str_replace('View', '', $wncdActivity->getName())] = ListContext::wncdViewTemplate;
					}
				}
			}
			elseif(empty($fa->{$view."View"})) unset($returnValue[$view]);
		}
		// stores available views and templates
		$this->viewsTemplate = $returnValue;
		// and returns the array 
		return $returnValue;
	}

	/**
	 *@return String returns either the view with isDefaultView = 1 or the first available view 
	 */
	public function getDefaultView($p, $module, $configS){
		$fa = $configS->m($p, $module);
		$views = $this->getAvailableTemplates($p, $module, $configS);
		$defaultView = null;
		foreach($views as $view=>$template){
			if($fa->{$view."View"}["isDefaultView"]=="1") $defaultView = $view;
		}
		if(!$defaultView){
			reset($views);
			$defaultView = key($views);
		}
		return $defaultView;
	}

	// Object lifecycle
	
	private $groupPList;
	private $includeChildrenGroups;
	private $crtSelectedItem;
	private $multipleSelectedItems;
	private $multipleEnableElementState = 0;
	private $multipleEnableElementStateArr;
	private $multipleElementStateRest = 2047;
	private $multipleElementStateMedi = 2047;
	private $multipleElementStateMediPerm = 0;
	private $multipleElementStatePerm = 0;
	private $multipleElementStateArr;
	private $allHaveWriteRights;
	private $sortedBy;
	private $ascendingSort;
	private $groupBy;
	private $defaultGroupByKey;
	private $defaultGroupByAscending;
	private $searchBar;

	public static function createInstance($p=MANDATORY_ARG, $module=MANDATORY_ARG, $configS=MANDATORY_ARG)
	{
		$returnValue = new self();
		$returnValue->setCrtView($returnValue->getDefaultView($p, $module, $configS));
		return $returnValue;
	}

	// ListFilter properties
	
	private $fieldSelectorList;
	public function getFieldSelectorList()
	{
		return $this->fieldSelectorList;
	}
	/**
	 * @param FieldSelectorListArrayWebImpl $fieldSelectorList
	 */
	public function setFieldSelectorList($fieldSelectorList)
	{
		if(isset($fieldSelectorList) && !is_a($fieldSelectorList, "FieldSelectorListArrayWebImpl")) throw new ServiceException("fieldSelectorList must be an FieldSelectorListArrayWebImpl in ListContext", ServiceException::FORBIDDEN);
		$this->fieldSelectorList = $fieldSelectorList;
	}

	public function setGroupPList($groupPListWithoutDetail, $includeChildrenGroups = true){
		$this->groupPList = $groupPListWithoutDetail;
		$this->includeChildrenGroups = $includeChildrenGroups;
		//this will refresh the list. so we need to reset the groupBy value
		$this->setGroupByItemCurrentValue(null);

		$this->addLogExpOnGroupFilterSearch();

		$this->setConfigGroupList($groupPListWithoutDetail);
	}

	public function getGroupPList(){
		return $this->groupPList;
	}
	public function doesGroupListIncludeChildren(){
		return $this->includeChildrenGroups;
	}

	protected function addFieldSelectorLogExp($logExp){
		$crtLogExp = $this->getFieldSelectorLogExp();
		if(isset($crtLogExp)){
			if($crtLogExp instanceof LogExpAnd)
			{
				$crtLogExp->addOperand($logExp);
			}
			else
			{
				$andExp = LogExp::createAndExp();
				$andExp->addOperand($crtLogExp);
				$andExp->addOperand($logExp);
				$this->setFieldSelectorLogExp($andExp);
			}
		} else {
			$this->setFieldSelectorLogExp($logExp);
		}
		//eput($this->getFieldSelectorLogExp()->displayDebug());
	}
	public function addGroupLogExp($logExp){
		$crtLogExp = $this->getGroupLogExp();
		if(isset($crtLogExp)){
			if($crtLogExp instanceof LogExpAnd)
			{
				$crtLogExp->addOperand($logExp);
			}
			else
			{
				$andExp = LogExp::createAndExp();
				$andExp->addOperand($crtLogExp);
				$andExp->addOperand($logExp);
				$this->setGroupLogExp($andExp);
			}
		} else {
			$this->setGroupLogExp($logExp);
		}
		//eput($this->getFieldSelectorLogExp()->displayDebug());
	}

	/**********************************************
	 * CrtSelectedItem / MultipleSelection part
	 * ********************************************
	 *
	 */
	public function setCrtSelectedItem($id){
		$this->crtSelectedItem = $id;
	}
	public function getCrtSelectedItem(){
		return $this->crtSelectedItem;
	}
	//this is called each time we switch off the multipleSelectionMode
	public function resetMultipleSelection(){
		$this->multipleSelectedItems = null;
		$this->multipleEnableElementState = 0;
		$this->multipleEnableElementStateArr = null;
		$this->multipleElementStateRest = 2047;
		$this->multipleElementStateMedi = 2047;
		$this->multipleElementStateMediPerm = 0;
		$this->multipleElementStatePerm = 0;
		$this->multipleElementStateArr = null;
	}

	/**
	 * Copies a multiple selection from another list context.
	 * Current multiple selection is discarded
	 * @param ListContext $listContext
	 */
	public function copyMultipleSelectionFromListContext($listContext) {
		if($listContext->isMultipleSelection()) {
			$this->resetMultipleSelection();
			$this->multipleSelectedItems = $listContext->getMultipleSelection(true);
			$this->multipleEnableElementStateArr = $listContext->getMultipleEnableElementStates();
			$this->multipleElementStateArr = $listContext->getMultipleElementStates();
			$this->multipleEnableElementState = $listContext->getMultipleEnableElementStateAsInt();
			$this->multipleElementStatePerm = $listContext->getMultipleElementStateAsInt(1);
			$this->multipleElementStateMediPerm = $listContext->getMultipleElementStateAsInt(2);
			$this->multipleElementStateMedi = $listContext->getMultipleElementStateAsInt(3);
			$this->multipleElementStateRest = $listContext->getMultipleElementStateAsInt(4);
		}
	}

	/**
	 * @param int $elementEnableState the enable element status encoded as an integer, see ElementP->getEnableElementStateAsInt for info.
	 * @param int $elementState the element statuses values encoded as an integer, see Element->getStateAsInt for info.
	 */
	public function addItemToMultipleSelection($id, $hasWriteRigths, $elementEnableState, $elementState){
		$this->crtSelectedItem = null;
		if(!isset($this->multipleSelectedItems)){
			$this->multipleSelectedItems = array();
		}
		$this->multipleSelectedItems[$id] = $hasWriteRigths || $this->multipleSelectedItems[$id]; //if once write mode, this is priority

		if(!isset($this->multipleEnableElementStateArr)) $this->multipleEnableElementStateArr = array();
		$this->multipleEnableElementStateArr[$id] = $elementEnableState;
		$this->multipleEnableElementState = $this->multipleEnableElementState | $elementEnableState;

		if(!isset($this->multipleElementStateArr)) {
			$this->multipleElementStateArr = array();
			$this->multipleElementStateRest = 2047 & $elementState;
			$this->multipleElementStateMedi = 2047 & (2047-$elementEnableState) | $elementEnableState & 2047 & $elementState;
			$this->multipleElementStateMediPerm = $elementEnableState & 2047 & $elementState;
		}
		else {
			$this->multipleElementStateRest = $this->multipleElementStateRest & $elementState;
			$this->multipleElementStateMedi = $this->multipleElementStateMedi & (2047-$elementEnableState) | $elementEnableState & $this->multipleElementStateMedi & $elementState;
			$this->multipleElementStateMediPerm = $this->multipleElementStateMediPerm & (2047-$elementEnableState) | $elementEnableState & ($this->multipleElementStateMediPerm | $elementState);
		}
		$this->multipleElementStateArr[$id] = $elementState;

		$this->multipleElementStatePerm = $this->multipleElementStatePerm | $elementState;
	}
	public function addItemsToMultipleSelection($ids, $elementEnableState, $elementState){
		if($ids != null){
			foreach($ids as $key=>$value){
				$this->addItemToMultipleSelection($key, $value, $elementEnableState[$key], $elementState[$key]);
			}
		}
	}
	/**
	 * @param boolean $computeMultipleElementState optional boolean. If true, then removing an item from the selection recalculates automatically the element state integers,
	 * else recalculation is differed until a manual call to computeMultipleElementStateInt is done. Defaults to true.
	 */
	public function removeItemFromMultipleSelection($id, $computeMultipleElementState=true){
		if(isset($this->multipleSelectedItems)){
			unset($this->multipleSelectedItems[$id]);
		}
		if(isset($this->multipleEnableElementStateArr) && isset($this->multipleElementStateArr)) {
			unset($this->multipleEnableElementStateArr[$id]);
			unset($this->multipleElementStateArr[$id]);
			if($computeMultipleElementState) $this->computeMultipleElementStateInt();
		}
	}
	/**
	 * @param boolean $computeMultipleElementState optional boolean. If true, then removing an item from the selection recalculates automatically the element state integers,
	 * else recalculation is differed until a manual call to computeMultipleElementStateInt is done. Defaults to true.
	 */
	public function removeItemsFromMultipleSelection($ids, $computeMultipleElementState=true){
		if($ids !=null){
			foreach($ids as $key=>$value){
				$this->removeItemFromMultipleSelection($key, false);
			}
			if($computeMultipleElementState) $this->computeMultipleElementStateInt();
		}
	}
	/**
	 * Updates the information regarding the element state of a selected element.
	 * @param int $id the element id
	 * @param int $elementEnableState the enable element status encoded as an integer, see ElementP->getEnableElementStateAsInt for info.
	 * @param int $elementState the element statuses values encoded as an integer, see Element->getStateAsInt for info.
	 * @param boolean $computeMultipleElementState optional boolean. If true, then the element state integers are recalculated,
	 * else recalculation is differed until a manual call to computeMultipleElementStateInt is done. Defaults to true.
	 */
	public function updateElementStateInMultipleSelection($id, $elementEnableState, $elementState, $computeMultipleElementState=true) {
		if($this->isInMultipleSelection($id)) {
			if($elementEnableState !== null) $this->multipleEnableElementStateArr[$id] = $elementEnableState;
			if($elementState !== null) $this->multipleElementStateArr[$id] = $elementState;
			if($computeMultipleElementState) $this->computeMultipleElementStateInt();
		}
	}
	/**
	 * Changes the element state from all elements in multiple selection using a bit mask
	 * @param int $mask the bit mask used to update only certain states
	 * @param int $elementState the element state encoded as an integer
	 * @param boolean $computeMultipleElementState optional boolean. If true, then the element state integers are recalculated,
	 * else recalculation is differed until a manual call to computeMultipleElementStateInt is done. Defaults to true.
	 */
	public function updateAllElementStateInMultipleSelection($mask, $elementState, $computeMultipleElementState=true) {
		if(isset($this->multipleElementStateArr)) {
			foreach($this->multipleElementStateArr as $id => &$val) {
				$val = $val & (2047-$mask) | $mask & $elementState;
			}
			if($computeMultipleElementState) $this->computeMultipleElementStateInt();
		}
	}
	public function isInMultipleSelection($id){
		if(isset($this->multipleSelectedItems)){
			return isset($this->multipleSelectedItems[$id]);
		}
		return false;
	}
	/**
	 * @param boolean $writableInfoAsValue if true, the value in the array is the element writable information, else the value is the element id.
	 */
	public function getMultipleSelection($writableInfoAsValue=false){
		if($this->multipleSelectedItems == null) return null;
		if($writableInfoAsValue) return $this->multipleSelectedItems;
		else {
			$keys = array_keys($this->multipleSelectedItems);
			return array_combine($keys, $keys);
		}
	}
	public function allHaveWriteRights(){
		if(!isset($this->multipleSelectedItems)) return false;
		return false === array_search(false, $this->multipleSelectedItems);
	}
	public function isMultipleSelection(){
		return ($this->multipleSelectedItems!=null);
	}
	public function getMultipleEnableElementStateAsInt() {
		return $this->multipleEnableElementState;
	}
	/**
	 * Returns an array[element id => element enable state as int]
	 * containing the element enable state policy for the multiple selection.
	 */
	public function getMultipleEnableElementStates() {
		return $this->multipleEnableElementStateArr;
	}
	/**
	 *
	 * @param int $level permission level. One of 1=permissive state, 2=medium permissive state, 3=medium state, 3=restrictive state
	 * if 1: state is 1 if at least one element has this state to 1 (executes an OR)
	 * if 2: state is 1 if at least one element having this state enabled by config, has a value equal to 1 (executes a filtered OR)
	 * if 3: state is 1 if all elements having this state enabled by config, have a value equal to 1 (executes a filtered AND)
	 * if 4: state is 1 if all elements have the state equal to 1.
	 * By default returns a value corresponding to the most restrictive level (4).
	 * @return number
	 */
	public function getMultipleElementStateAsInt($level=4) {
		if($level == 1) $returnValue = $this->multipleElementStatePerm;
		elseif($level == 2) $returnValue = $this->multipleElementStateMediPerm;
		elseif($level == 3) $returnValue = $this->multipleElementStateMedi;
		else $returnValue = $this->multipleElementStateRest;
		return $returnValue;
	}
	/**
	 * Returns an array[element id => element state as int]
	 * containing the element state for the multiple selection.
	 */
	public function getMultipleElementStates() {
		return $this->multipleElementStateArr;
	}
	/**
	 * Recalculates the integers encoding the multiple enable element state and
	 * multiple element state values.
	 */
	public function computeMultipleElementStateInt() {
		if(isset($this->multipleEnableElementStateArr) && isset($this->multipleElementStateArr)) {
			$this->multipleEnableElementState = 0;
			$this->multipleElementStateRest = 2047;
			$this->multipleElementStateMedi = 2047;
			$this->multipleElementStateMediPerm = 0;
			$this->multipleElementStatePerm = 0;
			$first = true;
			foreach($this->multipleEnableElementStateArr as $k => $elementEnableState) {
				$this->multipleEnableElementState = $this->multipleEnableElementState | $elementEnableState;

				if($first) {
					$this->multipleElementStateRest = 2047 & $this->multipleElementStateArr[$k];
					$this->multipleElementStateMedi = 2047 & (2047-$elementEnableState) | $elementEnableState & 2047 & $this->multipleElementStateArr[$k];
					$this->multipleElementStateMediPerm = $elementEnableState & 2047 & $this->multipleElementStateArr[$k];
					$first = false;
				}
				else {
					$this->multipleElementStateRest = $this->multipleElementStateRest & $this->multipleElementStateArr[$k];
					$this->multipleElementStateMedi = $this->multipleElementStateMedi & (2047-$elementEnableState) | $elementEnableState & $this->multipleElementStateMedi & $this->multipleElementStateArr[$k];
					$this->multipleElementStateMediPerm = $this->multipleElementStateMediPerm & (2047-$elementEnableState) | $elementEnableState & ($this->multipleElementStateMediPerm | $this->multipleElementStateArr[$k]);
				}

				$this->multipleElementStatePerm = $this->multipleElementStatePerm | $this->multipleElementStateArr[$k];
			}
		}
	}

	/**
	 * this will add LogExp to narrow the fetch to only the ids
	 * of the multipleSelection
	 */
	public function addLogExpOnMultipleSelection($multipleSelection){
		if($multipleSelection == null) return;
		$exp = LogExp::createInExp(FieldSelector::createElementAttributeSelector("id"), $multipleSelection);
		$this->addFieldSelectorLogExp($exp);
	}

	/*********************************************
	 * SortByFilter part
	 * *******************************************
	 */
	public function byPassDataTypeForSortByFilter($dataTypeName){
		return (!isset($dataTypeName) ||
//				$dataTypeName == "Attributs" ||
				$dataTypeName == "MultipleAttributs" ||
				$dataTypeName == "Texts" ||
				$dataTypeName == "Blobs"
				);
	}
	public function defineDataTypeSubFieldForSortByFilter($dataTypeName){
		if($dataTypeName == "Addresses"){
			$subs = array("street", "zip_code", "city", "state", "country");
		} else if($dataTypeName == "Files"){
			$subs = array("name", "type", "size", "date");
		} else if($dataTypeName == "TimeRanges"){
			$subs = array("begDate", "endDate");
		} else if($dataTypeName == "Urls"){
			$subs = array("name", "url");
		} else if($dataTypeName == "Emails"){
			$subs = array("value", "proofStatus", "externalAccessLevel", "externalAccessEndDate");
		} else {
			$subs = array("value");
		}
		return $subs;
	}
	public function defineElementSubFieldForSortByFilter(){
		return array("sys_date", "sys_creationDate", "state_important1", "state_important2", "state_locked", "state_archived", "state_deprecated", "state_hidden", "state_finalized", "state_approved", "state_dismissed", "state_blocked");
	}
	//this is used only if groupByFilter is seted. this allows to do the link with the field
	//from a groupBy filter
	private $sortByInformation;
	public function setSortByFieldSelectorList($fieldSelectorList){
		if(!isset($this->sortByInformation)){
			$this->sortByInformation = array();
		}
		$this->sortByInformation["fieldSelectorList"] = $fieldSelectorList;
	}

	//if a sortBy filter is setted, and defined, then we add a fieldSelector
	//to fetch the current field, to make it possible to display in the list
	public function addSortByFieldSelector($fieldListToCheckOn){
		try {
			$value = $this->getSortByItemFieldSelector($fieldListToCheckOn);
			if($value != null && !$value->isElementAttributeSelector()) {
				$this->getFieldSelectorList()->addFieldSelectorInstance($value);
			}
		} catch(ListException $e){
			if($e->getCode() != ListException::ALREADY_EXISTS) throw $e;
		}
	}
	public function getSortByItemFieldSelector($fieldListToCheckOn=null){
		$value = $this->getSortedBy();
		if($value == null || $this->sortByInformation==null || $this->sortByInformation["fieldSelectorList"]==null) return null;
		$returnValue = $this->sortByInformation["fieldSelectorList"]->getFieldSelectorFromKey($value);
		if($returnValue && $fieldListToCheckOn
			&& !$returnValue->isElementAttributeSelector()
			&& $fieldListToCheckOn->doesFieldExist($returnValue->getFieldName())==null){
			$this->setSortedBy("reset");
			return null;
		}
		return $returnValue;
	}
	public function addSortBySortingKey(){
		$key = $this->getSortedBy();
		$ascending = $this->isAscending();
		//add the sortingKey only if defined
		if($key != null && $key!=$this->getGroupBy()){
			$sortByFS = $this->getSortByItemFieldSelector();
			//if the $sortByFS is not existing then don't add sorting key
			if(isset($sortByFS)){
				if($sortByFS->isElementAttributeSelector()){
					$this->getFieldSortingKeyList()->addElementSortingKey($sortByFS->getSubFieldName(), $ascending);
				} else {
					$this->getFieldSortingKeyList()->addFieldSortingKey($sortByFS->getFieldName(), $sortByFS->getSubFieldName(), $ascending);
				}
			}
		}
	}
	/**
	 * setSortedBy
	 * if called with no parameter, then only make the sortingKeyList updated on current groupBy and current sortingKey
	 * else define the sortingKey and still updated on current groupBy
	 * - if sys_date bypass and set on sys_date
	 * - if reset bypass and reset to getDefaultSortingKey
	 */
	public function setSortedBy($key=null, $ascending=null){

		if($key !== null){
			$this->sortedBy = $key;
		} else {
			$key = $this->sortedBy;
		}
		if($ascending !== null){
			$this->ascendingSort = $ascending;
		} else {
		    $ascending = $this->ascendingSort;
		}

		if(($key == null || $key ==="reset") && method_exists($this->getFieldSelectorList(), "getDefaultSortingKey")){
			$key = $this->getFieldSelectorList()->getDefaultSortingKey();
			$ascending = $this->getFieldSelectorList()->getDefaultSortingAscending();
			$this->sortedBy = $key;
			$this->ascendingSort = $ascending;
		}

		$this->addGroupBySortingKey();
		$this->addSortBySortingKey();

	}
	public function isAscending(){ return $this->ascendingSort; }
	public function getSortedBy(){ return $this->sortedBy; }

	/******************************************/

	/*********************************************
	 * GroupByFilter part
	 * *******************************************
	 */
	public function byPassDataTypeForGroupByFilter($dataTypeName){
		return (!isset($dataTypeName) ||
				$dataTypeName == "Texts" ||
				$dataTypeName == "Varchars" ||
				$dataTypeName == "Blobs"
				);
	}
	public function defineDataTypeSubFieldForGroupByFilter($dataTypeName){
		if($dataTypeName == "Addresses"){
			$subs = array("city", "state", "country");
		} else if($dataTypeName == "Files"){
			$subs = array("name", "type", "size", "date");
		} else if($dataTypeName == "TimeRanges"){
			$subs = array("begDate");
		} else if($dataTypeName == "Urls"){
			$subs = array("url");
		} else if($dataTypeName == "Emails"){
			$subs = array("value", "proofStatus", "externalAccessLevel", "externalAccessEndDate");
		} else {
			$subs = array("value");
		}
		return $subs;
	}
	public function defineElementSubFieldForGroupByFilter(){
		return array("sys_date", "sys_creationDate", "state_important1", "state_important2", "state_locked", "state_archived", "state_deprecated", "state_hidden", "state_approved", "state_finalized", "state_dismissed", "state_blocked");
	}
	public function defineFieldsToFindDuplicates(){
		$subs = array(
			"Strings"=>"value",
			"Attributs"=>"value",
			"Emails"=>"value",
			"Urls"=>"url",
			"Files"=>"name",
			"__element"=>"id"
			);
		return $subs;
	}
	public function defineFieldsKeysForUpdate(){
		$subs = array(
			"Strings"=>"",
			"Attributs"=>"",
			"Emails"=>"",
			"Urls"=>"url",
			"Files"=>"name",
			"__element"=>"id"
			);
		return $subs;
	}

	public function getGroupBy(){
		return $this->groupBy;
	}
	private $onlyShowDuplicates = null;
	private $duplicatesIds = null;
	private $duplicatesElementEnableState = null;
	private $duplicatesElementState = null;

    /**
     * @param $key
     * @param bool $onlyShowDuplicates
     */
    public function setGroupBy($key, $onlyShowDuplicates=false){
		//groupBy can be set to null, this case dosen't mean we want to have the defaultGroupyByKey
		//but means we don't want to do any groupBy
        if($key ==="reset") {
            if(method_exists($this->getFieldSelectorList(), "getDefaultGroupByKey")) {
                $this->defaultGroupByKey = $this->getFieldSelectorList()->getDefaultGroupByKey();
                $this->defaultGroupByAscending = $this->getFieldSelectorList()->getDefaultGroupByAscending();
                $key = $this->defaultGroupByKey;
            }
            else $key = null;
        }

		$this->groupBy = $key;
		$this->addGroupBySortingKey();
		$this->addSortBySortingKey();
		$this->setGroupByOnlyDuplicates($onlyShowDuplicates);
	}
	public function setGroupByOnlyDuplicates($var){
		$this->resetDuplicatesIds();
		$this->onlyShowDuplicates = $var;
	}
	public function isGroupByOnlyDuplicates(){
		return $this->onlyShowDuplicates;
	}
	/**
	 * @param int $elementEnableState the enable element status encoded as an integer, see ElementP->getEnableElementStateAsInt for info.
	 * @param int $elementState the element statuses values encoded as an integer, see Element->getStateAsInt for info.
	 */
	public function addDuplicatesId($id, $writable, $elementEnableState, $elementState){
		if(!isset($this->duplicatesIds)) $this->duplicatesIds = array();
		$this->duplicatesIds[$id]=$writable;
		if(!isset($this->duplicatesElementEnableState)) $this->duplicatesElementEnableState = array();
		$this->duplicatesElementEnableState[$id] = $elementEnableState;
		if(!isset($this->duplicatesElementState)) $this->duplicatesElementState = array();
		$this->duplicatesElementState[$id] = $elementState;
	}
	/**
	 * Sets the duplicates Ids list
	 * @param Array|ValueListArrayImpl $ids
	 */
	public function setDuplicatesIds($ids) {
		if(is_array($ids)) $this->duplicatesIds = $ids;
		elseif($ids instanceof ValueListArrayImpl) $this->duplicatesIds = $ids->getListIterator();
		elseif(!empty($ids)) throw new ListContextException('Unsupported list of ids: should be an array or ValueListArrayImpl', ListContextException::INVALID_ARGUMENT);
	}
	public function getDuplicatesIds(){
		return $this->duplicatesIds;
	}
	public function getDuplicatesElementEnableStates() {
		return $this->duplicatesElementEnableState;
	}
	public function getDuplicatesElementStates() {
		return $this->duplicatesElementState;
	}
	public function resetDuplicatesIds(){
		$this->duplicatesIds = null;
		$this->duplicatesElementEnableState = null;
		$this->duplicatesElementState = null;
	}

	//this is used only if groupByFilter is seted. this allows to do the link with the field
	//from a groupBy filter
	private $groupByInformation;
	public function setGroupByFieldSelectorList($fieldSelectorList){
		if(!isset($this->groupByInformation)){
			$this->groupByInformation = array();
		}
		$this->groupByInformation["fieldSelectorList"] = $fieldSelectorList;
	}


	//this will always reset the sortingKeyList, because groupBy has priority
	public function addGroupBySortingKey(){
		$this->setGroupByItemCurrentValue(null);
		$groupByFS = $this->getGroupByItemFieldSelector();
		$this->setFieldSortingKeyList(FieldSortingKeyListArrayImpl::createInstance());
		if($groupByFS != null){

			if($this->getGroupBy() == $this->getSortedBy()) $ascending = $this->isAscending();
			else {
			    /*
			     * 1.  if groupByKey == defaultGroupByKeyKey then $ascending = defaultGroupByKeyAscending
			     * ----2.  else if groupByKey is defined in GroupBy activity then $ascending = (groupBy->fields->{groupByKey}->defaultSorted != DESC)
			     * 3.  else keep default logic (ascending = true except if date)
			     */
                if($this->getGroupBy() == $this->defaultGroupByKey) $ascending = $this->defaultGroupByAscending;
                else {
                    $ascending = true;
                    //exception for dates
                    switch ($groupByFS->getSubFieldName()) {
                        case "begDate":
                        case "date":
                        case "sys_date":
                        case "sys_creationDate":
                            $ascending = false;
                            break;
                    }
                }
			}

			if($groupByFS->isElementAttributeSelector()){
				$this->getFieldSortingKeyList()->addElementSortingKey($groupByFS->getSubFieldName(), $ascending);
			} else {
				$this->getFieldSortingKeyList()->addFieldSortingKey($groupByFS->getFieldName(), $groupByFS->getSubFieldName(), $ascending);
			}
		}
	}

	//if a groupBy filter is setted, and defined, then we add a fieldSelector
	//to fetch the current field, to make it possible to display in the list
	public function addGroupByFieldSelector($fieldListToCheckOn){
		try {
			$value = $this->getGroupByItemFieldSelector($fieldListToCheckOn);
			if($value != null && !$value->isElementAttributeSelector()) { // && $this->getGroupByItemFieldName() != $this->getElementFieldName()){
				$this->getFieldSelectorList()->addFieldSelectorInstance($value);
			}
		} catch(ListException $e){
			if($e->getCode() != ListException::ALREADY_EXISTS) throw $e;
		}
	}
	public function getGroupByItemFieldSelector($fieldListToCheckOn=null){
//		if(!isset($this->groupByInformation)) return null;
		$value = $this->getGroupBy();
		if($value == null) return null;
		if($this->groupByInformation["fieldSelectorList"] == null) return null;
		//first lookup in groupByFieldSelector list if exist
		$returnValue = $this->groupByInformation["fieldSelectorList"]->getFieldSelectorFromKey($value);
		//if not, lookup in the general FieldSelector list (could be on find duplicates or other special group by features)
		if(!$returnValue) $returnValue = $this->getFieldSelectorList()->getFieldSelectorFromKey($value);
		//if not, create the FieldSelector
		if(!$returnValue){
			list($dum, $fieldName, $subFieldName) = explode("(", str_replace(")","", $this->getGroupBy()));
			$returnValue = FieldSelector::createInstance($fieldName, ($subFieldName ? $subFieldName : "value"));
		}
		if($fieldListToCheckOn &&
			!$returnValue->isElementAttributeSelector() &&
			$fieldListToCheckOn->doesFieldExist($returnValue->getFieldName())==null){
			$this->setGroupBy("reset");
			return null;
		}
		return $returnValue;
	}

	//this allows to store the actual value of the groupBy, to be able to continue
	//next pages without repeating the current groupBy title
	//this value is always reset when redefining the searchBar
	private $groupByItemCurrentValue;
	public function getGroupByItemCurrentValue(){
		return $this->groupByItemCurrentValue;
	}
	public function setGroupByItemCurrentValue($value){
		$this->groupByItemCurrentValue = $value;
	}

	/******************************************/


	/********************************
	 * SEARCH FILTER definition
	 * ******************************
	 *
	 */

	/**
	 * reset the search bar with the last post data
	 * this will rebuild everything on current config
	 */
	public function recalculateSearchBar($p, $wigiiExecutor){
		$this->setSearchBar($p, $wigiiExecutor, $this->searchBar);
	}

	/**
	 * Sets the search bar from the POST or a computed array.
	 * @param Principal $p the current principal
	 * @param WigiiExecutor $wigiiExecutor
	 * @param Array $post http POST array or a PHP array
	 * @param GroupPListTreeArrayImpl $groupPTreeArrayImpl an optional GroupTree used to build the calculatedGroupIdsForSearch.
	 * @throws ListContextException
	 */
	public function setSearchBar($p, $wigiiExecutor, $post, $groupPTreeArrayImpl=null){
	    $configS = $wigiiExecutor->getConfigurationContext();
	    $exec = ServiceProvider::getExecutionService();
	    
		$this->searchBar = array();
		//the post injections controles are already done in the FiltersFormExecutor
		//don't fill in searchBar empty values
		if($post!=null){
			foreach($post as $key=>$values){
				if($key == "idForm" || $key == "action") continue; //remove the two formExecutor field
				if($values!=null){
					if(is_array($values)){
						foreach($values as $subKey=>$subValues){
							$this->searchBar[str_replace("_value", "", $key)][$subKey] = stripslashes($subValues);
						}
					} else {
						$this->searchBar[str_replace("_value", "", $key)] = stripslashes($values);
					}
				}
			}
		}

		//reset the LogExp before adding them the search criterias
		$this->setFieldSelectorLogExp(null);
		try { $this->addLogExpOnTextSearch($p, $wigiiExecutor); }
		catch (StringTokenizerException $e) { throw new ListContextException($e->getMessage(), ListContextException::INVALID_TextFilter); }
		try { $this->addLogExpOnTextAdvancedSearch($p, $wigiiExecutor); }
		catch (StringTokenizerException $e) { throw new ListContextException($e->getMessage(), ListContextException::INVALID_TextAdvancedFilter); }
		$this->addLogExpOnSelectSearch($wigiiExecutor);		
		$this->calculateGroupTreeForSearch($p, $wigiiExecutor, $groupPTreeArrayImpl);
		$this->addLogExpOnGroupFilterSearch();
		//add the textGroupSearch text after
		try { $this->addLogExpOnTextGroupSearch($p, $wigiiExecutor); }
		catch (StringTokenizerException $e) { throw new ListContextException($e->getMessage(), ListContextException::INVALID_TextGroupFilter); }

		// Medair (CWE) 12.03.2018: adds listFilterExp if defined in config
		$listFilterExp=(string)$configS->getParameter($p,$exec->getCrtModule(),'listFilterExp');
		if(!empty($listFilterExp)) {
		  $listFilterExp = $wigiiExecutor->evaluateFuncExp($p, $exec, str2fx($listFilterExp));
		  if(isset($listFilterExp)) {
		      if($listFilterExp instanceof LogExp) $this->addFieldSelectorLogExp($listFilterExp);
		      else throw new ListContextException('listFilterExp is not a valid LogExp', ListContextException::CONFIGURATION_ERROR);
		  }
		}
		
		//$this->setGroupBy($this->getGroupBy()/*,$this->isGroupByOnlyDuplicates()*/);
		$this->setSortedBy();

//		eput($this->getFieldSelectorLogExp());
	}

	private $xmlSubFieldsPerDataTypeCache;
	//very important to do this before the listContext is serialized. otherwise there will be problems
	//when the session is started next time, trying to make the xml elements
	protected function clearCacheSubFieldsForDataType(){
		unset($this->xmlSubFieldsPerDataTypeCache);
	}
	protected function getSubFieldsForDataType($wigiiExecutor, $dataTypeName){
		$configS = $wigiiExecutor->getConfigurationContext(); //ServiceProvider::getConfigService();
		if(!isset($this->xmlSubFieldsPerDataTypeCache)) $this->xmlSubFieldsPerDataTypeCache = array();
		if(!isset($this->xmlSubFieldsPerDataTypeCache[$dataTypeName])){
			$this->xmlSubFieldsPerDataTypeCache[$dataTypeName] = $configS->dt($dataTypeName)->xpath("*[@searchable='1']");
		}
		return $this->xmlSubFieldsPerDataTypeCache[$dataTypeName];
	}

	private $logExpOnTextSearch;
	public function getLogExpOnTextSearch(){ return $this->logExpOnTextSearch; }
	protected function setLogExpOnTextSearch($logExp){ $this->logExpOnTextSearch = ($logExp ? $logExp->reduceNegation(true) : null); }
	protected function addLogExpOnTextSearch($p, $wigiiExecutor){

		//first create the fieldSelector on wich we want to do each operation
		$transS = ServiceProvider::getTranslationService();
		$configS = $wigiiExecutor->getConfigurationContext(); //ServiceProvider::getConfigService();
		$exec = ServiceProvider::getExecutionService();

		$fsl = FieldSelectorListFromFieldList::createInstance();
		$fsl->setSelectedLanguages(array($transS->getLanguage()=>$transS->getLanguage()));

		//we do the search on every field and subField which has searchable=1
		if($this->getConfigGroupList() && $this->getConfigGroupList()->count()===1){
			$searchable = $configS->gf($p, reset($this->getConfigGroupList()->getListIterator())->getDbEntity())->xpath("*[@searchable='1'] | *[not(@searchable)]");
		} else {
			$searchable = $configS->mf($p, $exec->getCrtModule())->xpath("*[@searchable='1'] | *[not(@searchable)]");
		}
		$fullTextMatchFieldSelectorMapping = array();
		$fullTextColumnMapping = array();
		foreach ($searchable as $searchableFieldParams){
//			eput($searchableFieldParams->getName());
			$dataTypeName = (string)$searchableFieldParams["type"];
			switch($dataTypeName){
				case "":
				//Attributes or MultipleAttributs can be searched only
				//if defined in the selectSearchBar config
				case "Attributs":
				case "MultipleAttributs":
					continue 2;
					break;
				default:
					$subs = $this->getSubFieldsForDataType($wigiiExecutor, $dataTypeName);
					foreach($subs as $sub){
	//					eput($searchableFieldParams->getName()." ".$sub->getName());
						//filter only on sql type text or varchar
						switch((string)$sub["sqlType"]){
							case "text":
							case "varchar":
								$fs_key = $fsl->addFieldSelector($searchableFieldParams->getName(), $sub->getName());
								if($fullTextColumnMapping[(string)$searchableFieldParams->getName()]==null) $fullTextColumnMapping[(string)$searchableFieldParams->getName()] = array();
								$fullTextColumnMapping[(string)$searchableFieldParams->getName()][] = (string)$sub->getName();
								$fullTextMatchFieldSelectorMapping[$fs_key] = array($dataTypeName, (string)$searchableFieldParams->getName(), (string)$sub->getName());
								break;
							default: null;
						}
					}
			}
		}

		$this->clearCacheSubFieldsForDataType();

		//creates LogExp from text search
		$tempTextSearch = $this->getTextSearch();

		/**
		 * search the id if one keyword #number
		 */
		preg_match_all('/(\#[\d]{1,})/', $tempTextSearch, $matches);
		$filterOnId = array();
		foreach ($matches[0] as $key => $value) {
			  $filterOnId[] = str_replace("#", "", $value);
			  //delete in textSearch
			  $tempTextSearch = str_replace($value, "", $tempTextSearch);
		}
		$fslId = FieldSelectorListArrayImpl::createInstance();
		$fslId->addFieldSelectorInstance(FieldSelector::createElementAttributeSelector("id"));

		/**
		 * pre traitement to help finding telephone numbers
		 */
		//split any number with more than 7 digit in a row
		preg_match_all('/([\d]{7,})/', $tempTextSearch, $matches);
		foreach ($matches[0] as $key => $value) {
				// removes leading zeros
				$tempTab= ltrim($value,'0');
				//split the number
				$tempTab = str_split($tempTab);
				//add % for each group of two number starting from the end
				//after three groups, add % between each number
				$buffer = "";
				$len = count($tempTab);
				for($i=0; $i<$len; $i++){
					$buffer = $tempTab[$len-1-$i].$buffer;
					if($i!=$len-1 && ($i%2=="1" || $i > 4)){
						$buffer ="%".$buffer;
					}
				}
				$tempTab = $buffer;
				$tempTextSearch = str_replace($value, $tempTab, $tempTextSearch);
		}
		//eput($tempTextSearch);
		//detect any phone number pattern with separator and add % instead of separators
		$search = array (" ", "\t", "-", ".", "/", "\n", "\r", "\0", "\x0B");
		preg_match_all('/(\d+([\.|\s|\/|\-]\d+){3,})/', $tempTextSearch, $matches);
		foreach ($matches[0] as $key => $value) {
			// replaces separators by '%'	
			$tempTab = str_replace($search, "%", $value);
			// removes leading zeros
			$tempTab = preg_replace('/0+([1,2,3,4,5,6,7,8,9]\d+)/', '${1}', $tempTab);
			$tempTextSearch = str_replace($value, $tempTab, $tempTextSearch);
		}
		//eput($tempTextSearch);
		$expId = TechnicalServiceProvider::getSearchBarOrLogExpParser()->createLogExpOnCriteria($fslId, implode(" ", $filterOnId));
		$exp = null;
		//sql full text search
//		if($tempTextSearch){
//			//match against, IN BOOLEAN MODE, forcing filtering on each word
//			foreach($fullTextMatchFieldSelectorMapping as $fs_key=>$dt_fname_subfname){
//				$fs = $fsl->getFieldSelectorFromKey($fs_key);
//				$dataTypeName = $dt_fname_subfname[0];
//				if($fs){
//					switch($dataTypeName){
//						case "Addresses":
//						case "Blobs":
//						case "Emails":
//						case "Files":
//						case "Strings":
//						case "Texts":
//						case "Urls":
//						case "Varchars":
//							if(!$exp) $exp = LogExp::createOrExp();
//							$exp->addOperand(LogExp::createMatchAgainstExp($fs, $tempTextSearch));
//							break;
//
//						default:
//							if(!$exp) $exp = LogExp::createOrExp();
//							$exp->addOperand(LogExp::createLikeExp($fs, "'%".str_replace(" ", "% %", str_replace("'", "", $tempTextSearch))."%'"));
//					}
//				}
//			}
//		}
		//sql like filter (no use of sql fullText index)
		$exp = TechnicalServiceProvider::getSearchBarLogExpParser()->createLogExpOnCriteria($fsl, $tempTextSearch);
		if($exp!=null && $expId != null){
			$tempExp = LogExp::createAndExp();
			$tempExp->addOperand($exp);
			$tempExp->addOperand($expId);
			$exp = $tempExp;
		} else if($expId != null){
			$exp = $expId;
		}
		
		
		/**
		 * post traitement to help finding telephone numbers
		 */
		$search = array (" ", "\t", "-", ".", "/", "\n", "\r", "\0", "\x0B");
		preg_match_all('/([\d|\s|\.|\/\-]{7,})/', $tempTextSearch, $matches);
		foreach ($matches[0] as $key => $value) {
			// removes leading zeros
			$tempTab= ltrim($value,'0');
			//add a % between each number
			$tempTab = str_replace($search, "", $tempTab);
			$varNbOfDigit = mb_strlen($tempTab);
			$tempTab = str_split($tempTab);
			$tempTab = implode("%", $tempTab);
			$tempTab .= " AND (".$varNbOfDigit." >= LEN('".$tempTab."') AND ".($varNbOfDigit*2)." <= LEN('".$tempTab."') )";
			$tempTextSearch = str_replace($value, $tempTab, $tempTextSearch);
		}

		if($exp != null){
			// reduces LogExp
			$exp = $exp->reduceNegation(true);
			$this->addFieldSelectorLogExp($exp);
//			echo alert($exp->displayDebug());
		}
		$this->setLogExpOnTextSearch($exp);
//		echo alert($this->getFieldSelectorLogExp()->displayDebug());
	}
	private $logExpOnTextAdvancedSearch;
	public function getLogExpOnTextAdvancedSearch(){ return $this->logExpOnTextAdvancedSearch; }
	protected function setLogExpOnTextAdvancedSearch($logExp){ $this->logExpOnTextAdvancedSearch = ($logExp ? $logExp->reduceNegation(true) : null); }
	protected function addLogExpOnTextAdvancedSearch($p, $wigiiExecutor){
		$transS = ServiceProvider::getTranslationService();
		$configS = $wigiiExecutor->getConfigurationContext(); //ServiceProvider::getConfigService();
		$exec = ServiceProvider::getExecutionService();

		$exp = TechnicalServiceProvider::getFieldSelectorLogExpParser()->createLogExpFromString($this->getTextAdvancedSearch());

		if($exp != null){
			// reduces LogExp
			$exp = $exp->reduceNegation(true);
			$this->addFieldSelectorLogExp($exp);
//			echo alert($exp->displayDebug());
		}
		$this->setLogExpOnTextAdvancedSearch($exp);
//		echo alert($this->getFieldSelectorLogExp()->displayDebug());
	}
	private $logExpOnTextGroupSearch;
	public function getLogExpOnTextGroupSearch(){ return $this->logExpOnTextGroupSearch; }
	protected function setLogExpOnTextGroupSearch($logExp){ $this->logExpOnTextGroupSearch = ($logExp ? $logExp->reduceNegation(true) : null); }
	protected function addLogExpOnTextGroupSearch($p, $wigiiExecutor){
		$transS = ServiceProvider::getTranslationService();
		$configS = $wigiiExecutor->getConfigurationContext(); //ServiceProvider::getConfigService();
		$exec = ServiceProvider::getExecutionService();

		$exp = TechnicalServiceProvider::getFieldSelectorLogExpParser()->createLogExpFromString($this->getTextGroupSearch());

		if($exp != null){
			// reduces LogExp
			$exp = $exp->reduceNegation(true);
			$storeExp = $exp->reduceNegation(true);
			$this->addGroupLogExp($exp);
//			echo alert($exp->displayDebug());
		}
		$this->setLogExpOnTextGroupSearch($storeExp);
//		echo alert($this->getFieldSelectorLogExp()->displayDebug());
	}
	private $logExpOnSelectSearch;
	public function getLogExpOnSelectSearch(){ return $this->logExpOnSelectSearch; }
	protected function setLogExpOnSelectSearch($logExp){ $this->logExpOnSelectSearch = ($logExp ? $logExp->reduceNegation(true) : null); }
	//this method add the logExp for all select which refers to a field
	protected function addLogExpOnSelectSearch($wigiiExecutor){
		$exp = null;

		if($this->getSelectSearchFields() == null) return;

		foreach($this->getSelectSearchFields() as $selectSearchField=>$fieldName){
			//Date filter
			if(strpos($fieldName,"!TR!")===0){
				list($notUseful, $dataTypeName, $elFieldName, $elSubFieldName, $elAttributName) = explode(".", $fieldName);
				$begDateValue = $this->getSelectSearchField($selectSearchField."_begDate");
				$endDateValue = $this->getSelectSearchField($selectSearchField."_endDate");
				if($begDateValue == null && $endDateValue == null) continue;
				if(!isset($exp)) $exp  = LogExp::createAndExp();

				//eput("add filter on ".$dataTypeName." ".$elFieldName." ".$elSubFieldName." ".$elAttributName." from ".$begDateValue." to ".$endDateValue);

				//convert everything to timestamp as date comparaison in mysql are always converted to timestamp
				//http://dev.mysql.com/doc/refman/5.0/fr/comparison-operators.html?iframe=true&width=100%&height=100%
				if($begDateValue) $begDateValue = strtotime($begDateValue);
				//add one day to the final date to include it
				if($endDateValue) $endDateValue = strtotime($endDateValue)+24*3600;

				if($dataTypeName=="TimeRanges"){
					//special case for TimeRange search as we need to catch dates which could be in between
					$fsStartDate = FieldSelector::createInstance($elFieldName, "begDate");
					$fsEndDate = FieldSelector::createInstance($elFieldName, "endDate");

					$dateExp = LogExp :: createAndExp();
					//in search don't look for time. because the view are always full day.
					//adding and on time makes problems as well because the logExp should be a concatenation of date + time, not separate
					if($endDateValue) $dateExp->addOperand(LogExp :: createSmallerExp($fsStartDate, $endDateValue));
					if($begDateValue){
						$orLogExp = LogExp :: createOrExp();
						$orLogExp->addOperand(LogExp :: createGreaterEqExp($fsEndDate, $begDateValue));
						$and2LogExp = LogExp :: createAndExp();
						$and2LogExp->addOperand(LogExp :: createEqualExp($fsEndDate, null));
						$and2LogExp->addOperand(LogExp :: createGreaterEqExp($fsStartDate, $begDateValue));
						$orLogExp->addOperand($and2LogExp);
						$dateExp->addOperand($orLogExp);
					}
					$exp->addOperand($dateExp);
				} else {
					if($elAttributName){
						$fs = FieldSelector::createElementAttributeSelector($elAttributName);
					} else {
						if($elSubFieldName == "") $elSubFieldName = "value";
						$fs = FieldSelector::createInstance($elFieldName, $elSubFieldName);
					}

					if($begDateValue){
						$exp->addOperand(LogExp::createGreaterEqExp($fs, $begDateValue));
					}
					if($endDateValue){
						$exp->addOperand(LogExp::createSmallerExp($fs, $endDateValue));
					}
				}
//				eput($selectSearchField);
//				eput($dataTypeName." ".$elFieldName." ".$elSubFieldName." ".$elAttributName);
//				eput($this->getSelectSearchField($selectSearchField."_begDate"));
				continue;
			}
			//Booleans filter
			if(strpos($fieldName,"!BO!")===0){
				list($notUseful, $dataTypeName, $elFieldName, $elSubFieldName, $elAttributName) = explode(".", $fieldName);
				if($elSubFieldName == "") $elSubFieldName = "value";
				$boolValue = $this->getSelectSearchField($selectSearchField);
				if($boolValue == null) continue;

				if(!isset($exp)) $exp  = LogExp::createAndExp();

				if($boolValue === "true"){
					$boolValue = true;
				} else {
					$boolValue = false;
				}
				$fs = FieldSelector::createInstance($elFieldName, $elSubFieldName);
				$exp->addOperand(LogExp::createEqualExp($fs, $boolValue));
				continue;
			}
			$fs = FieldSelector::createInstance($fieldName);
			$value = $this->getSelectSearchField($selectSearchField);
			if($value == null) continue;

			if(!isset($exp)) $exp  = LogExp::createAndExp();

			if(is_array($value)){
				if($this->isReverseSelectSearchField($selectSearchField)){
					$exp->addOperand(LogExp::createNotInExp($fs, $value));
				} else {
					$exp->addOperand(LogExp::createInExp($fs, $value));
				}
			} else {
				if($this->isReverseSelectSearchField($selectSearchField)){
					$exp->addOperand(LogExp::createNotEqualExp($fs, $value));
				} else {
					$exp->addOperand(LogExp::createEqualExp($fs, $value));
				}
			}
		}

		if($exp != null){
//			eput($exp);
			$this->addFieldSelectorLogExp($exp);
		}
		$this->setLogExpOnSelectSearch($exp);
	}
	//this method add the logExp for all select which refers to a field
	private $groupLogExp;
	protected function setGroupLogExp($groupLogExp){
		$this->groupLogExp = $groupLogExp;
	}
	public function getGroupLogExp(){
		return $this->groupLogExp;
	}
	private $groupFilterLogExp;
	protected function setGroupFilterLogExp($groupFilterLogExp){
		$this->groupFilterLogExp = $groupFilterLogExp;
	}
	public function getGroupFilterLogExp(){
		return $this->groupFilterLogExp;
	}

	/**
	 * Recalculates Group LogExp based on filter criterias.
	 * @param int $desiredGroupId if set, will narrow the search space to this group ID, instead of GroupList.
	 * @param boolean $includeChildrenForDesiredGroup defines if children group of desired group should be selected or not, true by default, ignored if desiredGroupId is not set.
	 */
	public function addLogExpOnGroupFilterSearch($desiredGroupId=null, $includeChildrenForDesiredGroup=true){
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();

		//add groupList logExp;
		if($this->getGroupPList()==null || $this->getGroupPList()->isEmpty()){
			$this->groupLogExp = null;
			return;
		}

		$includeChildren = !$desiredGroupId && $this->doesGroupListIncludeChildren() || $desiredGroupId && $includeChildrenForDesiredGroup;
		
		// if a calculated group tree exists for search, then takes this one
		$groupLogExp = $this->getCalculatedGroupTreeForSearch();
		if(!empty($groupLogExp)) {
			// builds group tree as a tree of stdClass
			$groupTree = array();
			foreach($groupLogExp as $groupId => $parentGroupId) {
				// gets or creates current group node
				$node = $groupTree[$groupId];
				if(!isset($node)) {
					$node = array('id'=>$groupId, 'id_group_parent'=>$parentGroupId, 'children'=>array());
					$node = (object)$node;
					$groupTree[$groupId] = $node;
				}
				else {
					$node->id_group_parent = $parentGroupId;
				}
				// adds current group node to parent node (if not root)
				if(isset($parentGroupId)) {
					$parentNode = $groupTree[$parentGroupId];
					if(!isset($parentNode)) {
						$parentNode = array('id'=>$parentGroupId, 'id_group_parent'=>null, 'children'=>array());
						$parentNode = (object)$parentNode;
						$groupTree[$parentGroupId] = $parentNode;
					}
					$parentNode->children[$groupId] = $node;
				}
			}
			// removes parent which are not in search space
			$groupTree = array_intersect_key($groupTree, $groupLogExp);
			
			/*
			 * foreach selected groups
			 * 	if group is in search space
			 * 		if includeChildren then implode group tree from selected group
			 * 		else take selected group
			 *	else
			 *		takes search space AND (includeChildren ? INGR(selected group) : ING(selected group))
			 */
			if($desiredGroupId) {
				$group = $groupTree[$desiredGroupId];
				// narrows search space to desired group
				if(isset($group)) {
					if($includeChildren) {
						$groupLogExp = array('groupIds'=>array());
						$groupLogExp = (object)$groupLogExp;
						$this->implodeGroupTree($group, $groupLogExp);
						$groupLogExp = $groupLogExp->groupIds;						
					}
					else $groupLogExp = array($desiredGroupId => $desiredGroupId);
					$groupLogExp = implode(",", $groupLogExp);
					$groupLogExp = "ING(id IN (".$groupLogExp."))";
				}
				// intersects search space with desired group
				else {
					$groupLogExp = array_keys($groupLogExp);
					$groupLogExp = implode(",", $groupLogExp);
					$groupLogExp = "ING(id IN (".$groupLogExp."))";
					if($includeChildren) {
						$desiredGroupExp = "INGR(id IN (".$desiredGroupId."))";
					}
					else {
						$desiredGroupExp = "ING(id IN (".$desiredGroupId."))";
					}
					$groupLogExp = $desiredGroupExp." AND ".$groupLogExp;
				}
			}	
			// else intersects with selected group
			elseif($this->getGroupPList()->count() == 1) {
				$group = $this->getGroupPList()->getListIterator();
				$group = reset($group);
				$groupId = $group->getId();				
				$group = $groupTree[$groupId];
				// narrows search space to selected group
				if(isset($group)) {
					if($includeChildren) {
						$groupLogExp = array('groupIds'=>array());
						$groupLogExp = (object)$groupLogExp;
						$this->implodeGroupTree($group, $groupLogExp);
						$groupLogExp = $groupLogExp->groupIds;
					}
					else $groupLogExp = array($groupId => $groupId);
					$groupLogExp = implode(",", $groupLogExp);
					$groupLogExp = "ING(id IN (".$groupLogExp."))";
				}
				// intersects search space with selected group
				else {
					$groupLogExp = array_keys($groupLogExp);
					$groupLogExp = implode(",", $groupLogExp);
					$groupLogExp = "ING(id IN (".$groupLogExp."))";
					if($includeChildren) {
						$desiredGroupExp = "INGR(id IN (".$groupId."))";
					}
					else {
						$desiredGroupExp = "ING(id IN (".$groupId."))";
					}
					$groupLogExp = $desiredGroupExp." AND ".$groupLogExp;
				}
			}
			// else 
			else {
				// takes only search space
				if($includeChildren) {
					$groupLogExp = array_keys($groupLogExp);
					$groupLogExp = implode(",", $groupLogExp);
					$groupLogExp = "ING(id IN (".$groupLogExp."))";
				}
				// intersects search space with group list
				else {
					//throw new ListContextException("Cannot select ".$this->getGroupPList()->count()." groups without children", ListContextException::UNSUPPORTED_OPERATION);
					$desiredGroupExp = array();
					$narrowLogExp = array('groupIds'=>array());
					$narrowLogExp = (object)$narrowLogExp;
					foreach($this->getGroupPList()->getListIterator() as $groupP) {
						$groupId = $groupP->getId();
						$group = $groupTree[$groupId];
						// narrows search space to selected group
						if(isset($group) && isset($narrowLogExp)) {
							$narrowLogExp->groupIds[$groupId] = $groupId;
						}
						// stops narrowing space and intersects whole search space with selected group
						else $narrowLogExp = null;
						$desiredGroupExp[$groupId] = $groupId;
					}
					
					// if narrowed search space exists, takes this one
					if(isset($narrowLogExp)) {
						$groupLogExp = implode(",", $narrowLogExp->groupIds);
						$groupLogExp = "ING(id IN (".$groupLogExp."))";
					}
					// else intersects whole search space with group list
					else {
						$desiredGroupExp = implode(",", $desiredGroupExp);
						$desiredGroupExp = "ING(id IN (".$desiredGroupExp."))";
							
						$groupLogExp = array_keys($groupLogExp);
						$groupLogExp = implode(",", $groupLogExp);
						$groupLogExp = "ING(id IN (".$groupLogExp."))";
						$groupLogExp = $groupLogExp." AND ".$desiredGroupExp;
					}
				}
			}
			/*	
			else {
				$desiredGroupExp = array();
				$narrowLogExp = array('groupIds'=>array());
				$narrowLogExp = (object)$narrowLogExp;
				foreach($this->getGroupPList()->getListIterator() as $groupP) {
					$groupId = $groupP->getId();
					$group = $groupTree[$groupId];
					// narrows search space to selected group
					if(isset($group) && isset($narrowLogExp)) {
						if($includeChildren) $this->implodeGroupTree($group, $narrowLogExp);
						else $narrowLogExp->groupIds[$groupId] = $groupId;
					}
					// stops narrowing space and intersects whole search space with selected group
					else $narrowLogExp = null;
					$desiredGroupExp[$groupId] = $groupId;
				}
				
				// if narrowed search space exists, takes this one
				if(isset($narrowLogExp)) {
					$groupLogExp = implode(",", $narrowLogExp->groupIds);
					$groupLogExp = "ING(id IN (".$groupLogExp."))";
				}
				// else intersects whole search space with group list
				else {					
					$desiredGroupExp = implode(",", $desiredGroupExp);
					if($includeChildren){
						$desiredGroupExp = "INGR(id IN (".$desiredGroupExp."))";
					} else {
						$desiredGroupExp = "ING(id IN (".$desiredGroupExp."))";
					}
					
					$groupLogExp = array_keys($groupLogExp);
					$groupLogExp = implode(",", $groupLogExp);
					$groupLogExp = "ING(id IN (".$groupLogExp."))";
					$groupLogExp = $groupLogExp." AND ".$desiredGroupExp;
				}							
			}
			*/		
		}
		// else uses the GroupPList
		else {			
			if($desiredGroupId) {
				$groupLogExp = array($desiredGroupId => $desiredGroupId);
			}
			else {
				$groupLogExp = array();
				foreach($this->getGroupPList()->getListIterator() as $groupP){
					$groupLogExp[] = $groupP->getId();
				}
			}
			$groupLogExp = implode(",", $groupLogExp);			
			if($includeChildren){
				$groupLogExp = "INGR(id IN (".$groupLogExp."))";
			} else {
				$groupLogExp = "ING(id IN (".$groupLogExp."))";
			}
		}
		
		//add groupFilter
		if($this->getSelectGroupFilterFields() != null){
			$groupFilterText = array();
			foreach($this->getSelectGroupFilterFields() as $selectGroupFilterField=>$groupLogExpList){
				$value = $this->getSelectSearchField($selectGroupFilterField);
				if($value == null) continue;
				//fput($value." ".$crtGroupLogExp);
				if(is_array($value)){
					$temp = array();
					foreach($value as $val){
						if($groupLogExpList[$val] != null){
							$temp[] = $groupLogExpList[$val];
						}
					}
					if($temp!=null){
						$groupFilterText[] = "(".implode(" OR ", $temp).")";
					}
				} else {
					if($groupLogExpList[$value]){
						$groupFilterText[] = $groupLogExpList[$value];
					}
				}
			}
			$groupFilterText = implode(" AND ", $groupFilterText);
			//fput($groupFilterText);
			if($groupFilterText) $this->setGroupFilterLogExp($parser->createLogExpFromString($groupFilterText));
			else $this->setGroupFilterLogExp(null);
		}

		$this->groupLogExp = $parser->createLogExpFromString($groupLogExp);
		if($this->getGroupFilterLogExp()) $this->addGroupLogExp($this->getGroupFilterLogExp()->reduceNegation(true));
		if($this->getLogExpOnTextGroupSearch()) $this->addGroupLogExp($this->getLogExpOnTextGroupSearch()->reduceNegation(true));
	}
	/**
	 * Implodes a Group tree starting from a group and appends the result into a list of IDs stored into a StdClass
	 * @param StdClass $group a StdClass instance representing a group {id,id_group_parent,children:Array of Groups as StdClass}
	 * @param StdClass $groupList a given StdClass instance {groupIds:Array(groupId=>groupId)} where to store the flattened tree
	 */
	private function implodeGroupTree($group, $groupList) {
		if(!isset($groupList)) return;
		if(isset($group)) {
			$groupList->groupIds[$group->id] = $group->id;
			if(!empty($group->children)) {
				foreach($group->children as $c) {
					$this->implodeGroupTree($c, $groupList);
				}
			}
		}
	}
	
	
	public function getSearchBar(){
		return $this->searchBar;
	}
	public function getTextSearch(){
		if(!isset($this->searchBar)) return null;
		return $this->searchBar[ListContext::TextSearchField];
	}
	public function getDefaultTextSearch(){
		if(!isset($this->searchBar)) return null;
		return $this->searchBar[ListContext::DefaultTextSearchField];
	}
	public function getTextGroupSearch(){
		if(!isset($this->searchBar)) return null;
		return $this->searchBar[ListContext::TextGroupSearchField];
	}
	public function getDefaultTextGroupSearch(){
		if(!isset($this->searchBar)) return null;
		return $this->searchBar[ListContext::DefaultTextGroupSearchField];
	}
	public function getTextAdvancedSearch(){
		if(!isset($this->searchBar)) return null;
		return $this->searchBar[ListContext::TextAdvancedSearchField];
	}
	public function getDefaultTextAdvancedSearch(){
		if(!isset($this->searchBar)) return null;
		return $this->searchBar[ListContext::DefaultTextAdvancedSearchField];
	}
	public function getReverseSelectSearch(){
		if(!isset($this->searchBar)) return null;
		return $this->searchBar[ListContext::ReverseSelectSearchField];
	}
	public function isReverseSelectSearchField($selectSearchField){
		if(!isset($this->searchBar)) return null;
		if(!isset($this->searchBar[ListContext::ReverseSelectSearchField])) return null;
		return false !== array_search($selectSearchField, $this->searchBar[ListContext::ReverseSelectSearchField]);
	}
	public function getSelectSearchField($selectSearchField){
		if(!isset($this->searchBar)) return null;
		return $this->searchBar[$selectSearchField];
	}
	public function getLimitFilterInGroupForSearch() {
		if(!isset($this->searchBar)) return null;
		return $this->searchBar[ListContext::LimitFilterInGroupSearchField];
	}
	public function getExcludeGroupsInSearch() {
		if(!isset($this->searchBar)) return null;
		return $this->searchBar[ListContext::ExcludeGroupsSearchField];
	}
	
	private $selectSearchFields;
	public function getSelectSearchFields(){
		return $this->selectSearchFields;
	}
	public function addSelectSearchField($selectSearchField, $fieldName){
		if(!isset($this->selectSearchFields)){
			$this->selectSearchFields = array();
		}
		$this->selectSearchFields[$selectSearchField] = $fieldName;
	}
	public function resetSelectSearchField(){
		$this->selectSearchFields = null;
	}


	private $selectGroupFilterFields;
	public function getSelectGroupFilterField($selectGroupFilterField){
		if(!isset($this->selectGroupFilterFields)) return null;
		return $this->selectGroupFilterFields[$selectGroupFilterField];
	}
	public function getSelectGroupFilterFields(){
		return $this->selectGroupFilterFields;
	}
	public function addSelectGroupFilterField($selectGroupFilterField, $groupLogExpList){
		if(!isset($this->selectGroupFilterFields)){
			$this->selectGroupFilterFields = array();
		}
		$this->selectGroupFilterFields[$selectGroupFilterField] = $groupLogExpList;
	}
	public function resetGroupFilterFields(){
		$this->selectGroupFilterFields = null;
	}

	private $calculatedGroupTreeForSearch;
	/**
	 * @return Array an array(groupId=>parentGroupId)
	 */
	public function getCalculatedGroupTreeForSearch() {return $this->calculatedGroupTreeForSearch;}
	public function hasCalculatedGroupTreeForSearch() {return !empty($this->calculatedGroupTreeForSearch);}
	/**
	 * @param Array $groupTree array(groupId=>parentGroupId)
	 */
	public function setCalculatedGroupTreeForSearch($groupTree) {
		$this->calculatedGroupTreeForSearch = $groupTree;
	}	
	/**
	 * @param Array $groupTree array(groupId=>parentGroupId)
	 */
	public function addCalculatedGroupTreeForSearch($groupTree) {
		if(empty($this->calculatedGroupTreeForSearch)) $this->calculatedGroupTreeForSearch = array();
		if(!empty($groupTree)) {
			foreach($groupTree as $groupId=>$parentGroupId) {
				$this->calculatedGroupTreeForSearch[$groupId] = $parentGroupId;
			}
		}
	}
	public function resetCalculatedGroupTreeForSearch() {
		$this->calculatedGroupTreeForSearch = null;
	}
	
	/**
	 * Calculates the group tree that should be used as a search space.
	 * @param Principal $p the current principal
	 * @param WigiiExecutor $wigiiExecutor
	 * @param GroupPListTreeArrayImpl $groupPTreeArrayImpl the optional global tree view on which to search and reduces it by using the getLimitFilterInGroupForSearch and getExcludeGroupsInSearch.
	 * @return Array the calculated group tree as an array(groupId=>parentGroupId).
	 */
	protected function calculateGroupTreeForSearch($p, $wigiiExecutor, $groupPTreeArrayImpl=null) {
		$includeGroups = $this->getLimitFilterInGroupForSearch();
		$excludeGroups = $this->getExcludeGroupsInSearch();
		
		if(empty($includeGroups) && empty($excludeGroups)) return $this->getCalculatedGroupTreeForSearch();
		
		// calculates global space if needed
		if(!isset($groupPTreeArrayImpl)) {
			$groupPTreeArrayImpl = GroupPListTreeArrayImpl :: createInstance();
			$groupAS = ServiceProvider::getGroupAdminService();
			// extracts module
			$groupList = $this->getGroupPList();
			if(!$groupList->isEmpty()) {
				$groupP = reset($groupList->getListIterator());
				$module = $groupP->getGroup()->getModule();
			}
			else $module = ServiceProvider::getExecutionService()->getCrtModule();
			$groupAS->getAllGroups($p, $module, $groupPTreeArrayImpl, $groupAS->getListFilterForSelectGroupWithoutDetail());
		}		
		
		// reduce search tree
		$treeReduction = LCGroupIdsForSearchTreeVisitor::createInstance($includeGroups, $excludeGroups);
		$groupPTreeArrayImpl->visitInDepth($treeReduction);
		$this->setCalculatedGroupTreeForSearch($treeReduction->getGroupTree());
	}
	
	/**********************************/


	/******************************************/

	/******************************************
	 * Fetch Criteria Manipulation
	 * ****************************************
	 */
	//will fill the current listContext with the same criterias which will make the same
	//selection of items of the $listContext parameter. we do not take pagination, fieldSelector
	public function matchFetchCriteria($listContext){
		//Field sorting key
		$this->setFieldSortingKeyList($listContext->getFieldSortingKeyList());
		//Field logique expression
		$this->resetMultipleSelection();
		$this->addItemsToMultipleSelection($listContext->getMultipleSelection(true),
		$this->getMultipleEnableElementStates(), $this->getMultipleElementStates());
		$this->setFieldSelectorLogExp($listContext->getFieldSelectorLogExp());
		//GroupPList
		//setGroupPList need to have the groupFilterText, the TextGroupSearch and calculatedGroupIdsForSearch to make a correct calculation
		$this->setGroupFilterLogExp($listContext->getGroupFilterLogExp());
		$this->setLogExpOnTextGroupSearch($listContext->getLogExpOnTextGroupSearch());
		$this->setCalculatedGroupTreeForSearch($listContext->getCalculatedGroupTreeForSearch());
		$this->setGroupPList($listContext->getGroupPList(), $listContext->doesGroupListIncludeChildren());
		//GroupLogExp
		//$this->setGroupLogExp($listContext->getGroupLogExp());
		$this->matchSortingCriteria($listContext);
	}

	public function matchSortingCriteria($listContext){
		$this->setFieldSortingKeyList($listContext->getFieldSortingKeyList());
	}
	//this will make the listContext clean, as begining
	public function resetFetchCriteria($p, $wigiiExecutor){
		$this->resetMultipleSelection();
		$this->setFieldSortingKeyList(null);
		$this->setFieldSelectorLogExp(null);
		$this->setCalculatedGroupTreeForSearch(null);
		$this->setGroupPList(null);
		//$this->setGroupLogExp(null);
		$this->setSearchBar($p, $wigiiExecutor, null);
	}
}

/**
 * ListContext GroupIdsForSearchTreeVisitor
 * Created by CWE on 02.10.2015
 */
class LCGroupIdsForSearchTreeVisitor implements TreeInDepthVisitor {
	private $groupTree;
	private $recording;
	private $startedId;
	
	// Object lifecycle
	
	public static function createInstance($includeGroups, $excludeGroups)  {
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->setIncludeGroups($includeGroups);
		$returnValue->setExcludeGroups($excludeGroups);
		return $returnValue;
	}
	
	public function reset() {
		$this->freeMemory();
		$this->groupTree = array();
		$this->recording = true; // by default records all groups
		$this->startedId = null; // by default records all groups
	}
	public function freeMemory() {
		unset($this->groupTree);
		unset($this->includeGroups);
		unset($this->excludeGroups);
	}
	
	// Configuration
	
	private $includeGroups;
	public function setIncludeGroups($groupIds) {
		$this->includeGroups = array();
		if(!empty($groupIds)) {
			// checks and filters
			foreach($groupIds as $groupId) {
				if($groupId != 0) $this->includeGroups[$groupId] = $groupId;
			}
			if(!empty($this->includeGroups)) {
				$this->recording = false; // prevents recording until include group is visited				
			}
		}
	}
	private $excludeGroups;
	public function setExcludeGroups($groupIds) {
		$this->excludeGroups = array();
		if(!empty($groupIds)) {
			// checks and filters
			foreach($groupIds as $groupId) {
				if($groupId != 0) $this->excludeGroups[$groupId] = $groupId;
			}
		}		
	}
	
	// Implementation
	
	/**
	 * @return Array the group tree as an array(groupId=>parentGroupId)
	 */
	public function getGroupTree() {
		return $this->groupTree;
	}
	
	// TreeInDepthVisitor implementation
	
	public function actOnTreeNode($object, $depth, $numberOfChildren) {
		$groupId = $object->getId();		
		// on same level : include has priority on exclude
		if($this->excludeGroups[$groupId] && !$this->includeGroups[$groupId]) return false;
		// starts recording if include group is reached
		if(!$this->recording && $this->includeGroups[$groupId]) {
			$this->recording = true;
			$this->startedId = $groupId;
		}
		if($this->recording) {
			$this->groupTree[$groupId] = $object->getDbEntity()->getGroupParentId();
		}
		return true;
	}	
	public function actAfterTreeNode($object, $depth, $visitedAllChildren) {
		$groupId = $object->getId();
		//stops recording if start point is reached again
		if($this->recording && $this->startedId == $groupId) {
			$this->recording = false;
			$this->startedId = null;
		}
		return true;
	}
}

