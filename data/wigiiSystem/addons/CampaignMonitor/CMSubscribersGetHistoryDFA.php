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
 * A data flow activity which gets subscribers history from Campaign Monitor
 * Created by CWE on 28 novembre 2013
 */
class CMSubscribersGetHistoryDFA extends ElementDFAWithFuncExpVM
{		
	private $historyStatMapping;
	
	// Object lifecycle
		
	public function reset() {
		parent::reset();
		$this->historyStatMapping = array('nbOfSentEmails' => fs('nbOfSentEmails'),
			'openEmails' => fs('openEmails'),
			'lastOpenEmail' => fs('lastOpenEmail'),
			'clickedEmails' => fs('clickedEmails'),
			'lastClickedEmail' => fs('lastClickedEmail'),
			'unsubscribed' => fs('unsubscribed'),
			'unsubscribedOn' => fs('unsubscribedOn')
		);
	}	
	public function freeMemory() {
		unset($this->cs_REST_Subscribers);
		unset($this->cmSubscriberListId);
		unset($this->emailFuncExp);		
		unset($this->historyStatMapping);
		parent::freeMemory();
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("CMSubscribersGetHistoryDFA");
		}
		return $this->_debugLogger;
	}
	
	private $_executionSink;
	private function executionSink() {
		if (!isset ($this->_executionSink)) {
			$this->_executionSink = ExecutionSink :: getInstance("CMSubscribersGetHistoryDFA");
		}
		return $this->_executionSink;
	}
	
	// configuration
	
	private $cs_REST_Subscribers;
	/**
	 * Sets the CS_REST_Subscribers instance to be used to import subscribers
	 * See class /vendor/campaignmonitor/createsend-php/csrest_subscribers.php
	 * in /data/wigiiSystem/core/api/libs folder.
	 */
	public function setCS_REST_Subscribers($cs_REST_Subscribers) {
		$this->cs_REST_Subscribers = $cs_REST_Subscribers;
	}
	
	private $cmSubscriberListId;
	/**
	 * Sets the CM subscriber list ID which holds the subcriber 
	 * for which we want the history
	 * @param String $listId
	 */	
	public function setCMSubscriberListId($listId) {
		$this->cmSubscriberListId = $listId;
	}
	
	private $emailFuncExp;
	/**
	 * Sets the func exp used to get the email of the subscriber for which we want the history.
	 * @param FuncExp $funcExp a FuncExp or a FieldSelector instance
	 * example: fs('email1')	 
	 */
	public function setEmailFuncExp($funcExp) {
		$this->emailFuncExp = $funcExp;
	}	
	
	/**
	 * Sets the FieldSelector used to update the element with 
	 * history statistic value: nbOfSentEmails
	 * @param FieldSelector $fs
	 */
	public function setNbOfSentEmailsFS($fs) {
		if(!isset($fs)) throw new DataFlowServiceException("field selector cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->historyStatMapping['nbOfSentEmails'] = $fs;
	}
	/**
	 * Sets the FieldSelector used to update the element with 
	 * history statistic value: openEmails
	 * @param FieldSelector $fs
	 */
	public function setOpenEmailsFS($fs) {
		if(!isset($fs)) throw new DataFlowServiceException("field selector cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->historyStatMapping['openEmails'] = $fs;
	}
	/**
	 * Sets the FieldSelector used to update the element with 
	 * history statistic value: lastOpenEmail
	 * @param FieldSelector $fs
	 */
	public function setLastOpenEmailFS($fs) {
		if(!isset($fs)) throw new DataFlowServiceException("field selector cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->historyStatMapping['lastOpenEmail'] = $fs;
	}
	/**
	 * Sets the FieldSelector used to update the element with 
	 * history statistic value: clickedEmails
	 * @param FieldSelector $fs
	 */
	public function setClickedEmailsFS($fs) {
		if(!isset($fs)) throw new DataFlowServiceException("field selector cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->historyStatMapping['clickedEmails'] = $fs;
	}
	/**
	 * Sets the FieldSelector used to update the element with 
	 * history statistic value: lastClickedEmail
	 * @param FieldSelector $fs
	 */
	public function setLastClickedEmailFS($fs) {
		if(!isset($fs)) throw new DataFlowServiceException("field selector cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->historyStatMapping['lastClickedEmail'] = $fs;
	}
	/**
	 * Sets the FieldSelector used to update the element with 
	 * history statistic value: unsubscribed
	 * @param FieldSelector $fs
	 */
	public function setUnsubscribedFS($fs) {
		if(!isset($fs)) throw new DataFlowServiceException("field selector cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->historyStatMapping['unsubscribed'] = $fs;
	}
	/**
	 * Sets the FieldSelector used to update the element with 
	 * history statistic value: unsubscribedOn
	 * @param FieldSelector $fs
	 */
	public function setUnsubscribedOnFS($fs) {
		if(!isset($fs)) throw new DataFlowServiceException("field selector cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->historyStatMapping['unsubscribedOn'] = $fs;
	}
		
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {		
		// checks the injection of the CM client
		if(!isset($this->cs_REST_Subscribers)) throw new DataFlowServiceException("CM client has not been injected, please add one.", DataFlowServiceException::CONFIGURATION_ERROR);
		if(isset($this->cmSubscriberListId)) {
			$this->cs_REST_Subscribers->set_list_id($this->cmSubscriberListId);					
		}
		// checks the presence of the email func exp
		if(!isset($this->emailFuncExp)) throw new DataFlowServiceException("the email func exp is not set, please add one", DataFlowServiceException::CONFIGURATION_ERROR);
		parent::startOfStream($dataFlowContext);
	}
	
	// element event handling
	
	protected function processElement($elementP, $dataFlowContext) {
		// gets subscriber's email
		$element = $elementP->getDbEntity();
		$email = $this->evaluateFuncExp($this->emailFuncExp);
		if(!empty($email)) {
			$historyStat = array('nbOfSentEmails' => 0, 'openEmails' => 0, 'lastOpenEmail' => '', 'clickedEmails' => 0, 'lastClickedEmail' => '',
								 'unsubscribed' => false, 'unsubscribedOn' => '');
			
			// fetches subscriber's details
			$details = $this->executeGetSubscriberDetail($email);
			if($details) {
				//$this->debugLogger()->write(json_encode($details));
				// udpates unsubscription status
				if($details->State == 'Unsubscribed') {
					$historyStat['unsubscribed'] = true;
					$historyStat['unsubscribedOn'] = $details->Date;
				}
			}
			// fetches subscriber's history
			$history = $this->executeGetSubscriberHistory($email);
			if($history) {
				//$this->debugLogger()->write(json_encode($history));
				// computes history statistics				
				// nbOfSentEmails = number of campaigns in history				
				// openEmails = sum on all campaigns of (1 if exists at least 1 open event else 0)
				// lastOpenEmail = max date of all open events
				// clickedEmails = sum on all campaigns of (1 if exists at least 1 click event else 0)
				// lastClickedEmail = max date of all clicked events
				foreach($history as $campaigns) {
					$historyStat['nbOfSentEmails'] = $historyStat['nbOfSentEmails']+1;
					$events = $campaigns->Actions;
					if(!empty($events)) {
						$openedEmail = false; $clickedEmail = false;
						foreach($events as $event) {
							//$this->debugLogger()->write("history event:".json_encode($event));
							switch($event->Event) {
								case 'Click':
									$clickedEmail = true;
									if($event->Date > $historyStat['lastClickedEmail']) $historyStat['lastClickedEmail'] = $event->Date;
								case 'Open':
									$openedEmail = true;
									if($event->Date > $historyStat['lastOpenEmail']) $historyStat['lastOpenEmail'] = $event->Date;
									break;								
							}
						}
						if($openedEmail) $historyStat['openEmails'] = $historyStat['openEmails']+1;
						if($clickedEmail) $historyStat['clickedEmails'] = $historyStat['clickedEmails']+1;
					}
				}				
				// updates element				
				//$this->debugLogger()->write("history of subscriber '$email': ".json_encode($historyStat));
				$this->updateElementWithHistoryStat((object)$historyStat, $element);
				unset($historyStat);
				// pushes element in data flow
				$dataFlowContext->writeResultToOutput($elementP, $this);
			}
		}
		else $this->executionSink()->log("Empty email for element #".$element->getId().". Could not fetch subscriber's history");
	}

	/**
	 * Updates the element with the subscriber's history statistics
	 * @param StdClass $historyStat a PHP dynamic object with the attributes :
	 * - nbOfSentEmails : String = number of sent emails to subscriber
	 * - openEmails : Int as String = Nb of open emails
	 * - lastOpenEmail Date as String = Last open email date
	 * - clickedEmails Int as String = Nb of emails where the contact clicked in
	 * - lastClickedEmail Date as String = Last email date where the contact clicked in
	 * - unsubscribed Boolean = If true, then contact has unsubscribed from the list
	 * - unsubscribedOn String as Date = the unsubscription date as recorded by Campaign Monitor
	 * @param Element $element the element to be udpated
	 */
	protected function updateElementWithHistoryStat($historyStat, $element) {
		$fs = $this->historyStatMapping['nbOfSentEmails'];
		$element->setFieldValue($historyStat->nbOfSentEmails, $fs->getFieldName(), $fs->getSubFieldName());
		$fs = $this->historyStatMapping['openEmails'];
		$element->setFieldValue($historyStat->openEmails, $fs->getFieldName(), $fs->getSubFieldName());
		$fs = $this->historyStatMapping['lastOpenEmail'];
		$element->setFieldValue($historyStat->lastOpenEmail, $fs->getFieldName(), $fs->getSubFieldName());
		$fs = $this->historyStatMapping['clickedEmails'];
		$element->setFieldValue($historyStat->clickedEmails, $fs->getFieldName(), $fs->getSubFieldName());
		$fs = $this->historyStatMapping['lastClickedEmail'];
		$element->setFieldValue($historyStat->lastClickedEmail, $fs->getFieldName(), $fs->getSubFieldName());
		$fs = $this->historyStatMapping['unsubscribed'];
		$element->setFieldValue($historyStat->unsubscribed, $fs->getFieldName(), $fs->getSubFieldName());
		$fs = $this->historyStatMapping['unsubscribedOn'];
		$element->setFieldValue($historyStat->unsubscribedOn, $fs->getFieldName(), $fs->getSubFieldName());
	}
	
	// implementation
	
	private function executeGetSubscriberHistory($email) {
		$this->debugLogger()->write("fetches history of subscriber '$email'");
		$result = $this->cs_REST_Subscribers->get_history($email);
		if($result->was_successful()) {
			//$this->debugLogger()->write(json_encode($result));
			return $result->response;
		}		
		else {
			$this->executionSink()->log("Error in getting history of subscriber on Campaign Monitor. Response is : ".json_encode($result));
			return false;
		}
	}
	
	private function executeGetSubscriberDetail($email) {
		$this->debugLogger()->write("fetches detail of subscriber '$email'");
		$result = $this->cs_REST_Subscribers->get($email);
		if($result->was_successful()) {
			//$this->debugLogger()->write(json_encode($result));
			return $result->response;
		}		
		else {
			$this->executionSink()->log("Error in getting details of subscriber on Campaign Monitor. Response is : ".json_encode($result));
			return false;
		}
	}
}