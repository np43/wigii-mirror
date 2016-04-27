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
 * An Element Info class
 * Created by CWE on 23.09.2014
 */
class ElementInfo extends Model
{
	// Object lifecycle
	
	/**
	 * Creates a new instance of ElementInfo given an existing ElementP and 
	 * optional GroupList selecting the Element configuration.
	 * @param Principal the principal to which the PrincipalRights of the given ElementP are linked.
	 * @param ElementP|Element $elementP an ElementP or Element instance.
	 * @param GroupList|ElementConfigInfo $groupList an optional GroupList selecting the configuration or
	 * an existing ElementConfigInfo instance.
	 * @param AuthorizationServiceStamp an optional AuthorizationServiceStamp instance to certify content validity.
	 */
	public static function createInstance($principal, $elementP, $groupList, $authorizationServiceStamp=null) {
		$returnValue = new self();
		$returnValue->extractInfo($principal, $elementP, $groupList);
		if(isset($authorizationServiceStamp)) $returnValue->setAuthorizationServiceStamp($authorizationServiceStamp);
		return $returnValue;
	}
	
	protected $elementInfo;
	
	protected function extractInfo($principal, $elementP, $groupList) {
		if(!isset($elementP)) throw new RecordException('elementP cannot be null', RecordException::INVALID_ARGUMENT);
		if($elementP instanceof ElementP) {
			$element = $elementP->getElement();
			$pRights = $elementP->getRights();
		}
		else {
			$element = $elementP;
			$pRights = null;
		}
		if($principal instanceof Principal) {
			$readerUsername = $principal->getUsername();
			$readerUserId = $principal->getUserId();
		}
		else {
			$readerUsername = null;
			$readerUserId = null;
		}
		
		$this->elementInfo = array(
			'id' => $element->getId(),
			'canWriteElement' => (isset($pRights) ? $pRights->canWriteElement() : null),
			'readerUsername' => $readerUsername,
			'readerUserId' => $readerUserId,
			'readDate' => time(),
			'state_blocked' => $element->isState_blocked(),
			'elementConfigInfo' => (isset($groupList) ? 
					($groupList instanceof ElementConfigInfo ? $groupList : ElementConfigInfo::createInstance($groupList)) : 
					ElementConfigInfo::createInstance($element->getModule()))
		);
	}
	
	private $authorizationServiceStamp;
	protected function setAuthorizationServiceStamp($stamp) {$this->authorizationServiceStamp = $stamp;}
	/**
	 * @return AuthorizationServiceStamp if set else null.
	 */
	public function getAuthorizationServiceStamp() {return $this->authorizationServiceStamp;}
	
	// Element Info
	
	/**
	 * Returns the element id.
	 * @return int
	 */
	public function getElementId() {
		if(isset($this->elementInfo)) return $this->elementInfo['id'];
		else return null;
	}

	/**
	 * Returns true if this element is writable by the principal who fetched it.
	 * @return boolean
	 */
	public function isElementWritable() {
		if(isset($this->elementInfo)) return $this->elementInfo['canWriteElement'];
		else return null;
	}

	/**
	 * Returns the username of the principal who fetched the element.
	 * @return String
	 */
	public function getReaderUsername() {
		if(isset($this->elementInfo)) return $this->elementInfo['readerUsername'];
		else return null;
	}
	/**
	 * Returns the user id of the principal who fetched the element or null if it is a system principal or not defined.
	 * @return int
	 */
	public function getReaderUserId() {
		if(isset($this->elementInfo)) return $this->elementInfo['readerUserId'];
		else return null;
	}
	
	/**
	 * Returns the Unix timestamp of when this element info has been created.
	 * @return int unix timestamp in seconds.
	 */
	public function getReadDate() {
		if(isset($this->elementInfo)) return $this->elementInfo['readDate'];
		else return null;
	}
	
	/**
	 * Returns true if this element had state blocked when it has been fetched.
	 * @return boolean 
	 */
	public function isElementBlocked() {
		if(isset($this->elementInfo)) return $this->elementInfo['state_blocked'];
		else return null;
	}
	
	/**
	 * Returns the list of Fields of this Element.
	 * @param Principal $principal authenticated user performing the operation
	 * @param FieldList $fieldList the field list instance to be filled with the list of fields.
	 */
	public function getFields($principal, $fieldList) {
		if(!isset($this->elementInfo)) throw new RecordException('elementInfo has not been calculated', RecordException::INVALID_STATE);
		return $this->elementInfo['elementConfigInfo']->getFields($principal, $fieldList);
	}
}

