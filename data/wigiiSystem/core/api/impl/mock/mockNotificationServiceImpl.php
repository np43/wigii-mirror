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
 * Mock NotificationService implementation 
 * which stores notifications into a wigii folder that has the following fields:
 * <date type="Dates" includeTime="1"/>
 * <event type="Strings"/>
 * <elementIds type="Blobs" noWrap="1"/>
 * <recipients type="Emails" isMultiple="1"/>
 * <content type="Blobs" htmlArea="1" />
 * 
 * Created by CWE on Friday 10.10.2014
 */
 class mockNotificationServiceImpl extends NotificationService {

	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("mockNotificationServiceImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("mockNotificationServiceImpl");
		}
		return $this->_executionSink;
	}
	
	// Configuration
	
	private $groupId;
	/**
	 * Sets the group id in which to store the notifications
	 */
	public function setGroupId($groupId)  {
		$this->groupId = $groupId;
	}
	protected function getGroupId() {
		return $this->groupId;
	}
	
	// Implementation
	
	public function event($eventName, $entityName, $module, $object) {
		$this->mail = null;
		parent::event($eventName, $entityName, $module, $object);
		if(isset($this->mail)) {
			$this->executionSink()->publishStartOperation('event');
			ServiceProvider::getDataFlowService()->processDataSource($this->getRootPrincipal(), newElement($this->getGroupId()), dfasl(
				dfas('ElementSetterDFA',
					'setCalculatedFieldSelectorMap', cfsMap(
					cfs('date', date('Y-m-d H:i:s')),
					cfs('event', $entityName.'::'.$eventName),
					cfs('elementIds', $this->getElementIds($eventName, $entityName, $module, $object)),
					cfs('recipients', $this->recipients),
					cfs('content', $this->mail->getBodyHtmlForDb()))
				),
				dfas('ElementDFA', 'setMode', '1')	
			), true, false); /* explicitely prevents events to popup to avoid loops */
			$this->executionSink()->publishEndOperation('event');
		}
	}
	
	private $mail;
	private $recipients;
	protected function addRecipients($p, $eventName, $entityName, $mail, $rec, $gObj=null){
		$this->mail = $mail;
		parent::addRecipients($p, $eventName, $entityName, $mail, $rec, $gObj);
		$this->getRecipients();
	}
	protected function addRecipientsForMultiple($p, $eventName, $entityName, $mail, $elementPList, $gObj=null){
		$this->mail = $mail;
		parent::addRecipientsForMultiple($p, $eventName, $entityName, $mail, $elementPList, $gObj);
		$this->getRecipients();
	}
	
	
	private function getRecipients() {
		if($this->mail->hasRecipients()) {
			$this->recipients = array();
			$rec = $this->mail->getToForDb();
			if($rec) $this->recipients = array_merge($this->recipients, $rec);
			$rec = $this->mail->getCcForDb();
			if($rec) $this->recipients = array_merge($this->recipients, $rec);
			$rec = $this->mail->getBccForDb();
			if($rec) $this->recipients = array_merge($this->recipients, $rec);
			if(!empty($this->recipients)) $this->recipients = implode(', ', array_keys($this->recipients));
			else $this->recipients = null;
		}
		else $this->recipients = null;
	}
	private function getElementIds($eventName, $entityName, $module, $object) {
		$elementIds = null;
		if($entityName == 'MultipleElement') {
			$elementPList = $object->getElementPList();
			if($elementPList instanceof ElementPAdvancedList) {
				$elementIds = $elementPList->getElementIds();
				if(!empty($elementIds)) $elementIds = implode(', ', $elementIds);
				else $elementIds = null;
			}
			elseif($elementPList instanceof ElementPList) {
				if(!$elementPList->isEmpty()) {
					foreach($elementPList->getListIterator() as $elementP) {
						if(!isset($elementIds)) $elementIds = $elementP->getId();
						else $elementIds .= ', '.$elementP->getId();
					}
				}
			}
		}
		else {
			$rec = null;
			if(method_exists($object, "getElement")){
				$rec = $object->getElement();
			} else if(method_exists($object, "getElementP")){
				$rec = $object->getElementP()->getElement();
			} else if(method_exists($object, "getRecord")){
				$rec = $object->getRecord();
			}
			if(isset($rec)) $elementIds = $rec->getId();
		}
		return $elementIds;
	}
}


