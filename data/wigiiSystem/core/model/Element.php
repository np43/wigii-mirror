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
 * A wigii Element
 * Created by CWE on 31 mai 09
 * modified by LWR on 15 sept, to extend from Record
 * modified by CWE on 19.08.2014 to add link to parent in case of subelements.
 * modified by CWE on 23.09.2014 to add dynamic attributes
 */
class Element extends Record implements SysInformation
{
	// Object lifecycle
	
    public static function createInstance($module=MANDATORY_ARG, $fieldList = null, $wigiiBag = null, $array=null, $colPrefix='')
	{
		$e = new Element();
		$e->setModule($module);
		if(isset($fieldList)) $e->setFieldList($fieldList);
		if(isset($wigiiBag)) $e->setWigiiBag($wigiiBag);
		if(is_array($array))
		{
			$e->fillFromArray($array, $colPrefix);
		}
		return $e;
	}
	
	public function fillFromArray($array, $colPrefix='')
	{
		if(!is_array($array)) throw new ServiceException("array is not an array", ServiceException::INVALID_ARGUMENT);
		if(isset($array[$colPrefix."id"])) $this->setId($array[$colPrefix."id"]);
		if(isset($array[$colPrefix."id_element_parent"])) $this->setElementParentId($array[$colPrefix."id_element_parent"]);
		if(isset($array[$colPrefix."linkName"])) $this->setLinkName($array[$colPrefix."linkName"]);
		if(isset($array[$colPrefix."peerId"])) $this->setPeerId($array[$colPrefix."peerId"]);
		if(isset($array[$colPrefix."sys_creationDate"])) $this->setSys_creationDate($array[$colPrefix."sys_creationDate"]);
		if(isset($array[$colPrefix."sys_creationUser"])) $this->setSys_creationUser($array[$colPrefix."sys_creationUser"]);
		if(isset($array[$colPrefix."sys_creationUsername"])) $this->setSys_creationUsername($array[$colPrefix."sys_creationUsername"]);
		if(isset($array[$colPrefix."sys_date"])) $this->setSys_date($array[$colPrefix."sys_date"]);
		if(isset($array[$colPrefix."sys_user"])) $this->setSys_user($array[$colPrefix."sys_user"]);
		if(isset($array[$colPrefix."sys_username"])) $this->setSys_username($array[$colPrefix."sys_username"]);
		if(isset($array[$colPrefix."version"])) $this->setVersion($array[$colPrefix."version"]);
		if(isset($array[$colPrefix."sys_lockMicroTime"])) $this->setSys_lockMicroTime($array[$colPrefix."sys_lockMicroTime"]);
		if(isset($array[$colPrefix."sys_lockId"])) $this->setSys_lockId($array[$colPrefix."sys_lockId"]);
		if(isset($array[$colPrefix."state_locked"])) $this->setState_locked($array[$colPrefix."state_locked"]);
		if(isset($array[$colPrefix."state_important1"])) $this->setState_important1($array[$colPrefix."state_important1"]);
		if(isset($array[$colPrefix."state_important2"])) $this->setState_important2($array[$colPrefix."state_important2"]);
		if(isset($array[$colPrefix."state_hidden"])) $this->setState_hidden($array[$colPrefix."state_hidden"]);
		if(isset($array[$colPrefix."state_archived"])) $this->setState_archived($array[$colPrefix."state_archived"]);
		if(isset($array[$colPrefix."state_deprecated"])) $this->setState_deprecated($array[$colPrefix."state_deprecated"]);
		if(isset($array[$colPrefix."state_finalized"])) $this->setState_finalized($array[$colPrefix."state_finalized"]);
		if(isset($array[$colPrefix."state_approved"])) $this->setState_approved($array[$colPrefix."state_approved"]);
		if(isset($array[$colPrefix."state_dismissed"])) $this->setState_dismissed($array[$colPrefix."state_dismissed"]);
		if(isset($array[$colPrefix."state_blocked"])) $this->setState_blocked($array[$colPrefix."state_blocked"]);
		if(isset($array[$colPrefix."state_lockedInfo"])) $this->setState_lockedInfo($array[$colPrefix."state_lockedInfo"]);
		if(isset($array[$colPrefix."state_important1Info"])) $this->setState_important1Info($array[$colPrefix."state_important1Info"]);
		if(isset($array[$colPrefix."state_important2Info"])) $this->setState_important2Info($array[$colPrefix."state_important2Info"]);
		if(isset($array[$colPrefix."state_hiddenInfo"])) $this->setState_hiddenInfo($array[$colPrefix."state_hiddenInfo"]);
		if(isset($array[$colPrefix."state_archivedInfo"])) $this->setState_archivedInfo($array[$colPrefix."state_archivedInfo"]);
		if(isset($array[$colPrefix."state_deprecatedInfo"])) $this->setState_deprecatedInfo($array[$colPrefix."state_deprecatedInfo"]);
		if(isset($array[$colPrefix."state_finalizedInfo"])) $this->setState_finalizedInfo($array[$colPrefix."state_finalizedInfo"]);
		if(isset($array[$colPrefix."state_approvedInfo"])) $this->setState_approvedInfo($array[$colPrefix."state_approvedInfo"]);
		if(isset($array[$colPrefix."state_dismissedInfo"])) $this->setState_dismissedInfo($array[$colPrefix."state_dismissedInfo"]);
		if(isset($array[$colPrefix."state_blockedInfo"])) $this->setState_blockedInfo($array[$colPrefix."state_blockedInfo"]);
		// in memory fields (not persisted in db)
		if(isset($array[$colPrefix."element_parent"])) $this->setParentElement($array[$colPrefix."element_parent"]);
	}
	
	// Dependency injection
	
	private $elementLazyLoader;
	/**
	 * @param ElementLazyLoader $elementLazyLoader
	 */
	public function setElementLazyLoader($elementLazyLoader) {
		$this->elementLazyLoader = $elementLazyLoader;
	}
	/**
	 * @return ElementLazyLoader
	 */
	protected function getElementLazyLoader() {
		if(!isset($this->elementLazyLoader)) {
			$this->elementLazyLoader = ServiceProvider::getElementLazyLoader();
		}
		return $this->elementLazyLoader;
	}
	
	// Element content 
	
	private $module;
	public function getModule() { return $this->module; }
	public function setModule($var) { $this->module = $var; }

	public function getActivity(){ return null; }

	private $id_element_parent;
	public function getElementParentId() {return $this->id_element_parent;}
	public function setElementParentId($elementParentId) {
		$oldVal = $this->id_element_parent;
		$this->id_element_parent = $elementParentId;
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('id_element_parent', $oldValue, $this->id_element_parent);
	}
	
	private $linkName;
	public function getLinkName() {return $this->linkName;}
	public function setLinkName($linkName) {
		$oldValue = $this->linkName;
		$this->linkName = $linkName;
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('linkName', $oldValue, $this->linkName);
	}
	
	private $peerId;
	public function getPeerId() {return $this->peerId;}
	public function setPeerId($peerId) {
		$oldValue = $this->peerId;
		$this->peerId = $peerId;
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('peerId', $oldValue, $this->peerId);
	}  
	
	private $sys_creationDate;
	public function getSys_creationDate(){return $this->sys_creationDate; }
	public function setSys_creationDate($var){
		$oldValue = $this->sys_creationDate;
		$this->sys_creationDate = $this->formatValue($var);
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('sys_creationDate', $oldValue, $this->sys_creationDate);
	}
	
	private $sys_creationUser; // real user id
	public function getSys_creationUser(){ return $this->sys_creationUser; }
	public function setSys_creationUser($var){
		$oldValue = $this->sys_creationUser;
		$this->sys_creationUser = $var; 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('sys_creationUser', $oldValue, $this->sys_creationUser);
	}
	
	private $sys_creationUsername; // real username
	public function getSys_creationUsername(){ return $this->sys_creationUsername; }
	public function setSys_creationUsername($var){
		$oldValue = $this->sys_creationUsername;
		$this->sys_creationUsername = $var; 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('sys_creationUsername', $oldValue, $this->sys_creationUsername);
	}
	
	private $sys_date;
	public function getSys_date(){return $this->sys_date; }
	public function setSys_date($var){
		$oldValue = $this->sys_date;
		$this->sys_date = $this->formatValue($var); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('sys_date', $oldValue, $this->sys_date);
	}
	
	private $sys_user; // real user id
	public function getSys_user(){ return $this->sys_user; }
	public function setSys_user($var){
		$oldValue = $this->sys_user;
		$this->sys_user = $var;
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('sys_user', $oldValue, $this->sys_user);
	}
	
	private $sys_username; // real username
	public function getSys_username(){ return $this->sys_username; }
	public function setSys_username($var){
		$oldValue = $this->sys_username;
		$this->sys_username = $var; 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('sys_username', $oldValue, $this->sys_username);
	}
	
	private $version;
	public function getVersion(){return $this->version; }
	public function setVersion($var){
		$oldValue = $this->version;
		$this->version = $this->formatValue($var); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('version', $oldValue, $this->version);
	}
	
	private $sys_lockMicroTime;
	public function getSys_lockMicroTime(){return $this->sys_lockMicroTime; }
	public function setSys_lockMicroTime($var){
		$oldValue = $this->sys_lockMicroTime;
		$this->sys_lockMicroTime = $this->formatValue($var); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('sys_lockMicroTime', $oldValue, $this->sys_lockMicroTime);
	}

	private $sys_lockId;
	public function getSys_lockId(){return $this->sys_lockId; }
	public function setSys_lockId($var){
		$oldValue = $this->sys_lockId;
		$this->sys_lockId = $this->formatValue($var); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('sys_lockId', $oldValue, $this->sys_lockId);
	}
	
	private $state_locked;
	public function isState_locked() { return $this->state_locked; }
	public function setState_locked($islocked) { 
		$oldValue = $this->state_locked;
		$this->state_locked = $this->formatBoolean($islocked); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_locked', $oldValue, $this->state_locked);
	}
	
	private $state_important1;
	public function isState_important1() { return $this->state_important1; }
	public function setState_important1($isImportant1) {
		$oldValue = $this->state_important1;
		$this->state_important1 = $this->formatBoolean($isImportant1); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_important1', $oldValue, $this->state_important1);
	}
	
	private $state_important2;
	public function isState_important2() { return $this->state_important2; }
	public function setState_important2($isImportant2) { 
		$oldValue = $this->state_important2;
		$this->state_important2 = $this->formatBoolean($isImportant2); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_important2', $oldValue, $this->state_important2);
	}
	
	private $state_hidden;
	public function isState_hidden() { return $this->state_hidden; }
	public function setState_hidden($isHidden) { 
		$oldValue = $this->state_hidden;
		$this->state_hidden = $this->formatBoolean($isHidden); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_hidden', $oldValue, $this->state_hidden);
	}
	
	private $state_archived;
	public function isState_archived() { return $this->state_archived; }
	public function setState_archived($isarchived) { 
		$oldValue = $this->state_archived;
		$this->state_archived = $this->formatBoolean($isarchived); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_archived', $oldValue, $this->state_archived);
	}
	
	private $state_deprecated;
	public function isState_deprecated() { return $this->state_deprecated; }
	public function setState_deprecated($isdeprecated) { 
		$oldValue = $this->state_deprecated;
		$this->state_deprecated = $this->formatBoolean($isdeprecated); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_deprecated', $oldValue, $this->state_deprecated);
	}
	
	private $state_finalized;
	public function isState_finalized() { return $this->state_finalized; }
	public function setState_finalized($isfinalized) { 
		$oldValue = $this->state_finalized;
		$this->state_finalized = $this->formatBoolean($isfinalized); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_finalized', $oldValue, $this->state_finalized);
	}
	
	private $state_approved;
	public function isState_approved() { return $this->state_approved; }
	public function setState_approved($isapproved) { 
		$oldValue = $this->state_approved;
		$this->state_approved = $this->formatBoolean($isapproved); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_approved', $oldValue, $this->state_approved);
	}
	
	private $state_dismissed;
	public function isState_dismissed() { return $this->state_dismissed; }
	public function setState_dismissed($isdismissed) { 
		$oldValue = $this->state_dismissed;
		$this->state_dismissed = $this->formatBoolean($isdismissed); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_dismissed', $oldValue, $this->state_dismissed);
	}
	
	private $state_blocked;
	public function isState_blocked() { return $this->state_blocked; }
	public function setState_blocked($isblocked) { 
		$oldValue = $this->state_blocked;
		$this->state_blocked = $this->formatBoolean($isblocked); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_blocked', $oldValue, $this->state_blocked);
	}
	
	private $state_lockedInfo;
	public function getArrayState_lockedInfo() { return str2array($this->state_lockedInfo); }
	public function getState_lockedInfo() { return $this->state_lockedInfo; }
	public function setState_lockedInfo($lockedInfo) { 
		$oldValue = $this->state_lockedInfo;
		$this->state_lockedInfo = $this->formatValue($lockedInfo); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_lockedInfo', $oldValue, $this->state_lockedInfo);
	}
	public function setArrayState_lockedInfo($lockedInfo) { 
		$oldValue = $this->state_lockedInfo;
		$this->state_lockedInfo = array2str($this->formatValue($lockedInfo)); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_lockedInfo', $oldValue, $this->state_lockedInfo);
	}
	
	private $state_important1Info;
	public function getArrayState_important1Info() { return str2array($this->state_important1Info); }
	public function getState_important1Info() { return $this->state_important1Info; }
	public function setState_important1Info($important1Info) { 
		$oldValue = $this->state_important1Info;
		$this->state_important1Info = $this->formatValue($important1Info); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_important1Info', $oldValue, $this->state_important1Info);
	}
	public function setArrayState_important1Info($important1Info) { 
		$oldValue = $this->state_important1Info;
		$this->state_important1Info = array2str($this->formatValue($important1Info)); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_important1Info', $oldValue, $this->state_important1Info);
	}
	
	private $state_important2Info;
	public function getArrayState_important2Info() { return str2array($this->state_important2Info); }
	public function getState_important2Info() { return $this->state_important2Info; }
	public function setState_important2Info($important2Info) { 
		$oldValue = $this->state_important2Info;
		$this->state_important2Info = $this->formatValue($important2Info); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_important2Info', $oldValue, $this->state_important2Info);
	}
	public function setArrayState_important2Info($important2Info) { 
		$oldValue = $this->state_important2Info;
		$this->state_important2Info = array2str($this->formatValue($important2Info)); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_important2Info', $oldValue, $this->state_important2Info);
	}
	
	private $state_hiddenInfo;
	public function getArrayState_hiddenInfo() { return str2array($this->state_hiddenInfo); }
	public function getState_hiddenInfo() { return $this->state_hiddenInfo; }
	public function setState_hiddenInfo($hiddenInfo) { 
		$oldValue = $this->state_hiddenInfo;
		$this->state_hiddenInfo = $this->formatValue($hiddenInfo); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_hiddenInfo', $oldValue, $this->state_hiddenInfo);
	}
	public function setArrayState_hiddenInfo($hiddenInfo) {
		$oldValue = $this->state_hiddenInfo;
		$this->state_hiddenInfo = array2str($this->formatValue($hiddenInfo)); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_hiddenInfo', $oldValue, $this->state_hiddenInfo);
	}
	
	private $state_archivedInfo;
	public function getArrayState_archivedInfo() { return str2array($this->state_archivedInfo); }
	public function getState_archivedInfo() { return $this->state_archivedInfo; }
	public function setState_archivedInfo($archivedInfo) { 
		$oldValue = $this->state_archivedInfo;
		$this->state_archivedInfo = $this->formatValue($archivedInfo); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_archivedInfo', $oldValue, $this->state_archivedInfo);
	}
	public function setArrayState_archivedInfo($archivedInfo) { 
		$oldValue = $this->state_archivedInfo;
		$this->state_archivedInfo = array2str($this->formatValue($archivedInfo)); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_archivedInfo', $oldValue, $this->state_archivedInfo);
	}
	
	private $state_deprecatedInfo;
	public function getArrayState_deprecatedInfo() { return str2array($this->state_deprecatedInfo); }
	public function getState_deprecatedInfo() { return $this->state_deprecatedInfo; }
	public function setState_deprecatedInfo($deprecatedInfo) { 
		$oldValue = $this->state_deprecatedInfo;
		$this->state_deprecatedInfo = $this->formatValue($deprecatedInfo); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_deprecatedInfo', $oldValue, $this->state_deprecatedInfo);
	}
	public function setArrayState_deprecatedInfo($deprecatedInfo) { 
		$oldValue = $this->state_deprecatedInfo;
		$this->state_deprecatedInfo = array2str($this->formatValue($deprecatedInfo)); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_deprecatedInfo', $oldValue, $this->state_deprecatedInfo);
	}

	private $state_finalizedInfo;
	public function getArrayState_finalizedInfo() { return str2array($this->state_finalizedInfo); }
	public function getState_finalizedInfo() { return $this->state_finalizedInfo; }
	public function setState_finalizedInfo($finalizedInfo) { 
		$oldValue = $this->state_finalizedInfo;
		$this->state_finalizedInfo = $this->formatValue($finalizedInfo); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_finalizedInfo', $oldValue, $this->state_finalizedInfo);
	}
	public function setArrayState_finalizedInfo($finalizedInfo) { 
		$oldValue = $this->state_finalizedInfo;
		$this->state_finalizedInfo = array2str($this->formatValue($finalizedInfo)); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_finalizedInfo', $oldValue, $this->state_finalizedInfo);
	}
	
	private $state_approvedInfo;
	public function getArrayState_approvedInfo() { return str2array($this->state_approvedInfo); }
	public function getState_approvedInfo() { return $this->state_approvedInfo; }
	public function setState_approvedInfo($approvedInfo) { 
		$oldValue = $this->state_approvedInfo;
		$this->state_approvedInfo = $this->formatValue($approvedInfo); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_approvedInfo', $oldValue, $this->state_approvedInfo);
	}
	public function setArrayState_approvedInfo($approvedInfo) { 
		$oldValue = $this->state_approvedInfo;
		$this->state_approvedInfo = array2str($this->formatValue($approvedInfo)); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_approvedInfo', $oldValue, $this->state_approvedInfo);
	}
	
	private $state_dismissedInfo;
	public function getArrayState_dismissedInfo() { return str2array($this->state_dismissedInfo); }
	public function getState_dismissedInfo() { return $this->state_dismissedInfo; }
	public function setState_dismissedInfo($dismissedInfo) { 
		$oldValue = $this->state_dismissedInfo;
		$this->state_dismissedInfo = $this->formatValue($dismissedInfo); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_dismissedInfo', $oldValue, $this->state_dismissedInfo);
	}
	public function setArrayState_dismissedInfo($dismissedInfo) { 
		$oldValue = $this->state_dismissedInfo;
		$this->state_dismissedInfo = array2str($this->formatValue($dismissedInfo)); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_dismissedInfo', $oldValue, $this->state_dismissedInfo);
	}
	
	private $state_blockedInfo;
	public function getArrayState_blockedInfo() { return str2array($this->state_blockedInfo); }
	public function getState_blockedInfo() { return $this->state_blockedInfo; }
	public function setState_blockedInfo($blockedInfo) { 
		$oldValue = $this->state_blockedInfo;
		$this->state_blockedInfo = $this->formatValue($blockedInfo); 
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_blockedInfo', $oldValue, $this->state_blockedInfo);
	}
	public function setArrayState_blockedInfo($blockedInfo) { 
		$oldValue = $this->state_blockedInfo;
		$this->state_blockedInfo = array2str($this->formatValue($blockedInfo));
		if($this->hasElementAttributeListeners) $this->notifyElementAttributeChange('state_blockedInfo', $oldValue, $this->state_blockedInfo);
	}

	/**
	 * Returns a binary encoding of the state
	 * 2^0: state_locked,
	 * 2^1: state_blocked,
	 * 2^2: state_important1,
	 * 2^3: state_important2,
	 * 2^4: state_finalized,
	 * 2^5: state_approved,
	 * 2^6: state_dismissed,
	 * 2^7: state_archived,
	 * 2^8: state_deprecated,
	 * 2^9: state_hidden
	 * @param string $state optional state name to get only one state and not the compilation of all states.
	 * state name is one of 'locked', 'blocked', 'important1', 'important2', 'finalized', 
	 * 'approved', 'dismissed', 'archived', 'deprecated', 'hidden'.
	 */
	public function getStateAsInt($state=null) {
		$returnValue = 0;
		if((!isset($state) || isset($state) && $state == 'locked') && $this->isState_locked()) $returnValue += 1;
		if((!isset($state) || isset($state) && $state == 'blocked') && $this->isState_blocked()) $returnValue += 2;
		if((!isset($state) || isset($state) && $state == 'important1') && $this->isState_important1()) $returnValue += 4;
		if((!isset($state) || isset($state) && $state == 'important2') && $this->isState_important2()) $returnValue += 8;
		if((!isset($state) || isset($state) && $state == 'finalized') && $this->isState_finalized()) $returnValue += 16;
		if((!isset($state) || isset($state) && $state == 'approved') && $this->isState_approved()) $returnValue += 32;
		if((!isset($state) || isset($state) && $state == 'dismissed') && $this->isState_dismissed()) $returnValue += 64;
		if((!isset($state) || isset($state) && $state == 'archived') && $this->isState_archived()) $returnValue += 128;
		if((!isset($state) || isset($state) && $state == 'deprecated') && $this->isState_deprecated()) $returnValue += 256;
		if((!isset($state) || isset($state) && $state == 'hidden') && $this->isState_hidden()) $returnValue += 512;
		return $returnValue;
	}
	/**
	 * Returns a binary representation of a state name
	 * @param string $state state name, one of : 'locked', 'blocked', 'important1', 'important2', 'finalized', 
	 * 'approved', 'dismissed', 'archived', 'deprecated', 'hidden'.
	 */
	public static function stateName2int($state) {
		if($val) {
			switch($state) {
				case 'locked': return 1;
				case 'blocked': return 2;
				case 'important1': return 4;
				case 'important2': return 8;
				case 'finalized': return 16;
				case 'approved': return 32;
				case 'dismissed': return 64;
				case 'archived': return 128;
				case 'deprecated': return 256;
				case 'hidden': return 512;
				default: return 0;
			}
		} else return 0;
	}
	
	/**
	 * Updates the state value
	 * @param String $state the state name. One of: 'locked', 'blocked', 'important1', 'important2', 'finalized', 
	 * 'approved', 'dismissed', 'archived', 'deprecated', 'hidden'.
	 * @param Boolean $checked true if state is checked, false otherwise.
	 * @param Array $arrayInfo optional array of information to be stored into the attached stateInfo attribute.
	 */
	public function setState($state, $checked, $arrayInfo=null) {
		switch($state) {
			case 'locked': 
				$this->setState_locked($checked);
				if(isset($arrayInfo)) $this->setArrayState_lockedInfo($arrayInfo);
				break;
			case 'blocked':
				$this->setState_blocked($checked);
				if(isset($arrayInfo)) $this->setArrayState_blockedInfo($arrayInfo);
				break;
			case 'important1':
				$this->setState_important1($checked);
				if(isset($arrayInfo)) $this->setArrayState_important1Info($arrayInfo);
				break;
			case 'important2':
				$this->setState_important2($checked);
				if(isset($arrayInfo)) $this->setArrayState_important2Info($arrayInfo);
				break;
			case 'finalized':
				$this->setState_finalized($checked);
				if(isset($arrayInfo)) $this->setArrayState_finalizedInfo($arrayInfo);
				break;
			case 'approved':
				$this->setState_approved($checked);
				if(isset($arrayInfo)) $this->setArrayState_approvedInfo($arrayInfo);
				break;
			case 'dismissed':
				$this->setState_dismissed($checked);
				if(isset($arrayInfo)) $this->setArrayState_dismissedInfo($arrayInfo);
				break;
			case 'archived':
				$this->setState_archived($checked);
				if(isset($arrayInfo)) $this->setArrayState_archivedInfo($arrayInfo);
				break;
			case 'deprecated':
				$this->setState_deprecated($checked);
				if(isset($arrayInfo)) $this->setArrayState_deprecatedInfo($arrayInfo);
				break;
			case 'hidden':
				$this->setState_hidden($checked);
				if(isset($arrayInfo)) $this->setArrayState_hiddenInfo($arrayInfo);
				break;
			default: throw new RecordException("state '$state' is not a valid element state attribute", RecordException::INVALID_ARGUMENT);
		}
	}
	
	public function getAttribute($fieldSelector){
		if(!$fieldSelector->isElementAttributeSelector()) throw new ServiceException("fieldSelector should be an elementAttributeSelector", ServiceException::INVALID_ARGUMENT);
		switch($fieldSelector->getSubFieldName()){
			case "sys_date": return $this->getSys_date(); break;
			case "sys_user": return $this->getSys_user(); break;
			case "sys_username": return $this->getSys_username(); break;
			case "sys_creationDate": return $this->getSys_creationDate(); break;
			case "sys_creationUser": return $this->getSys_creationUser(); break;
			case "sys_creationUsername": return $this->getSys_creationUsername(); break;
			case "version": return $this->getVersion(); break;
			case "modulename": return $this->getModule()->getModuleName(); break;
			case "id": return $this->getId(); break;
			case "id_element_parent": return $this->getElementParentId(); break;
			case "linkName": return $this->getLinkName(); break;
			case "peerId": return $this->getPeerId(); break;
			case "state_locked": return $this->isState_locked(); break;
			case "state_important1": return $this->isState_important1(); break;
			case "state_important2": return $this->isState_important2(); break;
			case "state_hidden": return $this->isState_hidden(); break;
			case "state_archived": return $this->isState_archived(); break;
			case "state_deprecated": return $this->isState_deprecated(); break;
			case "state_finalized": return $this->isState_finalized(); break;
			case "state_approved": return $this->isState_approved(); break;
			case "state_dismissed": return $this->isState_dismissed(); break;
			case "state_blocked": return $this->isState_blocked(); break;
			case "state_lockedInfo": return $this->getState_lockedInfo(); break;
			case "state_important1Info": return $this->getState_important1Info(); break;
			case "state_important2Info": return $this->getState_important2Info(); break;
			case "state_hiddenInfo": return $this->getState_hiddenInfo(); break;
			case "state_archivedInfo": return $this->getState_archivedInfo(); break;
			case "state_deprecatedInfo": return $this->getState_deprecatedInfo(); break;
			case "state_finalizedInfo": return $this->getState_finalizedInfo(); break;
			case "state_approvedInfo": return $this->getState_approvedInfo(); break;
			case "state_dismissedInfo": return $this->getState_dismissedInfo(); break;
			case "state_blockedInfo": return $this->getState_blockedInfo(); break;
			case "sys_lockMicroTime": return $this->getSys_lockMicroTime(); break;
			case "sys_lockId": return $this->getSys_lockId(); break;
			// in memory fields (not persisted in db)
			case "this":
			case "value":
			case "": return $this; break;
			case "element_parent": return $this->getParentElement(); break;
			case "element_pathFromRoot": return $this->getSubElementPathFromRoot(); break;
			case "id_element_root": return $this->getElementRootId(); break;
			case "element_info": return $this->getElementInfo(); break;
			// not a standard attribute name (can be a dynamic attribute or not supported)
			default: 
				// returns dynamic attribute value if exists
				$da = $this->getDynamicAttribute($fieldSelector->getSubFieldName());
				if(isset($da)) return $da->getValue();
				// else throws exception
				else throw new ServiceException("ERROR, element attribute: ".$fieldSelector->getSubFieldName()." does not exists", ServiceException::INVALID_ARGUMENT);
		}
	}
	public function setAttribute($value, $fieldSelector){
		if(!$fieldSelector->isElementAttributeSelector()) throw new ServiceException("fieldSelector should be an elementAttributeSelector", ServiceException::INVALID_ARGUMENT);
		switch($fieldSelector->getSubFieldName()){
			case "sys_date": return $this->setSys_date($value); break;
			case "sys_user": return $this->setSys_user($value); break;
			case "sys_username": return $this->setSys_username($value); break;
			case "sys_creationDate": return $this->setSys_creationDate($value); break;
			case "sys_creationUser": return $this->setSys_creationUser($value); break;
			case "sys_creationUsername": return $this->setSys_creationUsername($value); break;
			case "version": return $this->setVersion($value); break;
			//cannot set the attribute modulename
			case "modulename": throw new ServiceException("ERROR, element attribute: ".$fieldSelector->getSubFieldName()." cannot be set.", ServiceException::UNSUPPORTED_OPERATION);
			case "id": return $this->setId($value); break;
			case "id_element_parent": return $this->setElementParentId($value); break;
			case "linkName": return $this->setLinkName($value); break;
			case "peerId": return $this->setPeerId($value); break;
			case "state_locked": return $this->setState_locked($value); break;
			case "state_important1": return $this->setState_important1($value); break;
			case "state_important2": return $this->setState_important2($value); break;
			case "state_hidden": return $this->setState_hidden($value); break;
			case "state_archived": return $this->setState_archived($value); break;
			case "state_deprecated": return $this->setState_deprecated($value); break;
			case "state_finalized": return $this->setState_finalized($value); break;
			case "state_approved": return $this->setState_approved($value); break;
			case "state_dismissed": return $this->setState_dismissed($value); break;
			case "state_blocked": return $this->setState_blocked($value); break;
			case "state_lockedInfo": return $this->setState_lockedInfo($value); break;
			case "state_important1Info": return $this->setState_important1Info($value); break;
			case "state_important2Info": return $this->setState_important2Info($value); break;
			case "state_hiddenInfo": return $this->setState_hiddenInfo($value); break;
			case "state_archivedInfo": return $this->setState_archivedInfo($value); break;
			case "state_deprecatedInfo": return $this->setState_deprecatedInfo($value); break;
			case "state_finalizedInfo": return $this->setState_finalizedInfo($value); break;
			case "state_approvedInfo": return $this->setState_approvedInfo($value); break;
			case "state_dismissedInfo": return $this->setState_dismissedInfo($value); break;
			case "state_blockedInfo": return $this->setState_blockedInfo($value); break;			
			case "sys_lockMicroTime": return $this->setSys_lockMicroTime($value); break;
			case "sys_lockId": return $this->setSys_lockId($value); break;
			// in memory fields (not persisted in db)
			case "element_parent": return $this->setParentElement($value); break;
			case "element_pathFromRoot": return $this->setSubElementPathFromRoot($value); break;
			case "id_element_root": throw new ServiceException("ERROR, cannot set id_element_root alone. Set element_pathFromRoot instead.", ServiceException::UNSUPPORTED_OPERATION);
			case "element_info": return $this->setElementInfo($value); break;
			// not a standard attribute name (can be a dynamic attribute or not supported)
			default: 
				// creates dynamic attribute if value instanceof ElementDynamicAttribute
				if($value instanceof ElementDynamicAttribute) {
					return $this->setDynamicAttribute($fieldSelector->getSubFieldName(), $value);
				}
				// else sets value of dynamic attribute if defined
				$da = $this->getDynamicAttribute($fieldSelector->getSubFieldName());
				if(isset($da)) {
					return $da->setValue($value);
				}
				// else throws exception
				else throw new ServiceException("ERROR, element attribute: ".$fieldSelector->getSubFieldName()." does not exists", ServiceException::INVALID_ARGUMENT);
		}
	}

	// Subitems
	
	/**
	 * Returns true if this element is a sub element	 
	 */
	public function isSubElement() {return !is_null($this->getElementParentId());}	
	
	private $parentElement;
	/**
	 * Returns the linked parent element in case of a sub element
	 * @return Element or null if link has not been initialized
	 */
	public function getParentElement() {
		if(!isset($this->parentElement) && $this->isSubElement()) {
			$this->getElementLazyLoader()->fetchParentElement($this);
		}
		return $this->parentElement;
	}
	/**
	 * Links an element to this sub element as the parent element
	 * @param Element $element the element to link
	 * @throws RecordException if element->id is not equal to this->id_element_parent
	 */
	public function setParentElement($element) {
		if(isset($element) && $element->getId() != $this->getElementParentId()) throw new RecordException("parent element id does not match", RecordException::INVALID_ARGUMENT);
		$this->parentElement = $element; 
	}
	
	private $subElementPathFromRoot;
	/**
	 * Returns the sub element path from root element
	 * @return LinkSelectorList or null if element is not a sub element
	 */
	public function getSubElementPathFromRoot() {
		if(!isset($this->subElementPathFromRoot) && $this->isSubElement()) {
			$this->getElementLazyLoader()->fetchSubElementPathFromRoot($this);
		}
		return $this->subElementPathFromRoot;
	}
	/**
	 * Sets the path from root element to this sub element
	 * @param LinkSelectorList $pathFromRoot
	 */
	public function setSubElementPathFromRoot($pathFromRoot) {
		if(isset($pathFromRoot) && $pathFromRoot->getLastLinkSelector()->getOwnerElementId() != $this->getElementParentId()) throw new RecordException("parent element id does not match", RecordException::INVALID_ARGUMENT);
		$this->subElementPathFromRoot=$pathFromRoot;
	}
	/**
	 * If sub element, then returns the root element ID else returns current ID.
	 */
	public function getElementRootId() {
		if($this->isSubElement()) return $this->getSubElementPathFromRoot()->getFirstLinkSelector()->getOwnerElementId();
		else return $this->getId();
	}
	
	// Soft links
	
	private $linkedElements;
	/**
	 * Returns the linked element whose id is defined in the given field.
	 * @param String|FieldSelector $fieldName A String defining the field name for which to get the linked element id,
	 * or can be directly a FieldSelector that can be used to select the id value in the element.
	 */
	public function getLinkedElement($fieldName) {
		if($fieldName instanceof FieldSelector) $fieldName = $fieldName->getFieldName();
		if(empty($fieldName)) throw new RecordException("fieldName cannot be null", RecordException::INVALID_ARGUMENT);
		if(!isset($this->linkedElements)) $this->linkedElements = array();
		if(is_null($this->linkedElements[$fieldName])) {
			$this->getElementLazyLoader()->fetchLinkedElement($fieldName, $this);
		}
		return $this->linkedElements[$fieldName];
	}
	/**
	 * Links an element under this field name
	 * @param String|FieldSelector $fieldName the field name under which to store the linked element
	 */
	public function setLinkedElement($fieldName, $element) {
		if($fieldName instanceof FieldSelector) $fieldName = $fieldName->getFieldName();
		if(empty($fieldName)) throw new RecordException("fieldName cannot be null", RecordException::INVALID_ARGUMENT);
		if(!isset($this->linkedElements)) $this->linkedElements = array();
		$this->linkedElements[$fieldName] = $element;
	}
	
	// Dynamic attributes
	
	private $dynamicAttributes;
	private $fixedDynamicAttributes;
	
	/**
	 * Returns the ElementDynamicAttribute stored under the given name or null if not defined
	 * @param String $name the name of the dynamic attribute
	 * @return ElementDynamicAttribute or null if not defined.
	 */
	public function getDynamicAttribute($name) {
		if(empty($name)) throw new RecordException('name cannot be empty', RecordException::INVALID_ARGUMENT);
		if(isset($this->dynamicAttributes)) return $this->dynamicAttributes[$name];
		else return null;
	}
	
	/**
	 * Gets the value of the ElementDynamicAttribute for the given name.
	 * @param String $name the name of the dynamic attribute
	 * @return mixed returns the dynamic attribute value or null if not defined.
	 */
	public function getDynamicAttributeValue($name) {
		$da = $this->getDynamicAttribute($name);
		if(isset($da)) return $da->getValue();
		else return null;
	}
	
	/**
	 * Stores an ElementDynamicAttribute under the given name.
	 * @param String $name the name of the dynamic attribute. Previous attribute is replaced.
	 * @param ElementDynamicAttribute $attr the element dynamic attribute to store.
	 * @param boolean $replaceable optional boolean. If true, then replacing this dynamic attribute instance
	 * with another one under the same name is allowed. Else, this dynamic attribute instance cannot be replaced
	 * by another one under the same name during the whole Element lifecycle. By default, dynamic attributes are replaceable. 
	 */
	public function setDynamicAttribute($name, $attr, $replaceable=true) {
		if(empty($name)) throw new RecordException('name cannot be empty', RecordException::INVALID_ARGUMENT);
		if(!($attr instanceof ElementDynamicAttribute)) throw new RecordException('attribute should be an instance of an ElementDynamicAttribute', RecordException::INVALID_ARGUMENT);
		if(!isset($this->dynamicAttributes)) $this->dynamicAttributes = array();	
		// checks if this name has already a fixed attribute
		if(isset($this->fixedDynamicAttributes) && isset($this->fixedDynamicAttributes[$name])) {
			throw new RecordException("A dynamic attribute has already been stored under the name '$name' and cannot be replaced.", RecordException::FORBIDDEN);
		}	
		$this->dynamicAttributes[$name] = $attr;
		$attr->setElement($this);
		// defines this attribute as fixed if replaceable = false
		if(!$replaceable) {
			if(!isset($this->fixedDynamicAttributes)) $this->fixedDynamicAttributes = array();
			$this->fixedDynamicAttributes[$name] = $name;
		}
		// activates attribute listener
		if($attr->listenToElementAttributeChange()) {
			if(!isset($this->elementAttributeListeners)) $this->elementAttributeListeners = array();
			$this->elementAttributeListeners[$name] = $attr;
			$this->hasElementAttributeListeners = true;
		}
		elseif($this->hasElementAttributeListeners && isset($this->elementAttributeListeners[$name])) {
			unset($this->elementAttributeListeners[$name]);
			$this->hasElementAttributeListeners = !empty($this->elementAttributeListeners);
		}
	}

	/**
	 * Sets the value of the ElementDynamicAttribute for the given name
	 * @param String $name the name of the dynamic attribute
	 * @param mixed $value the value to set for the dynamic attribute
	 * @throws RecordException if no ElementDynamicAttribute has been defined for this name or
	 * if the dynamic attribute is read only.
	 */
	public function setDynamicAttributeValue($name, $value) {
		$da = $this->getDynamicAttribute($name);
		if(!isset($da)) throw new RecordException("no ElementDynamicAttributes has been set under the name '$name'. Add one using the setDynamicAttribute method.", RecordException::INVALID_ARGUMENT);
		$da->setValue($value);
	}
	
	protected $hasElementAttributeListeners = false;
	private $elementAttributeListeners;
	
	/**
	 * Notifies element attribute changes to any ElementDynamicAttributes which are listening
	 * @param String $name element attribute name as defined in the setAttribute method.
	 * @param mixed $oldValue the old element attribute value
	 * @param mixed $newValue the new element attribute value
	 */
	protected function notifyElementAttributeChange($name, $oldValue, $newValue) {
		if($this->hasElementAttributeListeners) {
			foreach($this->elementAttributeListeners as $listener) {
				$listener->elementSetAttribute($name, $oldValue, $newValue);
			}
		}
	}
	
	// Element Info
	
	private $elementInfo;
	
	/**
	 * Sets the ElementInfo instance associated to this Element.
	 * This method can only be called once during the whole lifecycle of the Element
	 * @param ElementInfo $elementInfo
	 */
	public function setElementInfo($elementInfo) {
		if(isset($this->elementInfo)) throw new RecordException('ElementInfo property has already been set', RecordException::FORBIDDEN);
		$this->elementInfo = $elementInfo;
	}
	
	/**
	 * Returns the ElementInfo instance associated to this Element if set, else null.
	 * @return ElementInfo
	 */
	public function getElementInfo() {return $this->elementInfo;}
	
	// Inner fields implementation
	
	private $innerFieldsFieldList;
	/**
	 * Returns an inner fields FieldList given an access key or null if key is not defined
	 */
	public function getInnerFieldsFieldList($key) {
		if(is_null($key) || !isset($this->innerFieldsFieldList)) return null;		
		return $this->innerFieldsFieldList[$key];
	}
	/**
	 * Returns the inner field list which contains the given field, or null if it is not an inner field.
	 * @param String $innerFieldName the name of the inner field we are lookin for.
	 * @return FieldList or null
	 */
	public function getInnerFieldListContainingField($innerFieldName) {
		if(!isset($this->innerFieldsFieldList)) return null;
		foreach($this->innerFieldsFieldList as $fieldList) {
			try {
				$f = $fieldList->getField($innerFieldName);
				if(isset($f)) return $fieldList;
			}
			catch(ElementServiceException $e) {
				if($e->getCode() != ElementServiceException::NO_CORRESPONDANT_FIELD) throw $e;
			}
		}
		return null;
	}
	
	/**
	 * Stores an inner fields FieldList given an access key
	 * Replaces any existing field list stored under the same key 
	 */
	public function setInnerFieldsFieldList($key, $fieldList) {
		if(is_null($key)) throw new ElementServiceException("key cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(is_null($fieldList)) throw new ElementServiceException("inner field list cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(!isset($this->innerFieldsFieldList)) $this->innerFieldsFieldList = array();
		$this->innerFieldsFieldList[$key] = $fieldList;
	}
	
	private $innerFieldsWigiiBag;
	/**
	 * Returns a specific wigii bag used to store inner fields values or null if key is not defined
	 * @param $key the access key
	 */
	public function getInnerFieldsWigiiBag($key) {
		if(is_null($key) || !isset($this->innerFieldsWigiiBag)) return null;		
		return $this->innerFieldsWigiiBag[$key];
	}
	/**
	 * Stores a wigii bag for inner fields values under the given access key
	 */
	public function setInnerFieldsWigiiBag($key, $wigiiBag) {
		if(is_null($key)) throw new ElementServiceException("key cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(is_null($wigiiBag)) throw new ElementServiceException("wigii bag cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(!isset($this->innerFieldsWigiiBag)) $this->innerFieldsWigiiBag = array();
		$this->innerFieldsWigiiBag[$key] = $wigiiBag;
	}
	
	/**
	 * Returns true if this element has at least one inner field else returns false
	 */
	public function hasInnerFields() {
		if(!isset($this->innerFieldsFieldList)) return false;
		foreach($this->innerFieldsFieldList as $fieldList) {
			if(!$fieldList->isEmpty()) return true;
		}
		return false;
	}
	
	/**
	 * Returns true if this element has this inner field else returns false
	 * @param $fieldName the inner field name to look for
	 */
	public function hasInnerField($fieldName) {
		if(!isset($this->innerFieldsFieldList)) return false;
		foreach($this->innerFieldsFieldList as $fieldList) {
			try {
				$f = $fieldList->getField($fieldName);
				if(isset($f)) return true;
			}
			catch(ElementServiceException $e) {
				if($e->getCode() != ElementServiceException::NO_CORRESPONDANT_FIELD) throw $e;
			}
		}
		return false;
	}
}
/**
 * A class which loads on demand information linked to the element (parent of subelement or soft links)
 * Created by CWE on 19.08.2014
 */
class ElementLazyLoader {

	// configuration
	
	private $principal;
	/**
	 * @param Principal $principal
	 */
	public function setPrincipal($principal) {
		$this->principal = $principal;
	}
	/**
	 * @return Principal
	 */
	protected function getPrincipal() {
		if(!isset($this->principal)) {
			$this->principal = ServiceProvider::getAuthenticationService()->getMainPrincipal();
		}
		return $this->principal;
	}
	
	// dependency injection
	
	private $eltS;
	public function setElementService($elementService)
	{
		$this->eltS = $elementService;
	}
	protected function getElementService()
	{
		// autowired
		if(!isset($this->eltS))
		{
			$this->eltS = ServiceProvider::getElementService();
		}
		return $this->eltS;
	}
	
	
	// implementation
	
	/**
	 * @param Element $subElement element that should be a subelement
	 */
	public function fetchParentElement($subElement) {
		$element = $this->createElementInstance();
		$element->setId($subElement->getElementParentId());		
		if(!is_null($this->getElementService()->fillElement($this->getPrincipal(), $element))) {
			$element->setElementLazyLoader($this);
			$subElement->setParentElement($element);
		}		
	}
	
	public function fetchLinkedElement($fieldName, $element) {
		RecordException::throwNotImplemented();
	}
	
	/**
	 * @param Element $subElement element that should be a subelement
	 */
	public function fetchSubElementPathFromRoot($subElement) {
		$pathFromRoot=LinkSelectorListArrayImpl::createInstance();
		if($this->getElementService()->getSubElementPathFromRoot($this->getPrincipal(), $subElement->getId(), $pathFromRoot)) {
			$subElement->setSubElementPathFromRoot($pathFromRoot);
		}
	}
	
	protected function createElementInstance() {
		return Element::createInstance(null, FieldListArrayImpl::createInstance(), WigiiBagBaseImpl::createInstance());
	}
}

