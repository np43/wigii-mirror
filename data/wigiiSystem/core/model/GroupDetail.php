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
 * Detail of a Group
 * Accessible only if Principal has read access on group
 * Created by CWE on 29 août 09
 */
class GroupDetail extends Model
{
	private $description;
	private $portal;
	private $htmlContent;
	private $xmlPublish;
	private $subscription;
	private $emailNotification;
	private $numberOfBlockedElements;

	public static function createInstance($array = null)
	{
		$g = new GroupDetail();
		if(is_array($array)){
			if(isset($array["description"])) $g->setDescription($array["description"]);
			if(isset($array["portal"])) $g->setPortal($array["portal"]);
			if(isset($array["htmlContent"])) $g->setHtmlContent($array["htmlContent"]);
			if(isset($array["xmlPublish"])) $g->setXmlPublish($array["xmlPublish"]);
			if(isset($array["subscription"])) $g->setSubscription($array["subscription"]);
			if(isset($array["emailNotification"])) $g->setEmailNotification($array["emailNotification"]);
			if(isset($array["numberOfBlockedElements"])) $g->setNumberOfBlockedElements($array["numberOfBlockedElements"]);
		}
		return $g;
	}

	// Generic accessor

	/**
	 * Returns the value of a group attribute given a fieldSelector
	 */
	public function getAttribute($fieldSelector)
	{
		if(is_null($fieldSelector)) throw new GroupAdminServiceException("fieldSelector cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_object($fieldSelector)) $fName = $fieldSelector->getFieldName();
		else $fName = $fieldSelector;
		switch($fName)
		{
			case "description" : 			return $this->getDescription();
			case "portal" :					return $this->getPortal();
			case "htmlContent" : 			return $this->getHtmlContent();
			case "xmlPublish" : 			return $this->getXmlPublish();
			case "subscription" : 			return $this->getSubscription();
			case "emailNotification" :		return $this->getEmailNotification();
			case "numberOfBlockedElements": return $this->getNumberOfBlockedElements();
			default: throw new GroupAdminServiceException("invalid user attribute $fName in field selector", GroupAdminServiceException::INVALID_ARGUMENT);
		}
	}
	
	/**
	 * Sets the value of a group attribute given a fieldSelector
	 */
	public function setAttribute($value, $fieldSelector)
	{
		if(is_null($fieldSelector)) throw new GroupAdminServiceException("fieldSelector cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_object($fieldSelector)) $fName = $fieldSelector->getFieldName();
		else $fName = $fieldSelector;
		switch($fName)
		{
			case "description" : 			return $this->setDescription($value);
			case "portal" :					return $this->setPortal($value);
			case "htmlContent" : 			return $this->setHtmlContent($value);
			case "xmlPublish" : 			return $this->setXmlPublish($value);
			case "subscription" : 			return $this->setSubscription($value);
			case "emailNotification" :		return $this->setEmailNotification($value);
			case "numberOfBlockedElements": return $this->setNumberOfBlockedElements($value);
			default: throw new GroupAdminServiceException("invalid user attribute $fName in field selector", GroupAdminServiceException::INVALID_ARGUMENT);
		}
	}

	// Attributes

	public function getPortal()
	{
		return $this->portal;
	}
	public function setPortal($portal)
	{
		$this->portal = $this->formatValue($portal);
	}
	public function getDescription()
	{
		return $this->description;
	}
	public function setDescription($description)
	{
		$this->description = $this->formatValue($description);
	}
	public function getHtmlContent()
	{
		return $this->htmlContent;
	}
	public function setHtmlContent($htmlContent)
	{
		$this->htmlContent = $this->formatValue($htmlContent);
	}
	public function getXmlPublish()
	{
		return $this->xmlPublish;
	}
	public function setXmlPublish($xmlPublish)
	{
		$this->xmlPublish = $this->formatValue($xmlPublish);
	}
	public function getNewXmlPublishCode($p, $group){
		return md5($p->getExecutionId().$group->getId().time());
	}
	public function getSubscription()
	{
		return $this->subscription;
	}
	public function setSubscription($subscription)
	{
		$this->subscription = $this->formatValue($subscription);
	}
	public function getEmailNotification()
	{
		return $this->emailNotification;
	}
	public function setEmailNotification($emailNotification)
	{
		$this->emailNotification = $this->formatValue($emailNotification);
	}
	
	// Additional dynamic information
	
	public function getNumberOfBlockedElements() {
		return $this->numberOfBlockedElements;
	}
	public function setNumberOfBlockedElements($numberOfBlockedElements) {
		$this->numberOfBlockedElements = $numberOfBlockedElements;
	}
}