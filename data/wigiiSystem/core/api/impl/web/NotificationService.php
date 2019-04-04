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
 * Wigii email NotificationService 
 * Created on 20 nov. 09 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 * Modified by Medair (CWE) on 20.02.2018 to control notifications of sub-elements based on parent config.
 * Modified by CWE on 28.01.2019 to translate wigii namespace name
 */
class NotificationService implements MultiplexedEvent {

	private $_debugLogger;
	private $_executionSink;
	private $isNotificationPostingValueBlocked;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("NotificationService");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("NotificationService");
		}
		return $this->_executionSink;
	}

	/*
	 * dependency Injection
	 */
	private $configService;
	public function setConfigService($configService){
		$this->configService = $configService;
	}
	protected function getConfigService(){
		//autowired
		if(!isset($this->configService)){
			$this->configService = ServiceProvider::getConfigService();
		}
		return $this->configService;
	}
	private $executionService;
	public function setExecutionService($executionService){
		$this->executionService = $executionService;
		return $this;
	}
	protected function getExecutionService(){
		//autowired
		if(!isset($this->executionService)){
			$this->executionService = ServiceProvider::getExecutionService();
		}
		return $this->executionService;
	}
	private $translationService;
	public function setTranslationService($translationService){
		$this->translationService = $translationService;
	}
	protected function getTranslationService(){
		//autowired
		if(!isset($this->translationService)){
			$this->translationService = ServiceProvider::getTranslationService();
		}
		return $this->translationService;
	}
	private $emailService;
	public function setEmailService($emailService){
		$this->emailService = $emailService;
	}
	protected function getEmailService(){
		//autowired
		if(!isset($this->emailService)){
			$this->emailService = TechnicalServiceProvider::getEmailService();
		}
		return $this->emailService;
	}
	private $elementService;
	public function setElementService($elementService){
		$this->elementService = $elementService;
	}
	protected function getElementService(){
		//autowired
		if(!isset($this->elementService)){
			$this->elementService = ServiceProvider::getElementService();
		}
		return $this->elementService;
	}
	private $groupAdminService;
	public function setGroupAdminService($groupAdminService){
		$this->groupAdminService = $groupAdminService;
	}
	protected function getGroupAdminService(){
		//autowired
		if(!isset($this->groupAdminService)){
			$this->groupAdminService = ServiceProvider::getGroupAdminService();
		}
		return $this->groupAdminService;
	}

	private $rootPrincipal;
	public function setRootPrincipal($rootP)
	{
		$this->rootPrincipal = $rootP;
	}
	protected function getRootPrincipal()
	{
		if(!isset($this->rootPrincipal)) throw new AuthorizationServiceException("root principal has not been initialized by Service Provider", AuthorizationServiceException::FORBIDDEN);
		return $this->rootPrincipal;
	}

	private $skipNext = false;
	public function skipNextNotification(){
		$this->skipNext = true;
	}
	public function doesNextNotificationNeedToBeSkiped(){
		$r = $this->skipNext;
		$this->skipNext = false;
		return $r;
	}
	public function resetSkipNotification(){
		$this->skipNext = false;
	}
	/**
	 * throw NO_NOTIFICATION_NEEDED if this is the case. otherwise return true
	 */
	public function isNotificationNeededForElement($p, $element, $eventName){
		if(!isset($element)) throw new NotificationServiceException("Element required", NotificationServiceException::INVALID_ARGUMENT);

		$module = $element->getModule();
		if($this->getConfigService()->getParameter($p, $module, "Notification_enable")!="1"){
			throw new NotificationServiceException("Notification is not enabled in module: ".$module->getModuleName(), NotificationServiceException::NO_NOTIFICATION_NEEDED);
		}
		// Medair (CWE) 20.02.2018 if sub-element, then notification can be disabled by parent.
		if($element->isSubElement()) {
		    $rootElement = $this->getRootElement($p, $element);
		    // disables notification if root element has notification disabled
		    if($this->getConfigService()->getParameter($p, $rootElement->getModule(), "Notification_enable")!="1"){
		        throw new NotificationServiceException("Notification is not enabled in module: ".$rootElement->getModule()->getModuleName(), NotificationServiceException::NO_NOTIFICATION_NEEDED);
		    }
		    // if Notification_alwaysForceNotification=0, checks at sub-element field level if forceNotification=0 
		    if($this->getConfigService()->getParameter($p, $rootElement->getModule(), "Notification_alwaysForceNotification")=="0") {
		        if($rootElement->getFieldList()->doesFieldExist($element->getLinkName())) {
		            $fieldXml = $rootElement->getFieldList()->getField($element->getLinkName())->getXml();
		            $forceNotification = (string)$fieldXml["forceNotification"];
		            if(ServiceProvider::getWigiiBPL()->evaluateConfigParameter($p, $forceNotification,$rootElement) != "1") {
		                throw new NotificationServiceException("$eventName on Sub-Element but no ForceNotification on parent field found", NotificationServiceException::NO_NOTIFICATION_NEEDED);
		            }
		        }
		    }
		}
		switch ($eventName){
			case "insert":
				//do notification
				break;
			case "update":
				if($this->getConfigService()->getParameter($p, $module, "Notification_alwaysForceNotification")=="1"){
					//one modification at least needs to be done
					if(!$element->getWigiiBag()->hasChanges()){
						throw new NotificationServiceException("$eventName on Element but no changes are made", NotificationServiceException::NO_NOTIFICATION_NEEDED);
					}
				} else {
					//look for modification on field with forceNotification
					$forceNotificationFields = $this->getConfigService()->mf($p, $module)->xpath("*[@forceNotification='1']");
					if($forceNotificationFields != null){
						$needNotification = false;
						$wigiiBag = $element->getWigiiBag();
						foreach($forceNotificationFields as $forceNotificationField){
							if($wigiiBag->isChanged($forceNotificationField->getName())){
								$needNotification = true;
								break;
							}
						}
						if(!$needNotification) {
							throw new NotificationServiceException("$eventName on Element but no ForceNotification fields with changes found", NotificationServiceException::NO_NOTIFICATION_NEEDED);
						}
					} else {
						//if no ForceNotificationFound then no notification needed
						throw new NotificationServiceException("$eventName on Element but no ForceNotification field found", NotificationServiceException::NO_NOTIFICATION_NEEDED);
					}
				}
				break;
			case "setState":
				//do notification
				break;
			case "delete":
				//do notification
				break;
			case "restore":
				//do notification
				break;
			case "share":
				if($this->getConfigService()->getParameter($p, $module, "Notification_enableOnSharing")=="0"){
					throw new NotificationServiceException("$eventName on Element is configured to not do the notification", NotificationServiceException::NO_NOTIFICATION_NEEDED);
				}
				//else do notification
				break;
			case "unshare":
				if($this->getConfigService()->getParameter($p, $module, "Notification_enableOnSharing")=="0"){
					throw new NotificationServiceException("$eventName on Element is configured to not do the notification", NotificationServiceException::NO_NOTIFICATION_NEEDED);
				}
				//else do notification
				break;
			default:
				throw new NotificationServiceException("unknown event name: ".$eventName, NotificationServiceException::UNKNOWN_EVENT_TYPE);
		}
		return true;
	}

	/**
	 * looks if notification is needed for this multiple element event
	 */
	public function isNotificationNeededForMultipleElement($p, $module, $eventName, $rec=null){
		if(!isset($module)) throw new NotificationServiceException("module required", NotificationServiceException::INVALID_ARGUMENT);
		//if(!isset($elementIds)) throw new NotificationServiceException("elementIds required", NotificationServiceException::INVALID_ARGUMENT);

		if($this->getConfigService()->getParameter($p, $module, "Notification_enable")!="1"){
			throw new NotificationServiceException("Notification is not enabled in module: ".$module->getModuleName(), NotificationServiceException::NO_NOTIFICATION_NEEDED);
		}
		switch ($eventName){
			case "insert":
				//do notification
				break;
			case "update":
				if($this->getConfigService()->getParameter($p, $module, "Notification_alwaysForceNotification")=="1"){
					//one modification at least needs to be done
					if(!$rec->getWigiiBag()->hasChanges()){
						throw new NotificationServiceException("$eventName on MultipleElement but no changes are made", NotificationServiceException::NO_NOTIFICATION_NEEDED);
					}
				} else {
					//look for modification on field with forceNotification
					$forceNotificationFields = $this->getConfigService()->mf($p, $module)->xpath("*[@forceNotification='1']");
					if($forceNotificationFields != null){
						$needNotification = false;
						$wigiiBag = $rec->getWigiiBag();
						foreach($forceNotificationFields as $forceNotificationField){
							if($wigiiBag->isChanged($forceNotificationField->getName())){
								$needNotification = true;
								break;
							}
						}
						if(!$needNotification) {
							throw new NotificationServiceException("$eventName on MultipleElement but no ForceNotification fields with changes found", NotificationServiceException::NO_NOTIFICATION_NEEDED);
						}
					} else {
						//if no ForceNotificationFound then no notification needed
					    throw new NotificationServiceException("$eventName on MultipleElement but no ForceNotification field found", NotificationServiceException::NO_NOTIFICATION_NEEDED);
					}
				}
				break;
			case "setState":
				//do notification
				break;
			case "delete":
				//do notification
				break;
			case "share":
			case "setShare":
				if($this->getConfigService()->getParameter($p, $module, "Notification_enableOnSharing")=="0"){
				    throw new NotificationServiceException("$eventName on MultipleElement is configured to not do the notification", NotificationServiceException::NO_NOTIFICATION_NEEDED);
				}
				//else do notification
				break;
			case "unshare":
				if($this->getConfigService()->getParameter($p, $module, "Notification_enableOnSharing")=="0"){
				    throw new NotificationServiceException("$eventName on MultipleElement is configured to not do the notification", NotificationServiceException::NO_NOTIFICATION_NEEDED);
				}
				//else do notification
				break;
			case "moveToModule":
				//do notification, like insert
				break;
			default:
				throw new NotificationServiceException("unknown event name: ".$eventName, NotificationServiceException::UNKNOWN_EVENT_TYPE);
		}
		return true;
	}
	
	private $rootElement;
	public function setRootElement($rec, $rootElement){ $this->rootElement[$rec->getId()] = $rootElement; }
	protected function getRootElement($p, $rec){
		if(!isset($this->rootElement)) $this->rootElement = array();
		if($rec->isSubElement() && !isset($this->rootElement[$rec->getId()])){
			//first lookup the element root:
			$rootElementId = $this->getConfigService()->getCurrentSubElementPathFromRoot()->getFirstLinkSelector()->getOwnerElementId();
			$this->executionSink()->log("Subelement has ".$rootElementId." as root element.");
			$bag = FormBag::createInstance();
			$ffl = FormFieldList::createInstance($bag);
			$rootElement = Element::createInstance($this->getExecutionService()->getCrtModule(), $ffl, $bag);
			$rootElement->setId($rootElementId);
			$this->executionSink()->log("get root element from subelement: ".$rec->getId());
			$this->getElementService()->fillElement($p, $rootElement);
			$this->setRootElement($rec, $rootElement);
			//recenter config on subElement, the use of selectSubElementsConfig is in the case of a delete, the sublement could no more exist when calling this method
			$this->executionSink()->log("recenter config on subElement");
			$this->getConfigService()->selectSubElementsConfig($p, $rec->getElementParentId(), $rec->getLinkName());
		}
		return $this->rootElement[$rec->getId()];
	}

	/**
	 * @param mixed $object could be $pWithElement or $pWithElementWithGroup depending on the eventName
	 */
	public function event($eventName, $entityName, $module, $object){
		$p = $this->getP($object);
		$this->executionSink()->publishStartOperation("event", $p);
		try{
			if($this->doesNextNotificationNeedToBeSkiped()){
				$this->executionSink()->log("skip notification");
				$this->executionSink()->publishEndOperation("event", $p);
				return;
			}
			$gObj = null;
			if(method_exists($object, "getGroup")){
				$gObj = $object->getGroup();
			} else if(method_exists($object, "getGroupP")){
				$gObj = $object->getGroupP();
			} else if(method_exists($object, "getGroupList")){
				$gObj = $object->getGroupList();
			} else if(method_exists($object, "getGroupPList")){
				$gObj = $object->getGroupPList();
			}
			$rec = null;
			if(method_exists($object, "getElement")){
				$rec = $object->getElement();
			} else if(method_exists($object, "getElementP")){
				$rec = $object->getElementP()->getElement();
			} else if(method_exists($object, "getRecord")){
				$rec = $object->getRecord();
			}

			$otherArray = null;
			if(method_exists($object, "getArray")){
				$otherArray = $object->getArray();
			}

			$state = null;
			if(method_exists($object, "getState")){
				$state = $object->getState();
			}

			switch($entityName){
				case "Element":
					if($rec->isSubElement()) $this->getRootElement($p, $rec); //fill rootElement and center the configuration on rootElement
					else {
						//change execution service module of translation service in order to
						//translate correctly any #element
						$this->getTranslationService()->setExecutionModule($module);
					}
					$this->isNotificationNeededForElement($p, $rec, $eventName);

					//send the notification
					$mail = $this->getEmailService()->getEmailInstance();

					$this->setFrom($p, $mail);
					$this->addRecipients($p, $eventName, $entityName, $mail, $rec, $gObj);
					$this->setSubject($p, $eventName, $entityName, $module, $mail, $rec, $gObj);
					$this->setBody($p, $eventName, $entityName, $module, $mail, $rec, $gObj);

					//check there is recipients
					if($mail->hasRecipients()){
						$this->setAttachements($p, $eventName, $entityName, $module, $mail, $rec, $gObj);
						$mergeFields = $this->getConfigService()->mf($p, $module)->xpath("*[@useMergeData]");
						$mergeData = null;
						if($mergeFields){
							$mergeFields = $mergeFields[0];
							$mergeFields["useMergeData"];
							$mergeData = str2array($rec->getFieldValue((string)$mergeFields["useMergeData"]));
						}
						$this->getEmailService()->send($p, $mail, $mergeData);
					}
					break;
				case "MultipleElement":
					$elementPList = $object->getElementPList();
					//$module = $object->getModule();

					$this->isNotificationNeededForMultipleElement($p, $module, $eventName, $rec);

					//send the notification
					$mail = $this->getEmailService()->getEmailInstance();

					$this->setFrom($p, $mail);
					$this->addRecipientsForMultiple($p, $eventName, $entityName, $mail, $elementPList, $gObj);
					$this->setSubjectForMultiple($p, $eventName, $entityName, $module, $mail, $elementPList, $gObj);
					$this->setBodyForMultiple($p, $eventName, $entityName, $module, $mail, $elementPList, $rec, $gObj, $otherArray);

					//check there is recipients
					if($mail->hasRecipients()){
						$this->getEmailService()->send($p, $mail);
					}

					break;
			}

		} catch (NotificationServiceException $e){
			if ($e->getCode() == NotificationServiceException::NO_EMAIL_FOR_NOTIFICATION_FOUND){
				//if nobody to notify, then we do nothing
				$this->executionSink()->log("no email found for notification");
			} else if ($e->getCode() == NotificationServiceException::NO_NOTIFICATION_NEEDED){
				$this->executionSink()->log("No notification needed: ".$e->getMessage());
			} else{
				$this->executionSink()->publishEndOperationOnError("event", $e, $p);
				//reset translation module to current execution module
				$this->getTranslationService()->setExecutionModule($this->getExecutionService()->getCrtModule());
				throw $e;
			}
		} catch (Exception $e){
			$this->executionSink()->publishEndOperationOnError("event", $e, $p);
			//reset translation module to current execution module
			$this->getTranslationService()->setExecutionModule($this->getExecutionService()->getCrtModule());
			throw new NotificationServiceException('Fail to notify '.$eventName.' '.$entityName.' '.$module->getModuleUrl(),NotificationServiceException::WRAPPING, $e);
		}
		//reset translation module to current execution module
		$this->getTranslationService()->setExecutionModule($this->getExecutionService()->getCrtModule());
		$this->executionSink()->publishEndOperation("event", $p);
	}

	private function getP($object)
	{
		if(is_null($object)) throw new NotificationServiceException('object can not be null', NotificationServiceException::INVALID_ARGUMENT);
		$p = $object->getP();
		if(is_null($p)) throw new NotificationServiceException('object need to include P', NotificationServiceException::INVALID_ARGUMENT);
		return $p;
	}
	protected function getElementView($p, $rec, $trm, $fsl){
	    $options = $this->htmlRenderingOptions;/* gets some extra options to customize the html rendering process */
	    if(!isset($options)) $options = wigiiBPLParam();
	    
		$prevVal = null; //this will contains last value, if filled elementListText must have a , before
		$isaFieldSelectorFromActivity = is_a($fsl, "FieldSelectorListForActivity");

		$trm->reset($rec);

		if($fsl){
			foreach($fsl->getListIterator() as $key=>$fs){
				if($isaFieldSelectorFromActivity) $xmlHeader = $fsl->getXml($key);
				else $xmlHeader = null;
				if(!$fs->isElementAttributeSelector()){
					$field = $rec->getFieldList()->getField($fs->getFieldName());
					$fieldXml = $field->getXml();
				} else $field = null;
				if(isset($xmlHeader) && $xmlHeader["hidden"]=="1" && $xmlHeader["notIgnoreNotification"]!="1") continue;
				if(isset($xmlHeader) && $xmlHeader["ignoreNotification"]=="1" && $fieldXml["notIgnoreNotification"]!="1") continue;
				if(isset($fieldXml) && $fieldXml["ignoreNotification"]=="1") continue;

				$val = $trm->formatValueFromFS($fs, $rec, false, false);

				if($val==null && $val!==false && $xmlHeader["displayLabelNotification"]!="1") continue; //don't need to display null values

				//if Booleans then display the label:
				$label = null;
				if(!$fs->isElementAttributeSelector()){
					$dt = $field->getDataType();
					if($dt->getDataTypeName()=="Booleans" || $xmlHeader["displayLabelNotification"]=="1"){
						$trm->displayLabel($fs->getFieldName());
						$label = '<font style="font-weight:normal;font-style:italic;">'.$trm->getHtmlAndClean().": </font>";
					}
				}
				if(is_array($val)){
					//this can be either a multipleAttribut either a multilanguage field
					if($fsl->getSelectedLanguages()!=null){
						//if language selected, then take only selected language
						$tempVal = array_intersect_key($val, $fsl->getSelectedLanguages());
						//if no intersection, this is probably not a multilanguage field.
						if($tempVal != null) $val = $tempVal;
					}
					$val = implode(", ", $val);
				}

				$sep = ", ";
				if($xmlHeader["newEmptyLineNotification"]=="1"){
					$sep = "<br /><br />";
				}
				if($xmlHeader["newLineNotification"]=="1"){
					$sep = "<br />";
				}

				//if after an html image
				if($prevVal && strstr($prevVal, '<img')!==false){
					$elementListText .= "&nbsp;$sep";
				//other wise add a ,
				} else {
					if($prevVal) $elementListText .= "$sep";
				}


				$prevVal = $label.$val;
				$elementListText .= $prevVal;
			}
		}
		return $elementListText;
	}

	//rootElement is in the case of a subitem, the notification display the root element with a change request showing the subelement changes
	protected function getInitialMessageContent($p, $eventName, $entityName, $module, $rec, $gObj, $elementPList=null){
		$options = $this->htmlRenderingOptions;/* gets some extra options to customize the html rendering process */
		if(!isset($options)) $options = wigiiBPLParam();
		
	    if($rec && $rec->isSubElement()){
			$rootElement = $this->getRootElement($p, $rec);
		}
		$intro = "";
		if(!$p->getRealUser()){
			$intro .= "<b>".$this->getTranslationService()->t($p, "systemPrincipal")."</b>"." ";
		} else {
			$intro .= "<b>".$p->getRealUser()->getUsername()."</b>"." ";
		}
		switch($entityName){
			case "MultipleElement":
				//construct the subject
				switch ($eventName){
					case "update":
						$intro .= $this->getTranslationService()->t($p, "hasUpdatedFollowingElementsWithChanges");
						break;
					case "setState":
						$intro .= $this->getTranslationService()->t($p, "hasStateChangedFollowingElements");
						break;
						break;
					case "delete":
					case "insert":
					case "share":
					case "unshare":
					case "setShare":
					case "moveToModule":
						//element is deleted
						if($eventName=="delete") $intro .= $this->getTranslationService()->t($p, "hasDeletedThoseElementsFromGroups")." ";
						//element is added in:
						if($eventName=="insert") $intro .= $this->getTranslationService()->t($p, "hasImportedThoseElementsInGroup")." ";
						//element is added in:
						if($eventName=="share") $intro .= $this->getTranslationService()->t($p, "hasAddedThoseElementsInGroup")." ";
						//element is removed in:
						if($eventName=="unshare") $intro .= $this->getTranslationService()->t($p, "hasRemovedThoseElementsFromGroup")." ";
						//element are setted in those groups
						if($eventName=="setShare") $intro .= $this->getTranslationService()->t($p, "hasSettedThoseElementsInGroups")." ";
						//element are moved in those groups
						if($eventName=="moveToModule") $intro .= $this->getTranslationService()->t($p, "hasMovedThoseElementsInModuleGroup")." ";

						$paths = $this->getGroupAdminService()->getGroupsPath($p, $gObj);
						if($eventName=="moveToModule") $intro .= $this->getTranslationService()->t($p, reset($gObj->getListIterator())->getDbEntity()->getModule()->getModuleName())." ".$this->getTranslationService()->t($p, "inGroup")." ";
						$first = true;
						foreach($paths as $groupId=>$path){
							if($first) $first = false;
							else $intro .= ", ";
							$intro .= '<a href="'.$this->getGroupAdminService()->getUrlForGroup($gObj->getItemInList($groupId)->getDbEntity()->getWigiiNamespace(), $gObj->getItemInList($groupId)->getDbEntity()->getModule(), $groupId).'">' .implode(" / ", $path).'/</a>';
						}
						break;
				}
				break;
			case "Element":
				//construct the subject
				switch ($eventName){
					case "insert":
						if($rec->isSubElement()){
							//new sub element in element
							$intro .= $this->getTranslationService()->t($p, "hasAddedAnElementToRootElement")." ";
							$subModule = $this->getTranslationService()->resetSubExecutionModule();
							$intro .= $this->getTranslationService()->t($p, "#thisElement# "); //the space at the end is important in order to not return the first #thisElement# translation, but to do the second round with the executionModule replacement
							$this->getTranslationService()->setSubExecutionModule($subModule);
						} else {
							//new element in:
							$paths = $this->getGroupAdminService()->getGroupsPath($p, $gObj); //array(0=>$gObj->getId()));
							$intro .= $this->getTranslationService()->t($p, "hasAddedAnElementInGroup")." ";
	//						$intro .= '<a href="'.$this->getGroupAdminService()->getUrlForGroup($gObj->getDbEntity()->getWigiiNamespace(), $gObj->getDbEntity()->getModule(), $gObj->getId()).'">' .implode(" / ", reset($paths)).'/</a>';
							$first = true;
							foreach($paths as $groupId=>$path){
								if($first) $first = false;
								else $intro .= ", ";
								$intro .= '<a href="'.$this->getGroupAdminService()->getUrlForGroup($gObj->getItemInList($groupId)->getDbEntity()->getWigiiNamespace(), $gObj->getItemInList($groupId)->getDbEntity()->getModule(), $groupId).'">' .implode(" / ", $path).'/</a>';
							}
						}
						break;
					case "update":
						if($rec->isSubElement()){
							//new sub element in element
							$intro .= $this->getTranslationService()->t($p, "hasUpdatedThisElementToRootElement")." ";
							$subModule = $this->getTranslationService()->resetSubExecutionModule();
							$intro .= $this->getTranslationService()->t($p, "#thisElement# "); //the space at the end is important in order to not return the first #thisElement# translation, but to do the second round with the executionModule replacement
							$this->getTranslationService()->setSubExecutionModule($subModule);
						} else {
							$intro .= $this->getTranslationService()->t($p, "hasUpdatedThisElement");
						}
						break;
					case "setState":
						if($rec->isSubElement()){
							//new sub element in element
							$intro .= $this->getTranslationService()->t($p, "hasChangedThisElementStateToRootElement")." ";
							$subModule = $this->getTranslationService()->resetSubExecutionModule();
							$intro .= $this->getTranslationService()->t($p, "#thisElement# "); //the space at the end is important in order to not return the first #thisElement# translation, but to do the second round with the executionModule replacement
							$this->getTranslationService()->setSubExecutionModule($subModule);
						} else {
							$intro .= $this->getTranslationService()->t($p, "hasChangedThisElementState");
						}
						break;
					case "delete":
						if($rec->isSubElement()){
							//new sub element in element
							$intro .= $this->getTranslationService()->t($p, "hasDeletedThisElementFromRootElement")." ";
							$subModule = $this->getTranslationService()->resetSubExecutionModule();
							$intro .= $this->getTranslationService()->t($p, "#thisElement# ");
							$this->getTranslationService()->setSubExecutionModule($subModule);
						} else {
							//element is deleted
							$intro .= $this->getTranslationService()->t($p, "hasDeletedThisElementFromGroups");
							$paths = $this->getGroupAdminService()->getGroupsPath($p, $gObj);
							$first = true;
							foreach($paths as $groupId=>$path){
								if($first) $first = false;
								else $intro .= ", ";
								$intro .= '<a href="'.$this->getGroupAdminService()->getUrlForGroup($gObj->getItemInList($groupId)->getDbEntity()->getWigiiNamespace(), $gObj->getItemInList($groupId)->getDbEntity()->getModule(), $groupId).'">' .implode(" / ", $path).'/</a>';
							}
						}
						break;
					case "restore":
						if($rec->isSubElement()){
							//new sub element in element
							$intro .= $this->getTranslationService()->t($p, "hasRestoredThisElementToRootElement")." ";
							$subModule = $this->getTranslationService()->resetSubExecutionModule();
							$intro .= $this->getTranslationService()->t($p, "#thisElement# ");
							$this->getTranslationService()->setSubExecutionModule($subModule);
						} else {
							//element is deleted
							$intro .= $this->getTranslationService()->t($p, "hasRestoredThisElementToGroups");
							$paths = $this->getGroupAdminService()->getGroupsPath($p, $gObj);
							$first = true;
							foreach($paths as $groupId=>$path){
								if($first) $first = false;
								else $intro .= ", ";
								$intro .= '<a href="'.$this->getGroupAdminService()->getUrlForGroup($gObj->getItemInList($groupId)->getDbEntity()->getWigiiNamespace(), $gObj->getItemInList($groupId)->getDbEntity()->getModule(), $groupId).'">' .implode(" / ", $path).'/</a>';
							}
						}
						break;
					case "share":
						//element is added in:
						$paths = $this->getGroupAdminService()->getGroupsPath($p, array(0=>$gObj->getId()));
						$intro .= $this->getTranslationService()->t($p, "hasAddedThisElementToGroup")." ";
						$intro .= '<a href="'.$this->getGroupAdminService()->getUrlForGroup($gObj->getDbEntity()->getWigiiNamespace(), $gObj->getDbEntity()->getModule(), $gObj->getId()).'">' .implode(" / ", reset($paths)).'/</a>';
						break;
					case "unshare":
						//element is moved out of:
						$paths = $this->getGroupAdminService()->getGroupsPath($p, array(0=>$gObj->getId()));
						$intro .= $this->getTranslationService()->t($p, "hasMovedThisElementOutOfGroup")." ";
						$intro .= '<a href="'.$this->getGroupAdminService()->getUrlForGroup($gObj->getDbEntity()->getWigiiNamespace(), $gObj->getDbEntity()->getModule(), $gObj->getId()).'">' .implode(" / ", reset($paths)).'/</a>';
						break;
				}
				break;
		}

		//create the element preview list
		$elementListText = "";

		$trm = $this->createTemplateRecordManagerInstance($rec);
		$trm->setOutputEnabled(false);

		if($entityName == "MultipleElement"){
			$fsl = $elementPList->getFieldSelectorList();
			foreach($elementPList->getListIterator() as $elementP){
				$elementListText .= '<p style="font-weight:bold;">';
				//add link to element:
				$elementListText .= $this->getButtonViewElement($p, $elementP->getElement(), '<u style="font-weight:normal;">'.$elementP->getId()."</u>")."  ";
				$elementListText .= $this->getElementView($p, $elementP->getElement(), $trm, $fsl);
				$elementListText .= '</p><p></p>';
			}
			//reset the record to the multiple record
			$trm->reset($rec);
		} else {
			$fsl = FieldSelectorListForActivity :: createInstance(false, false); //no issue if double time the same
			$fsl->setSelectedLanguages(array ($this->getTranslationService()->getLanguage() => $this->getTranslationService()->getLanguage()));					
			$elementListText .= '<p style="font-weight:bold;">';
			if($rec->isSubElement()){
				$this->getConfigService()->getFields($p, $this->getExecutionService()->getCrtModule(), Activity :: createInstance("listView"), $fsl);
				$elementListText .= $this->getElementView($p, $this->getRootElement($p, $rec), $trm, $fsl);
				$fsl->reset();
				$fsl->setSelectedLanguages(array ($this->getTranslationService()->getLanguage() => $this->getTranslationService()->getLanguage()));
				$this->getConfigService()->getFields($p, $rec->getModule(), Activity :: createInstance("listView"), $fsl);
				$elementListText .= '</p><p></p>';
				$this->getTranslationService()->setSubExecutionModule($rec->getModule());
				$elementListText .= $this->getTranslationService()->t($p, "#Element#:");				
				$elementListText .= '<br />';
				if($eventName=="delete") $elementListText .= $this->getButtonViewElement($p, $rec, '<u style="font-weight:normal;">'.$rec->getId()."</u><br/>")."  ";
				$elementListText .= $this->getElementView($p, $rec, $trm, $fsl);
			} else {
				$this->getConfigService()->getFields($p, $module, Activity :: createInstance("listView"), $fsl);
				if($eventName=="delete") $elementListText .= $this->getButtonViewElement($p, $rec, '<u style="font-weight:normal;">'.$rec->getId()."</u><br/>")."  ";
				$elementListText .= $this->getElementView($p, $rec, $trm, $fsl);
			}
			$elementListText .= '</p><p></p>';
		}

		//create the access button
		if($entityName=="Element" && ($eventName!="delete")){
			$accessButton = $this->getButtonViewElement($p, $rec);
		} else if($entityName=="Element" && $eventName=="delete" && $rec->isSubElement()){
			$subModule = $this->getTranslationService()->resetSubExecutionModule();
			$accessButton = $this->getButtonViewElement($p, $rootElement);
			$this->getTranslationService()->setSubExecutionModule($subModule);
		} else {
			if($gObj && is_a($gObj, "ObjectList")){
				$accessButton = "";
				$first = true;
				$usedWigiiNamespaceName = array();
				foreach($gObj->getListIterator() as $groupId=>$gItem){
					//prevent duplicating buttons on same areas
					if($usedWigiiNamespaceName[$gItem->getDbEntity()->getWigiiNamespace()->getWigiiNamespaceName()."-".$gItem->getDbEntity()->getModule()->getModuleName()]){
						continue;
					} else {
						if(!$first) $accessButton .= "<br />";
						else $first = false;
						$accessButton .= $this->getButtonAccess($p, $gItem->getDbEntity()->getWigiiNamespace(), $gItem->getDbEntity()->getModule());
						$usedWigiiNamespaceName[$gItem->getDbEntity()->getWigiiNamespace()->getWigiiNamespaceName()."-".$gItem->getDbEntity()->getModule()->getModuleName()] = 1;
					}
				}
			} else if($gObj) {
				$accessButton = $this->getButtonAccess($p, $gObj->getDbEntity()->getWigiiNamespace(), $gObj->getDbEntity()->getModule());
			} else {
				$accessButton = $this->getButtonAccess($p);
			}
		}

		//lookup if there is a displayContentInNotification and add it at the begining of the message:
		$displayInContent = "";
		if($entityName =="Element" && ($eventName=="update" || $eventName=="insert")){
			foreach($rec->getFieldList()->getListIterator() as $field){
				$fieldXml = $field->getXml();

				if($fieldXml["displayContentInNotification"]=="1" && $rec->getWigiiBag()->isFilled($field->getFieldName())){
					//special case for handling files
					if($field->getDataType() && $field->getDataType()->getDataTypeName()=="Files" && $fieldXml["htmlArea"]=="1"){
						$displayInContent .= '<div style="border-bottom:1px dotted #ccc;padding-bottom:20px;">';
						$displayInContent .= $trm->formatValueFromRecord($field->getFieldName(), "textContent", $rec);
						$displayInContent .= '</div>';
						$displayInContent .= '<p></p>';
					} else if($field->getDataType()) {

						$trm->displayLabel($field->getFieldName());
						$label = $trm->getHtmlAndClean();

						//if isInLine or is File increase the margins
						if($fieldXml["isInLine"]=="1"){
							$displayInContent .= '<p style="font-style:italic;padding:0px;margin:15px 0px 3px 0px;">';
						} else if($field->getDataType()->getDataTypeName()=="Files"){
							$displayInContent .= '<p style="font-style:italic;padding:0px;margin:15px 0px;">';
						} else {
							$displayInContent .= '<p style="font-style:italic;padding:0px;margin:3px 0px;">';
						}

						if(strstr($label, "<img")!==false){
							$displayInContent .= $this->getTranslationService()->t($p, $field->getFieldName(), $fieldXml).": ".$label." ";
						} else {
							$displayInContent .= $label.": ";
						}
						if($field->getDataType()->getDataTypeName()=="Blobs" || $field->getDataType()->getDataTypeName()=="Texts"){
							$trm->displayValue($field->getFieldName());
							$displayInContent .= "".$trm->getHtmlAndClean()."";
						} else if($field->getDataType()->getDataTypeName()=="Booleans"){
							$displayInContent .= $trm->formatValueFromRecord($field->getFieldName(), "value", $rec, false);
						} else {
							$trm->displayValue($field->getFieldName(),null,$fsl->getSelectedLanguages());
							$displayInContent .= "<b>".$trm->getHtmlAndClean()."</b>";
						}
						$displayInContent .= '</p>';
					}
				}
			}
		}

		//create the change list
		$changeList = "";
		if($eventName=="update"){
			if($entityName == "Element"){
				$changeList .= '<p>'.$this->getTranslationService()->t($p, "theFollowingChangesHasBeenMade").':</p>';
			}
			$fslForNotif = $options->getValue('fslForNotif');
			foreach($rec->getFieldList()->getListIterator() as $field){
				if($rec->getWigiiBag()->isChanged($field->getFieldName()) && !$rec->getWigiiBag()->isHidden($field->getFieldName())){
					$fieldXml = $field->getXml();

					if(isset($fslForNotif) && !$fslForNotif->containsFieldSelector($field->getFieldName())) continue;
					if($fieldXml["ignoreNotification"]=="1" && !isset($fslForNotif)) continue;

					$trm->displayLabel($field->getFieldName());
					$label = $trm->getHtmlAndClean();

					//if isInLine or is File increase the margins
					if($fieldXml["isInLine"]=="1"){
						$changeList .= '<p style="font-style:italic;padding:0px;margin:15px 0px 3px 0px;color:#f00;">';
					} else if($field->getDataType() && $field->getDataType()->getDataTypeName()=="Files"){
						$changeList .= '<p style="font-style:italic;padding:0px;margin:15px 0px;color:#f00;">';
					} else {
						$changeList .= '<p style="font-style:italic;padding:0px;margin:3px 0px;color:#f00;">';
					}

					if(strstr($label, "<img")!==false){
						$changeList .= $this->getTranslationService()->t($p, $field->getFieldName(), $fieldXml).": ".$label." ";
					} else {
						$changeList .= $label.": ";
					}

					if(!$rec->getWigiiBag()->isFilled($field->getFieldName())){
						//the field is now empty
						$changeList .= $this->getTranslationService()->t($p, "thisFieldHasBeenEmptied");
					} else {
						if($field->getDataType() && ($field->getDataType()->getDataTypeName()=="Blobs" || $field->getDataType()->getDataTypeName()=="Texts")){
							$trm->displayValue($field->getFieldName());
							$changeList .= "".$trm->getHtmlAndClean()."";
						} else if($field->getDataType() && $field->getDataType()->getDataTypeName()=="Booleans"){
							$changeList .= $trm->formatValueFromRecord($field->getFieldName(), "value", $rec, false);
						} else {
							$trm->displayValue($field->getFieldName(),null,$fsl->getSelectedLanguages());
							$changeList .= "<b>".$trm->getHtmlAndClean()."</b>";
						}
					}
					$changeList .= '</p>';
				}
			}
			$changeList .= '';
		}
		if($entityName == "Element" && $eventName=="setState"){
			$changeList .= '<p></p>';
			$state = $this->getExecutionService()->getCrtParameters(1);
			$changeList .= '<p>';
			if($rec->getAttribute(fs_e('state_'.$state))) {
				$changeList .= $this->getTranslationService()->t($p, "theFollowingStateHasBeenAdded").': ';
			}
			else {
				$changeList .= $this->getTranslationService()->t($p, "theFollowingStateHasBeenRemoved").': ';
			}
			$changeList .= $trm->doFormatForState($state, true);
			$changeList .= '</p>';
			$changeList .= '<p>'.$this->getTranslationService()->t($p, "theFollowingStatesAreSet").': ';
			$statuses = $this->getElementService()->getElementStateAsHtml($p, $rec);
			if(empty($statuses)) $changeList .= '-';
			else $changeList .= $statuses;
			$changeList .= '</p>';
		}
		if($entityName == "MultipleElement" && $eventName=="setState"){
			$changeList .= '<p></p>';
			$state = $this->getExecutionService()->getCrtParameters(1);
			$changeList .= '<p>';
			if($this->getExecutionService()->getCrtParameters(2)) {
				$changeList .= $this->getTranslationService()->t($p, "theFollowingStateHasBeenAdded").': ';
			}
			else {
				$changeList .= $this->getTranslationService()->t($p, "theFollowingStateHasBeenRemoved").': ';
			}
			$changeList .= $trm->doFormatForState($state, true);
		}

		//format all the pieces together
		$result = "";
		if(!empty($options->getValue('introduction'))) $result .= $options->getValue('introduction');
		$result .= $displayInContent;
		if(!($entityName =="Element" && ($eventName=="update" || $eventName=="insert") && $this->getConfigService()->getParameter($p, $module, "Notification_hideElementView")=="1")){
			$result .= "<p></p>";
			$timestamp = time();
			$dayOfWeek = date('w', $timestamp);
			if($dayOfWeek == 0) $dayOfWeek = 7;
			$dayOfWeek = $this->getTranslationService()->t($p, 'dayOfWeek_'.$dayOfWeek);
			$result .= '<p>'.$intro.' '
					.$this->getTranslationService()->t($p, 'date_on').' '.$dayOfWeek.' '.date('d.m.Y', $timestamp).' '.$this->getTranslationService()->t($p, 'date_at').' '.date('H:i', $timestamp)
					.' :</p>';
			if($entityName == "Element"){
				$result .= '<p></p>'.$elementListText.'';
				$result .= $changeList;
			} else {
				$result .= '<p></p>';
				if($changeList){
					$result .= $changeList.'';
					$result .= '<p></p><p style="text-align:center;border-top:1px dotted #ccc;padding-top:10px;"></p>';
				}
				$result .= $elementListText;
			}
		}
		if(!($entityName =="Element" && ($eventName=="update" || $eventName=="insert") && $this->getConfigService()->getParameter($p, $module, "Notification_hideAccessButton")=="1")){
			$result .= '<p></p>';
			$result .= '<div style="text-align:center;border-top:1px dotted #ccc;padding-top:10px;">';
				$result .= '<span style="color:#000;background-color:#ecf3fe;border:1px solid #aac9ff;text-decoration:none;"><font style="font-size:16px;">&nbsp;</font>'.$accessButton.'<font style="font-size:16px;">&nbsp;</font></span>';
			$reuslt .= '</div>';
		}
		$result .= '<p></p>';
		return $result;
	}
	protected function getShortSubject($p, $eventName, $entityName, $module){
		$transS = ServiceProvider::getTranslationService();
		$wigiiNamespace = $this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceName();
		// CWE 28.01.2019 translates wigii namespace using homePageNamespaceLabel if defined
		if($wigiiNamespace) {
			$customLabel = $transS->t($p, "homePageNamespaceLabel_".str_replace(' ', '%20', $wigiiNamespace));
			if($customLabel == "homePageNamespaceLabel_".str_replace(' ', '%20', $wigiiNamespace)) $customLabel = $wigiiNamespace;
		}
		return ($wigiiNamespace ? $customLabel.' - ' : "").$transS->t($p, $module->getModuleName()).': ';
	}
	public function getInitialSubject($p, $eventName, $entityName, $module, $rec, $gObj, $elementPList=null){
		// fetches root element and aligns configuration
	    if($rec && $rec->isSubElement()){
			$rootElement = $this->getRootElement($p, $rec);
		}
		
		if(!$p->getRealUser()){
			$username = $this->getTranslationService()->t($p, "systemPrincipal")." ";
		} else {
			$username = $p->getRealUser()->getUsername()." ";
		}
		
		switch($entityName){
			case "MultipleElement":
				$subject = $username." ";
				//construct the subject
				switch ($eventName){
					case "update":
						$subject .= $this->getTranslationService()->t($p, "hasUpdatedFollowingElements");
						break;
					case "setState":
						$subject .= $this->getTranslationService()->t($p, "hasStateChangedFollowingElements");
						break;
					case "delete":
						//element is deleted
						$subject .= $this->getTranslationService()->t($p, "hasDeletedThoseElements");
						break;
					case "insert":
					case "share":
					case "unshare":
					case "setShare":
					case "moveToModule":
						//element is added in:
						if($eventName=="insert") $subject .= $this->getTranslationService()->t($p, "hasImportedThoseElementsInGroup")." ";
						//element is added in:
						if($eventName=="share") $subject .= $this->getTranslationService()->t($p, "hasAddedThoseElementsInGroup")." ";
						//element is removed in:
						if($eventName=="unshare") $subject .= $this->getTranslationService()->t($p, "hasRemovedThoseElementsFromGroup")." ";
						//element are setted in those groups
						if($eventName=="setShare") $subject .= $this->getTranslationService()->t($p, "hasSettedThoseElementsInGroups")." ";
						//element are moved in those groups
						if($eventName=="moveToModule") $subject .= $this->getTranslationService()->t($p, "hasMovedThoseElementsInModuleGroup")." ";

						$paths = $this->getGroupAdminService()->getGroupsPath($p, $gObj);
						if($eventName=="moveToModule") $subject .= $this->getTranslationService()->t($p, reset($gObj->getListIterator())->getDbEntity()->getModule()->getModuleName())." ".$this->getTranslationService()->t($p, "inGroup")." ";
						$first = true;
						foreach($paths as $groupId=>$path){
							if($first) $first = false;
							else $subject .= ", ";
							$subject .= implode("/", $path);
						}
						break;
				}
				break;
			case "Element":
				if($eventName=="setState") $subject .= $this->getTranslationService()->t($p, "stateOfElement");
				else $subject = $this->getTranslationService()->t($p,"#Element# "); //the space at the end is important in order to not return the first #Element# translation, but to do the second round with the executionModule replacement
				$subject .= " (#".$rec->getId().") ";
				
				//construct the subject
				switch ($eventName){
					case "insert":
						$subject .= $this->getTranslationService()->t($p, "addedBy")." ".$username;
						if($rec->isSubElement()){
							//new sub element in element
							//$subject .= $this->getTranslationService()->t($p, "hasAddedAnElementToRootElement")." ";
							$subject .= $this->getTranslationService()->t($p, "toRootElement")." ";
							$subModule = $this->getTranslationService()->resetSubExecutionModule();
							$subject .= $this->getTranslationService()->t($p, "#thisElement# "); //the space at the end is important in order to not return the first #thisElement# translation, but to do the second round with the executionModule replacement
							$this->getTranslationService()->setSubExecutionModule($subModule);
						} else {
							//new element in:
							//$subject .= $this->getTranslationService()->t($p, "hasAddedAnElementInGroup")." ";
							$subject .= $this->getTranslationService()->t($p, "inGroup")." ";
							$paths = $this->getGroupAdminService()->getGroupsPath($p, $gObj);
							$first = true;
							foreach($paths as $path){
								if($first) $first = false;
								else $subject .= ", ";
								$subject .= implode("/", $path);
							}
						}
						break;
					case "update":
						$subject .= $this->getTranslationService()->t($p, "updatedBy")." ".$username;
						if($rec->isSubElement()){
							//update sub element in element
							//$subject .= $this->getTranslationService()->t($p, "hasUpdatedThisElementToRootElement")." ";
							$subject .= $this->getTranslationService()->t($p, "toRootElement")." ";
							$subModule = $this->getTranslationService()->resetSubExecutionModule();
							$subject .= $this->getTranslationService()->t($p, "#thisElement# "); //the space at the end is important in order to not return the first #thisElement# translation, but to do the second round with the executionModule replacement
							$this->getTranslationService()->setSubExecutionModule($subModule);
						} else {
							//$subject .= $this->getTranslationService()->t($p, "hasUpdatedThisElement");
						}
						break;
					case "setState":
						$subject .= $this->getTranslationService()->t($p, "setStateBy")." ".$username;
						if($rec->isSubElement()){
							//change state of sub element in element
							//$subject .= $this->getTranslationService()->t($p, "hasChangedThisElementStateToRootElement")." ";
							$subject .= $this->getTranslationService()->t($p, "toRootElement")." ";
							$subModule = $this->getTranslationService()->resetSubExecutionModule();
							$subject .= $this->getTranslationService()->t($p, "#thisElement# "); //the space at the end is important in order to not return the first #thisElement# translation, but to do the second round with the executionModule replacement
							$this->getTranslationService()->setSubExecutionModule($subModule);
						} else {
							$subject .= $this->getTranslationService()->t($p, "hasChangedThisElementState");
						}
						break;
					case "delete":
						$subject .= $this->getTranslationService()->t($p, "deletedBy")." ".$username;
						if($rec->isSubElement()){
							//delete sub element in element
							//$subject .= $this->getTranslationService()->t($p, "hasDeletedThisElementFromRootElement")." ";
							$subject .= $this->getTranslationService()->t($p, "toRootElement")." ";
							$subModule = $this->getTranslationService()->resetSubExecutionModule();
							$subject .= $this->getTranslationService()->t($p, "#thisElement# "); //the space at the end is important in order to not return the first #thisElement# translation, but to do the second round with the executionModule replacement
							$this->getTranslationService()->setSubExecutionModule($subModule);
						} else {
							//element is deleted
							//$subject .= $this->getTranslationService()->t($p, "hasDeletedThisElementFromGroups");
							$subject .= $this->getTranslationService()->t($p, "fromGroups")." ";
							$paths = $this->getGroupAdminService()->getGroupsPath($p, $gObj);
							$first = true;
							foreach($paths as $path){
								if($first) $first = false;
								else $subject .= ", ";
								$subject .= implode("/", $path);
							}
						}
						break;
					case "restore":
						$subject .= $this->getTranslationService()->t($p, "restoredBy")." ".$username;
						if($rec->isSubElement()){
							//restores sub element in element
							// when restoring sub elements, current module is parent module
						    $parentModule = $this->getTranslationService()->resetSubExecutionModule();
						    $this->getTranslationService()->setSubExecutionModule($rec->getModule());
						    $subject = $this->getTranslationService()->t($p,"#Element# "); //the space at the end is important in order to not return the first #Element# translation, but to do the second round with the executionModule replacement
						    $subject .= " (#".$rec->getId().") ";
						    $subject .= $this->getTranslationService()->t($p, "restoredBy")." ".$username;
						    
							$subject .= $this->getTranslationService()->t($p, "toRootElement")." ";
							//$subject .= $this->getTranslationService()->t($p, "hasRestoredThisElementToRootElement")." ";
							$subModule = $this->getTranslationService()->resetSubExecutionModule();
							$subject .= $this->getTranslationService()->t($p, "#thisElement# "); //the space at the end is important in order to not return the first #thisElement# translation, but to do the second round with the executionModule replacement
							$this->getTranslationService()->setSubExecutionModule($parentModule);
						} else {
							//element is deleted
							$subject .= $this->getTranslationService()->t($p, "toGroups")." ";
							//$subject .= $this->getTranslationService()->t($p, "hasRestoredThisElementToGroups");
							$paths = $this->getGroupAdminService()->getGroupsPath($p, $gObj);
							$first = true;
							foreach($paths as $path){
								if($first) $first = false;
								else $subject .= ", ";
								$subject .= implode("/", $path);
							}
						}
						break;
					case "share":
						$subject .= $this->getTranslationService()->t($p, "sharedBy")." ".$username;
						//element is added in:
						$paths = $this->getGroupAdminService()->getGroupsPath($p, array(0=>$gObj->getId()));
						$subject .= $this->getTranslationService()->t($p, "inGroup")." ";
						//$subject .= $this->getTranslationService()->t($p, "hasAddedThisElementToGroup")." ";
						$subject .= implode("/", reset($paths));
						break;
					case "unshare":
						$subject .= $this->getTranslationService()->t($p, "unsharedBy")." ".$username;
						//element is moved out of:
						$paths = $this->getGroupAdminService()->getGroupsPath($p, array(0=>$gObj->getId()));
						$subject .= $this->getTranslationService()->t($p, "fromGroup")." ";
						//$subject .= $this->getTranslationService()->t($p, "hasMovedThisElementOutOfGroup")." ";
						$subject .= implode("/", reset($paths));
						break;
				}
				break;
		}
		if($rec && $rec->isSubElement()){			
			$subject .= " (".substr($this->getShortSubject($p, "update", $entityName, $rootElement->getModule()),0,-2).")";
		} else {
			$subject .= " (".substr($this->getShortSubject($p, $eventName, $entityName, $module),0,-2).")";
		}
		return $subject;
	}
	protected function setSubject($p, $eventName, $entityName, $module, $mail, $rec, $gObj){
		$subject = $this->getNotificationSubjectInputValue();
		if(!$subject) $subject = $this->getInitialSubject($p, $eventName, $entityName, $module, $rec, $gObj);
		$mail->setSubject($subject);
	}
	protected function setSubjectForMultiple($p, $eventName, $entityName, $module, $mail, $elementPList, $gObj){
		$subject = $this->getNotificationSubjectInputValue();
		if(!$subject) $subject = $this->getInitialSubject($p, $eventName, $entityName, $module, $rec, $gObj);
		$mail->setSubject($subject);
	}
	protected function setBody($p, $eventName, $entityName, $module, $mail, $rec, $gObj){
		$body = $this->getHtml($p, $eventName, $entityName, $module, $rec, $gObj);
		$mail->setBodyHtml($body);
	}
	protected function setAttachements($p, $eventName, $entityName, $module, $mail, $rec, $gObj){
		//add file attachements if defined
		if($entityName =="Element" && ($eventName=="update" || $eventName=="insert")){
			$trm = $this->createTemplateRecordManagerInstance($rec);
			foreach($rec->getFieldList()->getListIterator() as $field){
				$fieldXml = $field->getXml();
				if($fieldXml["attachContentInNotification"]=="1" && $field->getDataType() && $field->getDataType()->getDataTypeName()=="Files" && $rec->getWigiiBag()->isFilled($field->getFieldName())){
					//if the file is an htmlArea
					if($fieldXml["htmlArea"]=="1"){
						$path = time()."_".$rec->getFieldValue($field->getFieldName(), "username")."_".$rec->getFieldValue($field->getFieldName(), "name").$rec->getFieldValue($field->getFieldName(), "type");
						file_put_contents(FILES_PATH."emailAttachement_".$path, $trm->wrapHtmlFilesWithHeader(stripslashes($rec->getFieldValue($field->getFieldName(), "textContent")),$rec->getFieldValue($field->getFieldName(), "name").$rec->getFieldValue($field->getFieldName(), "type")));
						//delete the file in this case, as the file is created just for the attachement.
						$mail->createAttachment(
							FILES_PATH."emailAttachement_".$path, true,
							WigiiEmailMime::TYPE_OCTETSTREAM,
							WigiiEmailMime::DISPOSITION_ATTACHMENT,
							WigiiEmailMime::ENCODING_BASE64,
							stripslashes($rec->getFieldValue($field->getFieldName(), "name")).$rec->getFieldValue($field->getFieldName(), "type")
							);
					} else {
						//if the file has changed (TEMPORARYUPLOADEDFILE_path)
						$path = $rec->getFieldValue($field->getFieldName(), "path");
						// CWE 16.09.2016 : skips attachements of Files located on Box
						if(strstr($path, "box://")) continue;
						//do not delete the file after send, as it is a stored file
						$mail->createAttachment(
						    resolveFilePath($path), false,
							WigiiEmailMime::TYPE_OCTETSTREAM,
							WigiiEmailMime::DISPOSITION_ATTACHMENT,
							WigiiEmailMime::ENCODING_BASE64,
							stripslashes($rec->getFieldValue($field->getFieldName(), "name")).$rec->getFieldValue($field->getFieldName(), "type")
							);
					}
				}
			}
		}

		$body = $this->getHtml($p, $eventName, $entityName, $module, $rec, $gObj);
		$mail->setBodyHtml($body);
	}
	protected function setBodyForMultiple($p, $eventName, $entityName, $module, $mail, $elementPList, $rec, $gObj, $additionalRowInfo){
		$body = $this->getHtmlForMultiple($p, $eventName, $entityName, $module, $elementPList, $rec, $gObj, $additionalRowInfo);
		$mail->setBodyHtml($body);
	}

	//if blocked, then the Notification service is not looking if user has defined a custom notification
	//the system will then do a standard notification
	//this is usefull for i.e. when we want to notify for autoSharing without taking in consideration
	//the custom notification that the user could have define for the notification about the update event.
	public function isNotificationPostingValueBlocked(){
		return $this->isNotificationPostingValueBlocked;
	}
	public function blockNotificationPostingValue(){
		$this->isNotificationPostingValueBlocked = true;
	}
	public function unblockNotificationPostingValue(){
		$this->isNotificationPostingValueBlocked = false;
	}
	public function getNotificationMessageInputName(){
		return "XXX_notificationMessageInput_XXX";
	}
	public function getNotificationMessageInputValue(){
		if($this->isNotificationPostingValueBlocked()) return null;
		if($_POST[$this->getNotificationMessageInputName()] == null) return null;
		return stripslashes($_POST[$this->getNotificationMessageInputName()]);
	}
	public function getNotificationEmailInputName(){
		return "XXX_notificationEmailInput_XXX";
	}
	public function getNotificationEmailInputValue(){
		if($this->isNotificationPostingValueBlocked()) return null;
		if($_POST[$this->getNotificationEmailInputName()] == null) return null;
		return stripslashes($_POST[$this->getNotificationEmailInputName()]);
	}
	//for now not yet used
	public function getNotificationToInputName(){
		return "XXX_notificationToInput_XXX";
	}
	//for now not yet used
	public function getNotificationToInputValue(){
		if($this->isNotificationPostingValueBlocked()) return null;
		if($_POST[$this->getNotificationToInputName()] == null) return null;
		return stripslashes($_POST[$this->getNotificationToInputName()]);
	}
	public function getNotificationSubjectInputName(){
		return "XXX_notificationSubjectInput_XXX";
	}
	public function getNotificationSubjectInputValue(){
		if($this->isNotificationPostingValueBlocked()) return null;
		if($_POST[$this->getNotificationSubjectInputName()] == null) return null;
		return stripslashes($_POST[$this->getNotificationSubjectInputName()]);
	}
	public function displayNotificationForm($p, $eventName, $entityName, $module, $rec, $gObj, $elementPList=null){
		$transS = $this->getTranslationService();
		$totalWidth = 500;
		$labelWidth = 100; //$this->getLabelWidth(); the notification form labels are only: From and Subject
		$valueWidth = $totalWidth-$labelWidth;
		//if there is already values, that means an error occurs in the email validation
		if($this->getNotificationEmailInputValue()){
			//if the field is already filled, that means an error occurs, display invalidEmail message
			?><div class="label" style="width: 100%; max-width:<?=$totalWidth;?>px; clear:left;" ><?
				?><img class="icon" src="<?=SITE_ROOT_forFileUrl;?>images/icones/tango/22x22/emblems/emblem-unreadable.png" /><?
				?><label class="R"><?=$this->getTranslationService()->t($p, "invalidEmailFromOrTo");?></label><?
			?></div><?
		}
		//20 is about label padding
		?><div class="label" style="width: 100%; max-width:<?=$labelWidth-20;?>px; clear:left; " ><?
			?><label for="id<?=$this->getNotificationEmailInputName();?>" ><?=$this->getTranslationService()->t($p, "addNotificationEmailFrom");?></label></div><?
		?><div class="value" style="width: 100%; max-width:<?=$valueWidth;?>px;"  ><?
			?><input id="id<?=$this->getNotificationEmailInputName();?>" name="<?=$this->getNotificationEmailInputName();?>" class="" style="width: 100%; max-width:<?=$valueWidth-5;?>px;" value="<?=($this->getNotificationEmailInputValue() ? $this->getNotificationEmailInputValue() : $p->getValueInGeneralContext("email"));?>" /><?
		?></div><?
		//20 is about label padding
		?><div class="label" style="width: 100%; max-width:<?=$labelWidth-20;?>px; clear:left; " ><?
			?><label for="id<?=$this->getNotificationToInputName();?>" ><?=$this->getTranslationService()->t($p, "addNotificationEmailTo");?></label></div><?
		?><div class="value" style="width: 100%; max-width:<?=$valueWidth;?>px;"  ><?
			if($this->getNotificationToInputValue()){
				$recipients = $this->getNotificationToInputValue();
			} else {
				$value2arrayMapper = $this->createValueListArrayMapperForEmailNotification();
				$this->getRecipientsFromRecAndGroups($p, $eventName, $entityName, $value2arrayMapper, ($elementPList ? $elementPList : $rec), $gObj);
				if($value2arrayMapper->getListIterator()!=null){
					$recipients = implode(", ", $value2arrayMapper->getListIterator());
				} else {
					$recipients = null;
				}
			}
			?><textarea class="difH noElastic" id="id<?=$this->getNotificationToInputName();?>" name="<?=$this->getNotificationToInputName();?>" style="width: 100%; max-width:<?=$valueWidth-5;?>px;height:50px;" ><?=$recipients;?></textarea><?
		?></div><?
		//20 is about label padding
		?><div class="label" style="width: 100%; max-width:<?=$labelWidth-20?>px; clear:left; " ><?
			//no error is possible here
			?><label for="id<?=$this->getNotificationSubjectInputName();?>" ><?=$this->getTranslationService()->t($p, "addNotificationEmailSubject");?></label></div><?
		?><div class="value" style="width: 100%; max-width:<?=$valueWidth;?>px;" ><?
			?><input id="id<?=$this->getNotificationSubjectInputName();?>" name="<?=$this->getNotificationSubjectInputName();?>" class="" style="width: 100%; max-width:<?=$valueWidth-5;?>px;" value="<?=($this->getNotificationSubjectInputValue() ? $this->getNotificationSubjectInputValue() : $this->getInitialSubject($p, $eventName, $entityName, $module, $rec, $gObj));?>" /><?
		?></div><?
		//add file attachements if defined
		if($entityName =="Element" && ($eventName=="update" || $eventName=="insert")){
			$trm = $this->createTemplateRecordManagerInstance($rec);
			$trm->setOutputEnabled(true);
			echo '<p style="font-style:italic;padding:0px;margin:15px 0px;">';
			foreach($rec->getFieldList()->getListIterator() as $field){
				$fieldXml = $field->getXml();
				if($fieldXml["attachContentInNotification"]=="1" && $field->getDataType() && $field->getDataType()->getDataTypeName()=="Files" && $rec->getWigiiBag()->isFilled($field->getFieldName())){
					// CWE 16.09.2016 : skips attachements of Files located on Box
					if(strstr($rec->getFieldValue($field->getFieldName(),'path'), "box://")) continue;
					// Else shows attachements
					$trm->displayLabel($field->getFieldName());
					echo $trm->formatValueFromRecord($field->getFieldName(), "name", $rec);
					echo $trm->formatValueFromRecord($field->getFieldName(), "type", $rec);
					echo " (".$trm->formatValueFromRecord($field->getFieldName(), "size", $rec).") ";
				}
			}
			echo '</p>';
		}
		?><div class="clear"></div><?
		?><div class="value" style="width: 100%; max-width:<?=$totalWidth;?>px; clear:left; " ><?
			?><textarea id="id<?=$this->getNotificationMessageInputName();?>" name="<?=$this->getNotificationMessageInputName();?>" class="htmlArea" style="width: 100%; max-width:<?=$totalWidth;?>px;" ><?=($this->getNotificationMessageInputValue() ? $this->getNotificationMessageInputValue() : $this->getInitialMessageContent($p, $eventName, $entityName, $module, $rec, $gObj, $elementPList));?></textarea><?
		?></div><?
	}
	protected function displayNotificationMessage($p, $eventName, $entityName, $module, $rec, $gObj, $elementPList=null){
		$val = $this->getNotificationMessageInputValue();

		if($val!=null){
			//replace empty message with null
			if(strtolower(trim(str_replace("\n", "", $val)))==="<br />") $val = null;
		}
		if($val == null) $val = $this->getInitialMessageContent($p, $eventName, $entityName, $module, $rec, $gObj, $elementPList);

		if($val != null){
			echo '<p>&nbsp;</p>';
			echo $val;
			echo '<p>&nbsp;</p>';
		}
	}
	protected function displayInitialMessageContent($p, $eventName, $entityName, $module, $rec, $gObj, $elementPList=null){
		$val = $this->getInitialMessageContent($p, $eventName, $entityName, $module, $rec, $gObj, $elementPList);
		if($val != null){
			?><p><?=$val;?></p><?
		}
	}
	protected function getButtonViewElement($p, $rec, $label = null, $wigiiNamespace=null, $module=null){
	    $options = $this->htmlRenderingOptions;/* gets some extra options to customize the html rendering process */
	    if(!isset($options)) $options = wigiiBPLParam();
	    
	    if(!$wigiiNamespace) $wigiiNamespace = $this->getExecutionService()->getCrtWigiiNamespace();
		if(!$module){
			if($rec->isSubElement()){
				$module = $this->getExecutionService()->getCrtModule(); //like this it works with subElement or not
			} else {
				$module = $rec->getModule();
			}
		}
		if(!$label) $label = $this->getTranslationService()->t($p, "viewElement");
		$result = "";
		$result .= '<a href="'.$this->getElementService()->getUrlForElement($wigiiNamespace, $module, $rec, false, $options->getValue('targetFolder')).'" target="_blank" style="color:#000;text-decoration:none;">';
		$result .= $label;
		$result .= '</a>';
		return $result;
	}
	protected function getButtonAccess($p, $wigiiNamespace=null, $module=null){
		$transS = ServiceProvider::getTranslationService();
		if(!$wigiiNamespace) $wigiiNamespace = $this->getExecutionService()->getCrtWigiiNamespace();
		if(!$module) $module = $this->getExecutionService()->getCrtModule();
		// CWE 28.01.2019 translates wigii namespace using homePageNamespaceLabel if defined
		if($wigiiNamespace && $wigiiNamespace->getWigiiNamespaceName()) {
			$customLabel = $transS->t($p, "homePageNamespaceLabel_".$wigiiNamespace->getWigiiNamespaceUrl());
			if($customLabel == "homePageNamespaceLabel_".$wigiiNamespace->getWigiiNamespaceUrl()) $customLabel = $wigiiNamespace->getWigiiNamespaceName();
		}
		$result = "";
		$result .= '<a href="'.SITE_ROOT."#".$wigiiNamespace->getWigiiNamespaceUrl()."/".$module->getModuleUrl().'" target="_blank" style="color:#000;text-decoration:none;">';
		$result .= $transS->t($p, "accessSystem")." ".($wigiiNamespace && $wigiiNamespace->getWigiiNamespaceName() ? $customLabel.' - ' : "").$transS->t($p, $module->getModuleName());
		$result .= '</a>';
		return $result;
	}
	/**
	 * Generates the HTML of the notification message
	 * @param WigiiBPLParameter $options an optional bag of parameters to customize the HTML generation process
	 * @return string
	 */
	public function getHtml($p, $eventName, $entityName, $module, $rec, $gObj, $options=null){
		//in insert, the form is defined as empty, so everything will be changed of course
		//we don't want
		if($eventName == "insert") $rec->getWigiiBag()->resetChanges();

		$trm = $this->createTemplateRecordManagerInstance($rec);
		$trm->setP($p);
//		$this->getDetailRenderer($trm)->setP($p);

		$templatePath = $this->getConfigService()->getTemplatePath($p, $module, $this->getActivity());
		$exec = $this->getExecutionService();
		$this->htmlRenderingOptions = $options;
		ob_start();
		include($templatePath);
		$body = ob_get_clean();
        $this->htmlRenderingOptions = null;
        
		//check if there is any link on the element id which was not set correctly before in the case of add:
		if(strpos($body, '/item/"')!==false) $body = str_replace('/item/"', '/item/'.$rec->getId().'"', $body);

		return $body;
	}
	private $htmlRenderingOptions = null;
	
	public function getHtmlForMultiple($p, $eventName, $entityName, $module, $elementPList, $rec, $gObj, $additionalRowInfo=null){

		$trm = $this->createTemplateRecordManagerInstance(null);
		$trm->setP($p);
//		$this->getDetailRenderer($trm)->setP($p);

		$templatePath = $this->getConfigService()->getTemplatePath($p, $module, $this->getActivity());
		$exec = $this->getExecutionService();
		ob_start();
		include($templatePath);
		$body = ob_get_clean();
		return $body;
	}
	protected function getTechnicalInfo($p){
		ob_start();
		?><div style="font-size:9px;color:#fff;border:none;"><?
		$this->getExecutionService()->displayTechnicalInfo($p);
		?></div><?
		return ob_get_clean();
	}

	protected function setFrom($p, $mail){
		$from = (string)$this->getConfigService()->getParameter($p, null, "emailNotificationFrom");
		$fromEmail = $this->getNotificationEmailInputValue();
		if($fromEmail != null){
			//validation needs to be already done in doSpecific check
			$from = $fromEmail;
			$mail->addBcc($fromEmail);
		}
		if(!$p->getValueInGeneralContext("email") && $from!=(string)$this->getConfigService()->getParameter($p, null, "emailNotificationFrom")){
			$p->setValueInGeneralContext("email", $from);
		}
		$mail->setFrom($from);
		return $from;
	}

	protected function createFieldSelectorInstanceForEmailNotification(){
		$r = FieldSelector::createInstance('emailNotification');
		return $r;
	}
	protected function createValueListArrayMapperForEmailNotification(){
		return TechnicalServiceProvider::getValueListArrayMapper(true, ValueListArrayMapper::Natural_Separators);
	}
	protected function getRecipientsFromElementAndGroups($p, $eventName, $entityName, $value2arrayMapper, $rec, $gObj=null){
		$fs = $this->createFieldSelectorInstanceForEmailNotification();

		//update all groups on update or setState
		//moveToModule don't has the original groups to notify the leaver...
		if($entityName == "Element" && (
			$eventName == "update" ||
			$eventName == "setState" ||
			$rec->isSubElement() //if subItem, the event for the root is as an update
			)) {

//			fput("groups containing elements ".$eventName);

			$excludeGroups = (string) $this->getConfigService()->getParameter($p, $this->getExecutionService()->getCrtModule(), "Notification_excludeGroupsInUpdateOrSetState");
			$excludeOtherWigiiNamespace = !((string) $this->getConfigService()->getParameter($p, $this->getExecutionService()->getCrtModule(), "Notification_includeOtherNamespaceInUpdateOrSetState") == "1");

			//filter the notification groups on current wigiiNamespace
			$gExp = null;
			$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
			if($excludeOtherWigiiNamespace && $gObj){
				//if unique gObj, compare with gObj wigiiNamespace
				if(is_a($gObj, "ObjectList")){
					if($gObj->count()==1){
						$gExp .= 'wigiiNamespace = "'.reset($gObj->getListIterator())->getDbEntity()->getWigiiNamespace()->getWigiiNamespaceName().'"';
					} else {
						$gExp .= 'wigiiNamespace = "'.$this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceName().'"';
					}
				} else {
					$gExp .= 'wigiiNamespace = "'.$gObj->getDbEntity()->getWigiiNamespace()->getWigiiNamespaceName().'"';
				}
			} elseif($excludeOtherWigiiNamespace) {
				$gExp .= 'wigiiNamespace = "'.$this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceName().'"';
			}
			if($excludeGroups!=null) $gExp .= ($gExp ? ' AND' : '').' id NOTIN('.$excludeGroups.')';
			if($gExp) $gExp = $parser->createLogExpFromString($gExp);
			$this->getElementService()->getFieldFromGroupsContainingElement(
				$this->getRootPrincipal(),
				$fs,
				($rec->isSubElement() ? $this->getRootElement($p, $rec) : $rec), 1, //add parents
				$value2arrayMapper, $gExp);

		} else if($gObj && $entityName == "Element"){
			if(is_a($gObj, "ObjectList")){
				$this->getGroupAdminService()->getGroupsField(
					$this->getRootPrincipal(),
					$fs,
					$gObj,
					1, //add parents
					$value2arrayMapper
					);
			} else if($gObj){
				$gExp = 'id = '.$gObj->getId();
				$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
				$gExp = $parser->createLogExpFromString($gExp);
				$this->getGroupAdminService()->getSelectedGroupsField(
					$this->getRootPrincipal(),
					$fs,
					$gExp, 1, //add parents
					$value2arrayMapper);
			}
		}

		//Add emails from fieldList
		//the hasGroupEmailNotification is not used in this case becase in Notification Activity we already
		//send the notification to GroupsContaining record
		$hasGroupEmailNotification = $rec->getLinkedEmailInRecord($p, $value2arrayMapper);
		if($rec && $rec->isSubElement()) $hasGroupEmailNotification = $this->getRootElement($p, $rec)->getLinkedEmailInRecord($p, $value2arrayMapper) || $hasGroupEmailNotification;

		return $hasGroupEmailNotification;
	}
	protected function getRecipientsFromMultipleAndGroups($p, $eventName, $entityName, $value2arrayMapper, $elementPList, $gObj=null){
		$fs = $this->createFieldSelectorInstanceForEmailNotification();

		//update all groups on update or setState
		//moveToModule don't has the original groups to notify the leaver...
		if($entityName == "MultipleElement" && (
			$eventName == "update" ||
			$eventName == "setState"
			)) {

			$excludeGroups = (string) $this->getConfigService()->getParameter($p, $this->getExecutionService()->getCrtModule(), "Notification_excludeGroupsInUpdateOrSetState");
			$excludeOtherWigiiNamespace = !((string) $this->getConfigService()->getParameter($p, $this->getExecutionService()->getCrtModule(), "Notification_includeOtherNamespaceInUpdateOrSetState") == "1");

//			fput("groups containing elements ".$eventName);
			$gExp = null;
			$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
			if($excludeOtherWigiiNamespace && $gObj){
				//if unique gObj, compare with gObj wigiiNamespace
				if(is_a($gObj, "ObjectList") && $gObj->count()==1){
					$gExp .= 'wigiiNamespace = "'.reset($gObj->getListIterator())->getDbEntity()->getWigiiNamespace()->getWigiiNamespaceName().'"';
				} else {
					$gExp .= 'wigiiNamespace = "'.$gObj->getDbEntity()->getWigiiNamespace()->getWigiiNamespaceName().'"';
				}
			} elseif($excludeOtherWigiiNamespace) {
				$gExp .= 'wigiiNamespace = "'.$this->getExecutionService()->getCrtWigiiNamespace()->getWigiiNamespaceName().'"';
			}
			if($excludeGroups!=null) $gExp .= ($gExp ? ' AND' : '').' id NOTIN('.$excludeGroups.')';
			if($gExp) $gExp = $parser->createLogExpFromString($gExp);
			$this->getElementService()->getFieldFromGroupsContainingElements(
				$this->getRootPrincipal(),
				$fs,
				$elementPList, 1, //add parents
				$value2arrayMapper, $gExp);

		} else if($gObj && $entityName == "MultipleElement"){
			if(is_a($gObj, "ObjectList")){
				$this->getGroupAdminService()->getGroupsField(
					$this->getRootPrincipal(),
					$fs,
					$gObj,
					1, //add parents
					$value2arrayMapper
					);
			} else if($gObj){
				$gExp = 'id = '.$gObj->getId();
				$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
				$gExp = $parser->createLogExpFromString($gExp);
				$this->getGroupAdminService()->getSelectedGroupsField(
					$this->getRootPrincipal(),
					$fs,
					$gExp, 1, //add parents
					$value2arrayMapper);
			}
		}

		//Add emails from fieldList
		//the hasGroupEmailNotification is not used in this case becase in Notification Activity we already
		//send the notification to GroupsContaining record
		foreach($elementPList->getListIterator() as $elementP){
			$hasGroupEmailNotification = $elementP->getElement()->getLinkedEmailInRecord($p, $value2arrayMapper);
		}

		return $hasGroupEmailNotification;
	}
	protected function getRecipientsFromRecAndGroups($p, $eventName, $entityName, $value2arrayMapper, $rec, $gObj=null){
		if($entityName == "MultipleElement"){
			$this->getRecipientsFromMultipleAndGroups($p, $eventName, $entityName, $value2arrayMapper, $rec, $gObj);
		} else {
			$this->getRecipientsFromElementAndGroups($p, $eventName, $entityName, $value2arrayMapper, $rec, $gObj);
		}
	}
	/**
	 * @param mixed $gObj can be Group, GroupList, depending on $eventName and entityName
	 */
	protected function addRecipients($p, $eventName, $entityName, $mail, $rec, $gObj=null){
		$mail->clearRecipients();
		$pEmail = $p->getValueInGeneralContext("email");
		$exculdePEmail = $this->getConfigService()->getParameter($p, $rec->getModule(), "Notification_includeSenderInNotification")!="1";
		if(!$exculdePEmail) $mail->addTo($mail->getFrom());

		$value2arrayMapper = $this->createValueListArrayMapperForEmailNotification();
		if($this->getNotificationToInputValue()){
			$value2arrayMapper->addValue($this->getNotificationToInputValue());
		} else {
			$this->getRecipientsFromRecAndGroups($p, $eventName, $entityName, $value2arrayMapper, $rec, $gObj);
		}

		if($value2arrayMapper->count() == null){
			throw new NotificationServiceException('no email for Notification found', NotificationServiceException::NO_EMAIL_FOR_NOTIFICATION_FOUND);
		}

		foreach($value2arrayMapper->getListIterator() as $email){
			if($exculdePEmail && $email == $pEmail) continue; //do not notify the person it self
			$mail->addBcc($email);
		}

		TechnicalServiceProvider::recycleValueListArrayMapper($value2arrayMapper);
	}
	protected function addRecipientsForMultiple($p, $eventName, $entityName, $mail, $elementPList, $gObj=null){
		$mail->clearRecipients();
		$pEmail = $p->getValueInGeneralContext("email");
		$exculdePEmail = $this->getConfigService()->getParameter($p, $this->getExecutionService()->getCrtModule(), "Notification_includeSenderInNotification")!="1";
		if(!$exculdePEmail) $mail->addTo($mail->getFrom());

		$value2arrayMapper = $this->createValueListArrayMapperForEmailNotification();
		if($this->getNotificationToInputValue()){
			$value2arrayMapper->addValue($this->getNotificationToInputValue());
		} else {
			$this->getRecipientsFromRecAndGroups($p, $eventName, $entityName, $value2arrayMapper, $elementPList, $gObj);
		}

		if($value2arrayMapper->count() == null){
			throw new NotificationServiceException('no email for Notification found', NotificationServiceException::NO_EMAIL_FOR_NOTIFICATION_FOUND);
		}

		//eput($value2arrayMapper->getListIterator());

		foreach($value2arrayMapper->getListIterator() as $email){
			if($exculdePEmail && $email == $pEmail) continue; //do not notify the person it self
			$mail->addBcc($email);
		}

		TechnicalServiceProvider::recycleValueListArrayMapper($value2arrayMapper);

	}

	private $currentTrm;
	protected function getTrm($record){
		if(!isset($this->currentTrm)){
			$this->currentTrm = $this->createTemplateRecordManagerInstance($record);
		}
		$this->currentTrm->reset($record);
		return $this->currentTrm;
	}
	protected function createTemplateRecordManagerInstance($record){
		$trm = TemplateRecordManager::createInstance(true);
		$trm->setConfigService($this->getConfigService());
		$trm->setTranslationService($this->getTranslationService());
		$trm->reset($record);
		return $trm;
	}

	private $activity;
	protected function getActivity(){
		if(!isset($this->activity)){
			$this->activity = $this->createActivityInstance();
		}
		return $this->activity;
	}
	protected function createActivityInstance(){
		$r = Activity::createInstance("Notification");
		return $r;
	}

}


