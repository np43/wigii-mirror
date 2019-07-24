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
 * Mock EmailService implementation 
 * which stores emails into a wigii folder that has the following fields :
 * <date type="Dates" includeTime="1"/>
 * <from type="Emails"/>
 * <replyTo type="Emails"/>
 * <to type="Emails" isMultiple="1"/>
 * <cc type="Emails" isMultiple="1"/>
 * <bcc type="Emails" isMultiple="1"/>
 * <recipients type="Emails" hidden="1" isMultiple="1"/>
 * <subject type="Strings"/>
 * <content type="Blobs" htmlArea="1"/>
 * 
 * Created by CWE on Friday 10.10.2014
 */
class mockEmailServiceWebImpl extends EmailServiceWebImpl {

	private $_debugLogger;
	private $_executionSink;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("mockEmailServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("mockEmailServiceWebImpl");
		}
		return $this->_executionSink;
	}
	
	// Configuration
	
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
	
	private $groupId;
	/**
	 * Sets the group id in which to store the notifications
	 */
	public function setGroupId($groupId)  {
		$this->groupId = $groupId;
		return $this;
	}
	protected function getGroupId() {
		return $this->groupId;
	}
	
	// Implementation
		
	protected function persistEmailsAttachementToDelete($principal, $email, $nb) {
		/* nothing to do */
	}
	
	protected function persistEmail($principal, $email, $status) {
		$this->persistEmails($principal, $email, $status);
	}
	
	protected function persistEmails($principal, $emails, $status){
		$this->executionSink()->publishStartOperation('persistEmails', $principal);
		$returnValue = 0;
		try {
			if(!empty($emails)) {
			    $rootP = $this->getRootPrincipal();
			    $rootP->setAdaptiveWigiiNamespace(true);
				ServiceProvider::getDataFlowService()->processDataSource($rootP, array2df($emails), dfasl(
					dfas('MapModel2ElementDFA', 'setGroupId', $this->getGroupId(),
						'setModel2ElementMappingMethod', CallableObject::createInstance('mapWigiiEmail2Element', $this)),
					dfas('ElementDFA', 'setMode', '1')
				), true, false); /* explicitely prevents events to popup to avoid loops */
			}
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError('persistEmails', $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation('persistEmails', $principal);
		return $returnValue;
	}	
	
	/**
	 * DataFlow callback used to map a WigiiEmail to an Element of module Emails.
	 * @param WigiiEmailWebImpl $wigiiEmail
	 * @param Element $element
	 * @param MapModel2ElementDFA $caller
	 */
	public function mapWigiiEmail2Element($wigiiEmail, $element, $caller) {
		if(isset($wigiiEmail)) {
			$recipients = array();
			$element->setFieldValue(date('Y-m-d H:i:s'), 'date');
			$element->setFieldValue($wigiiEmail->getFrom(), 'from');
			$element->setFieldValue($wigiiEmail->getReplyToForDb(), 'replyTo');			
			$arr = $wigiiEmail->getToForDb();
			if(!empty($arr)) {
				$recipients = array_merge($recipients, $arr);
				$element->setFieldValue(implode(', ', array_keys($arr)), 'to');
			}
			$arr = $wigiiEmail->getCcForDb();
			if(!empty($arr)) {
				$recipients = array_merge($recipients, $arr);
				$element->setFieldValue(implode(', ', array_keys($arr)), 'cc');
			}
			$arr = $wigiiEmail->getBccForDb();
			if(!empty($arr)) {
				$recipients = array_merge($recipients, $arr);
				$element->setFieldValue(implode(', ', array_keys($arr)), 'bcc');
			}
			if(!empty($recipients)) {
				$element->setFieldValue(implode(', ', array_keys($recipients)), 'recipients');
			}
			$element->setFieldValue($wigiiEmail->getSubjectForDb(), 'subject');
			$element->setFieldValue($wigiiEmail->getBodyHtmlForDb(), 'content');
			
			$caller->writeResultToOutput($element);
		}
	}
}


