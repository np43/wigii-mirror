<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/*
 * Created on 2 nov. 09
 * by LWR
 */

class ListContext extends ListFilter {

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

	const listView = "list";
	const listViewTemplate = "elementList.tpl.php";
	const calendarView = "calendar";
	const calendarViewTemplate = "elementCalendar.tpl.php";
	const blogView = "blog";
	const blogViewTemplate = "elementBlog.tpl.php";
	public function setCrtViewToList(){
		$this->crtView = ListContext::listView;
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
	public function getViewsTemplate(){
		return array(
			ListContext::listView=>ListContext::listViewTemplate,
			ListContext::calendarView=>ListContext::calendarViewTemplate,
			ListContext::blogView=>ListContext::blogViewTemplate
			);
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

	//return the list of possible views define in the config
	public function getAvailableTemplates($p, $module, $configS){
		$fa = $configS->m($p, $module);
		$returnValue = $this->getViewsTemplate();
		foreach($this->getViewsTemplate() as $view=>$template){
			if(empty($fa->{$view."View"})) unset($returnValue[$view]);
		}
		return $returnValue;
	}

	//takes either the list view, either the view with isDefaultView = 1
	public function getDefaultView($p, $module, $configS){
		$fa = $configS->m($p, $module);
		$views = $this->getViewsTemplate();
		$defaultView = null;
		foreach($this->getViewsTemplate() as $view=>$template){
			if(empty($fa->{$view."View"})) unset($views[$view]);
			if($fa->{$view."View"}["isDefaultView"]=="1") $defaultView = $view;
		}
		if(!$defaultView){
			reset($views);
			$defaultView = key($views);
		}
		return $defaultView;
	}

	private $groupPList;
	private $includeChildrenGroups;
	private $crtSelectedItem;
	private $multipleSelectedItems;
	private $multipleEnableElementState = 0;
	private $multipleEnableElementStateArr;
	private $multipleElementStateRest = 1023;
	private $multipleElementStateMedi = 1023;
	private $multipleElementStateMediPerm = 0;
	private $multipleElementStatePerm = 0;
	private $multipleElementStateArr;
	private $allHaveWriteRights;
	private $sortedBy;
	private $groupBy;
	private $ascendingSort;
	private $searchBar;


	public static function createInstance($p, $module, $configS)
	{
		$returnValue = new self();
		$returnValue->setCrtView($returnValue->getDefaultView($p, $module, $configS));
		return $returnValue;
	}

	//must be an FieldSelectorListForActivity
	private $fieldSelectorList;
	public function getFieldSelectorList()
	{
		return $this->fieldSelectorList;
	}
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
		$this->multipleElementStateRest = 1023;
		$this->multipleElementStateMedi = 1023;
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
			$this->multipleElementStateRest = 1023 & $elementState;
			$this->multipleElementStateMedi = 1023 & (1023-$elementEnableState) | $elementEnableState & 1023 & $elementState;
			$this->multipleElementStateMediPerm = $elementEnableState & 1023 & $elementState;
		}
		else {
			$this->multipleElementStateRest = $this->multipleElementStateRest & $elementState;
			$this->multipleElementStateMedi = $this->multipleElementStateMedi & (1023-$elementEnableState) | $elementEnableState & $this->multipleElementStateMedi & $elementState;
			$this->multipleElementStateMediPerm = $this->multipleElementStateMediPerm & (1023-$elementEnableState) | $elementEnableState & ($this->multipleElementStateMediPerm | $elementState);
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
				$val = $val & (1023-$mask) | $mask & $elementState;
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
			$this->multipleElementStateRest = 1023;
			$this->multipleElementStateMedi = 1023;
			$this->multipleElementStateMediPerm = 0;
			$this->multipleElementStatePerm = 0;
			$first = true;
			foreach($this->multipleEnableElementStateArr as $k => $elementEnableState) {
				$this->multipleEnableElementState = $this->multipleEnableElementState | $elementEnableState;

				if($first) {
					$this->multipleElementStateRest = 1023 & $this->multipleElementStateArr[$k];
					$this->multipleElementStateMedi = 1023 & (1023-$elementEnableState) | $elementEnableState & 1023 & $this->multipleElementStateArr[$k];
					$this->multipleElementStateMediPerm = $elementEnableState & 1023 & $this->multipleElementStateArr[$k];
					$first = false;
				}
				else {
					$this->multipleElementStateRest = $this->multipleElementStateRest & $this->multipleElementStateArr[$k];
					$this->multipleElementStateMedi = $this->multipleElementStateMedi & (1023-$elementEnableState) | $elementEnableState & $this->multipleElementStateMedi & $this->multipleElementStateArr[$k];
					$this->multipleElementStateMediPerm = $this->multipleElementStateMediPerm & (1023-$elementEnableState) | $elementEnableState & ($this->multipleElementStateMediPerm | $this->multipleElementStateArr[$k]);
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
	public function setGroupBy($key, $onlyShowDuplicates=false){
		//groupBy can be set to null, this case dosen't mean we want to have the defaultGroupyByKey
		//but means we don't want to do any groupBy
		if($key ==="reset" && method_exists($this->getFieldSelectorList(), "getDefaultGroupByKey")){
			$key = $this->getFieldSelectorList()->getDefaultGroupByKey();
		}

		$this->groupBy = $key;

		$this->addGroupBySortingKey();
		$this->addSortBySortingKey();
		$this->setGroupByOnlyDuplicates($onlyShowDuplicates);
	}
	protected function setGroupByOnlyDuplicates($var){
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
				$ascending = true;
				//exception for dates
				switch($groupByFS->getSubFieldName()){
					case "begDate":
					case "date":
					case "sys_date":
					case "sys_creationDate":
						$ascending = false;
						break;
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

//	public function getGroupByItemFieldName(){
//		if(!isset($this->groupByInformation)) return null;
//		$value = $this->getGroupBy();
//		if($value == null) return null;
//		$value = $this->groupByInformation["fieldSelectorList"]->getFieldSelectorFromKey($value);
//		if(isset($value)) return null;
//		return $value->getFieldName();
//	}
//	public function getGroupByItemSubFieldName(){
//		if(!isset($this->groupByInformation)) return null;
//		$value = $this->getGroupBy();
//		if($value == null) return null;
//		$value = $this->groupByInformation["fieldSelectorList"]->getFieldSelectorFromKey($value);
//		if(isset($value)) return null;
//		return $value->getSubFieldName();
//	}
//	public function getGroupByItemDataTypeName(){
//		if(!isset($this->groupByInformation)) return null;
//		$value = $this->getGroupBy();
//		if($value == null) return null;
//		return $this->groupByInformation[$value]["dataTypeName"];
//	}
	//this allows to store the actual value of the groupBy, to be able to continue
	//next pages without repating the current groupBy title
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

	//WARNING, the searchBar data is raw data from the post. no addslashes added
//	public function setSearchBarWithoutCalculationOffLogExp($post){
//		//reset the GroupByItmeCurrentValue
//		$this->setGroupByItemCurrentValue(null); //reinitialize the currentValue of groupBy, because this is used only when loading new page
//		if(ini_get("magic_quotes_gpc")){
//			$magicQuotes = true;
//			if(is_array($post)){
//			foreach ($post as $key=>$value){
//				if(is_array($value)){
//					foreach($value as $tempKey=>$tempValue){
//						if($magicQuotes && $post[$key][$tempKey]!=null) $post[$key][$tempKey] = stripslashes($post[$key][$tempKey]);
//					}
//				} else {
//					if($magicQuotes && $post[$key]!=null) $post[$key] = stripslashes($post[$key]);
//				}
//			}
//			}
//		}
//		$this->searchBar = $post; //serialize($post);
//	}
	public function setSearchBar($p, $wigiiExecutor, $post){

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

//		$this->setSearchBarWithoutCalculationOffLogExp($post);

		//reset the LogExp before adding them the search criterias
		$this->setFieldSelectorLogExp(null);
		try { $this->addLogExpOnTextSearch($p, $wigiiExecutor); }
		catch (StringTokenizerException $e) { throw new ListContextException($e->getMessage(), ListContextException::INVALID_TextFilter); }
		try { $this->addLogExpOnTextAdvancedSearch($p, $wigiiExecutor); }
		catch (StringTokenizerException $e) { throw new ListContextException($e->getMessage(), ListContextException::INVALID_TextAdvancedFilter); }
		$this->addLogExpOnSelectSearch($wigiiExecutor);
		$this->addLogExpOnGroupFilterSearch();
		//add the textGroupSearch text after
		try { $this->addLogExpOnTextGroupSearch($p, $wigiiExecutor); }
		catch (StringTokenizerException $e) { throw new ListContextException($e->getMessage(), ListContextException::INVALID_TextGroupFilter); }

		$this->setGroupBy($this->getGroupBy(),$this->isGroupByOnlyDuplicates());
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
					continue;
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
		$search = array (" ", "\t", "-", ".", "/", "\n", "\r", "\0", "\x0B");
		preg_match_all('/([\d|\s|\.|\/\-]{7,})/', $tempTextSearch, $matches);
		foreach ($matches[0] as $key => $value) {
			  $tempTab = str_replace($search, "", $value);
			  $tempTab = str_split($tempTab);
			  $tempTab = implode("%", $tempTab);
			  $tempTextSearch = str_replace($value, $tempTab, $tempTextSearch);
		}

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

	protected function addLogExpOnGroupFilterSearch(){
		$exp = LogExp::createAndExp();
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();

		//add groupList logExp;
		if($this->getGroupPList()==null || $this->getGroupPList()->isEmpty()){
			$this->groupLogExp = null;
			return;
		}


		$groupLogExp = array();
		foreach($this->getGroupPList()->getListIterator() as $groupP){
			$groupLogExp[] = $groupP->getId();
		}
		$groupLogExp = implode(",", $groupLogExp);
		if($this->doesGroupListIncludeChildren()){
			$groupLogExp = "INGR(id IN (".$groupLogExp."))";
		} else {
			$groupLogExp = "ING(id IN (".$groupLogExp."))";
		}
////		eput($temp);
//		$exp->addOperand($parser->createLogExpFromString($temp));

		//add groupFilter
		if($this->getSelectGroupFilterFields() != null){
			$groupFilterText = array();
			foreach($this->getSelectGroupFilterFields() as $selectGroupFilterField=>$groupLogExpList){
				$value = $this->getSelectSearchField($selectGroupFilterField);
				if($value == null) continue;
//				eput($value." ".$crtGroupLogExp);
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
			if($groupFilterText) $this->setGroupFilterLogExp($parser->createLogExpFromString($groupFilterText));
			else $this->setGroupFilterLogExp(null);
		}

		$this->groupLogExp = $parser->createLogExpFromString($groupLogExp);
		if($this->getGroupFilterLogExp()) $this->addGroupLogExp($this->getGroupFilterLogExp()->reduceNegation(true));
		if($this->getLogExpOnTextGroupSearch()) $this->addGroupLogExp($this->getLogExpOnTextGroupSearch()->reduceNegation(true));
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
		//setGroupPList need to have the groupFilterText or the TextGroupSearch to make a correct calculation
		$this->setGroupFilterLogExp($listContext->getGroupFilterLogExp());
		$this->setLogExpOnTextGroupSearch($listContext->getLogExpOnTextGroupSearch());
		$this->setGroupPList($listContext->getGroupPList(), $listContext->doesGroupListIncludeChildren());
//		//GroupLogExp
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
		$this->setGroupPList(null);
		//$this->setGroupLogExp(null);
		$this->setSearchBar($p, $wigiiExecutor, null);
	}
}


