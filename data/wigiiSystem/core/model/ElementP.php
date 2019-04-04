<?php
/**
 *  This file is part of Wigii (R) software.
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
 * An element associated to a PrincipalRights
 * Created by CWE on 26 août 09
 * Modified by CWE on 03.07.2014 to add the element states configuration
 */
class ElementP extends Model implements DbEntity
{
	private $element;
	private $principalRights;

	public static function createInstance($element)
	{
		$returnValue = new ElementP();
		$returnValue->setElement($element);
		return $returnValue;
	}

	/**
	 * DbEntity implementation
	 */
	public function getId(){
		if($this->getDbEntity()!=null){
			return $this->getDbEntity()->getId();
		}
		return null;
	}
	public function getDbEntity(){
		if(isset($this->element)){
			return $this->element;
		}
		return null;
	}

	public function getElement()
	{
		return $this->element;
	}
	protected function setElement($element)
	{
		$this->element = $element;
	}
	public function getRights()
	{
		return $this->principalRights;
	}
	public function setRights($principalRights)
	{
		$this->principalRights = $principalRights;
	}

	//this will reset completely the Rights of the element, and it will use the elementData
	//to manage S right or state_locked
	public function setRightsFromDB($principal, $data, $colPref = ''){
		// can read or more only if canWriteElement or canShareElement are not null and equal to 0 or 1
		if(isset($data[$colPref.'canWriteElement']) || isset($data[$colPref.'canShareElement']))
		{
			$pr = PrincipalRights::createInstance($data, $colPref);

			if($this->getElement() != null){
				//if canShareElement is true and control ownership is good --> change in canWriteElement
				if(isset($data[$colPref.'canShareElement'])){
					if(	$this->formatBoolean($data[$colPref.'canShareElement']) &&
						$this->getElement()->getSys_creationUser() == $principal->getRealUserId()
						){
						$pr->setCanWriteElement(true);
						$pr->setCanShareElement(true);
					}
				}
				//if state_locked and control of last modif user can be done and does not match --> change in read only
				if(	$this->getElement()->isState_locked() &&
					$this->getElement()->getSys_user() != $principal->getRealUserId()
					){
					$pr = PrincipalRights::createInstance();
				}
			}

			$this->setRights($pr);
		}
		else $this->setRights(null);
	}
	
	// Element state and action configuration
	
	private $enabledElementState_locked;
	public function isEnabledElementState_locked() {return $this->enabledElementState_locked;}
	public function enableElementState_locked($bool) {$this->enabledElementState_locked = $bool;}
	
	private $enabledElementState_blocked;
	public function isEnabledElementState_blocked() {return $this->enabledElementState_blocked;}
	public function enableElementState_blocked($bool) {$this->enabledElementState_blocked = $bool;}
	
	private $enabledElementState_important1;
	public function isEnabledElementState_important1() {return $this->enabledElementState_important1;}
	public function enableElementState_important1($bool) {$this->enabledElementState_important1 = $bool;}
	
	private $enabledElementState_important2;
	public function isEnabledElementState_important2() {return $this->enabledElementState_important2;}
	public function enableElementState_important2($bool) {$this->enabledElementState_important2 = $bool;}
	
	private $enabledElementState_finalized;
	public function isEnabledElementState_finalized() {return $this->enabledElementState_finalized;}
	public function enableElementState_finalized($bool) {$this->enabledElementState_finalized = $bool;}
	
	private $enabledElementState_approved;
	public function isEnabledElementState_approved() {return $this->enabledElementState_approved;}
	public function enableElementState_approved($bool) {$this->enabledElementState_approved = $bool;}
	
	private $enabledElementState_dismissed;
	public function isEnabledElementState_dismissed() {return $this->enabledElementState_dismissed;}
	public function enableElementState_dismissed($bool) {$this->enabledElementState_dismissed = $bool;}
	
	private $enabledElementState_archived;
	public function isEnabledElementState_archived() {return $this->enabledElementState_archived;}
	public function enableElementState_archived($bool) {$this->enabledElementState_archived = $bool;}
	
	private $enabledElementState_deprecated;
	public function isEnabledElementState_deprecated() {return $this->enabledElementState_deprecated;}
	public function enableElementState_deprecated($bool) {$this->enabledElementState_deprecated = $bool;}
	
	private $enabledElementState_hidden;
	public function isEnabledElementState_hidden() {return $this->enabledElementState_hidden;}
	public function enableElementState_hidden($bool) {$this->enabledElementState_hidden = $bool;}
	
	private $enabledElementAction_delete;
	public function isEnabledElementAction_delete() {return $this->enabledElementAction_delete;}
	public function enableElementAction_delete($bool) {$this->enabledElementAction_delete = $bool;}
	
	private $enabledElementAction_organize;
	public function isEnabledElementAction_organize() {return $this->enabledElementAction_organize;}
	public function enableElementAction_organize($bool) {$this->enabledElementAction_organize = $bool;}
	
	/**
	 * Returns a binary encoding of the "enableElementState" and "enableElementAction"
	 * 2^0: state_locked, 
	 * 2^1: state_blocked, 
	 * 2^2: state_important1, 
	 * 2^3: state_important2, 
	 * 2^4: state_finalized, 
	 * 2^5: state_approved, 
	 * 2^6: state_dismissed,
	 * 2^7: state_archived, 
	 * 2^8: state_deprecated, 
	 * 2^9: state_hidden,
	 * 2^10: action_delete,
	 * 2^11: action_organize 
	 */
	public function getEnableElementStateAsInt() {
		$returnValue = 0;
		if($this->isEnabledElementState_locked()) $returnValue += 1;
		if($this->isEnabledElementState_blocked()) $returnValue += 2;
		if($this->isEnabledElementState_important1()) $returnValue += 4;
		if($this->isEnabledElementState_important2()) $returnValue += 8;
		if($this->isEnabledElementState_finalized()) $returnValue += 16;
		if($this->isEnabledElementState_approved()) $returnValue += 32;
		if($this->isEnabledElementState_dismissed()) $returnValue += 64;
		if($this->isEnabledElementState_archived()) $returnValue += 128;
		if($this->isEnabledElementState_deprecated()) $returnValue += 256;
		if($this->isEnabledElementState_hidden()) $returnValue += 512;
		if($this->isEnabledElementAction_delete()) $returnValue += 1024;
		if($this->isEnabledElementAction_organize()) $returnValue += 2048;
		return $returnValue;
	}	

	/**
	 * Returns true if specific state or action is enabled
	 * @param String $state state name, one of: 'locked', 'blocked', 'important1', 'important2', 'finalized', 
	 * 'approved', 'dismissed', 'archived', 'deprecated', 'hidden',
	 * action name, one of 'delete', 'organize'
	 */
	public function isEnabledElementState($state) {
		switch($state) {
			case 'locked': return $this->isEnabledElementState_locked(); break;
			case 'blocked': return $this->isEnabledElementState_blocked(); break;
			case 'important1': return $this->isEnabledElementState_important1(); break;
			case 'important2': return $this->isEnabledElementState_important2(); break;
			case 'finalized': return $this->isEnabledElementState_finalized(); break;
			case 'approved': return $this->isEnabledElementState_approved(); break;
			case 'dismissed': return $this->isEnabledElementState_dismissed(); break;
			case 'archived': return $this->isEnabledElementState_archived(); break;
			case 'deprecated': return $this->isEnabledElementState_deprecated(); break;
			case 'hidden': return $this->isEnabledElementState_hidden(); break;
			case 'delete': return $this->isEnabledElementAction_delete(); break;
			case 'organize': return $this->isEnabledElementAction_organize(); break;
			default: throw new RecordException("state '$state' is not a valid Element state or action.", RecordException::INVALID_ARGUMENT);
		}
	}
	
	/**
	 * Enables or not the specific state or action.
	 * @param String $state state name, one of: 'locked', 'blocked', 'important1', 'important2', 'finalized',
	 * 'approved', 'dismissed', 'archived', 'deprecated', 'hidden',
	 * action name, one of 'delete', 'organize'.
	 * @param Boolean $bool
	 */
	public function enableElementState($state, $bool) {
		switch($state) {
			case 'locked': return $this->enableElementState_locked($bool); break;
			case 'blocked': return $this->enableElementState_blocked($bool); break;
			case 'important1': return $this->enableElementState_important1($bool); break;
			case 'important2': return $this->enableElementState_important2($bool); break;
			case 'finalized': return $this->enableElementState_finalized($bool); break;
			case 'approved': return $this->enableElementState_approved($bool); break;
			case 'dismissed': return $this->enableElementState_dismissed($bool); break;
			case 'archived': return $this->enableElementState_archived($bool); break;
			case 'deprecated': return $this->enableElementState_deprecated($bool); break;
			case 'hidden': return $this->enableElementState_hidden($bool); break;
			case 'delete': return $this->enableElementAction_delete($bool); break;
			case 'organize': return $this->enableElementAction_organize($bool); break;
			default: throw new RecordException("argument '$state' is not a valid Element state or action.", RecordException::INVALID_ARGUMENT);
		}
	}
	
	private $parentElementState_blocked;
	public function isParentElementState_blocked() {return $this->parentElementState_blocked;}
	public function setParentElementState_blocked($bool) {$this->parentElementState_blocked = $bool;}
	
	// Element Info
	
	/**
	 * Computes the ElementInfo instance linked to this Element.
	 * This method can only be called once in the whole lifecycle of this Element.
	 * @param Principal the principal to which the PrincipalRights of this ElementP are linked.
	 * @param GroupList|ElementConfigInfo $groupList an optional GroupList selecting the configuration or
	 * an existing ElementConfigInfo instance.
	 * @param AuthorizationServiceStamp an optional AuthorizationServiceStamp instance to certify content validity.
	 */
	public function computeElementInfo($principal, $groupList, $authorizationServiceStamp=null) {
		if(!isset($this->element)) throw new RecordException('element has not been set.', RecordException::INVALID_STATE);
		if(is_null($this->element->getElementInfo())) {
			$eInfo = ElementInfo::createInstance($principal, $this, $groupList, $authorizationServiceStamp);
			$this->element->setElementInfo($eInfo);
			return $eInfo;
		}
		else throw new RecordException('ElementInfo has already been calculated.', RecordException::FORBIDDEN);
	}
	
	/**
	 * Returns the ElementInfo instance associated to this Element if set, else null.
	 * @return ElementInfo
	 */
	public function getElementInfo() {
		if(isset($this->element)) return $this->element->getElementInfo();
		else return null;
	}
}