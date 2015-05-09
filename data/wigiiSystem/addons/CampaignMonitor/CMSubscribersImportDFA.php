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

/**
 * A data flow activity which imports subscribers into Campaign Monitor
 * Created by CWE on 28 novembre 2013
 */
class CMSubscribersImportDFA implements DataFlowActivity
{	
	private $subscribers;
	private $nSubscribers;	
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->nSubscribers = 0;	
		$this->maxImportSize = 1000;	
	}	
	public function freeMemory() {
		unset($this->cs_REST_Subscribers);
		unset($this->cmSubscriberListId);
		unset($this->resubscribe);
		unset($this->queueSubscriptionBasedAutoResponders);
		unset($this->restartSubscriptionBasedAutoResponders);
		unset($this->subscribers);
	}
	
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("CMSubscribersImportDFA");
		}
		return $this->_debugLogger;
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
	 * Sets the CM subscriber list ID which should be populated
	 * @param String $listId
	 */	
	public function setCMSubscriberListId($listId) {
		$this->cmSubscriberListId = $listId;
	}
	
	private $resubscribe;
	/**
	 * See doc for parameter $resubscribe of method CS_REST_Subscribers->import
	 * in class /vendor/campaignmonitor/createsend-php/csrest_subscribers.php
	 */
	public function setResubscribeExistingContact($bool) {
		$this->resubscribe = $bool;
	}
	
	private $queueSubscriptionBasedAutoResponders;
	/**
	 * See doc for parameter $queueSubscriptionBasedAutoResponders of method CS_REST_Subscribers->import
	 * in class /vendor/campaignmonitor/createsend-php/csrest_subscribers.php
	 */
	public function setQueueSubscriptionBasedAutoResponders($bool) {
		$this->queueSubscriptionBasedAutoResponders = $bool;
	}
	
	private $restartSubscriptionBasedAutoResponders;
	/**
	 * See doc for parameter $restartSubscriptionBasedAutoResponders of method CS_REST_Subscribers->import
	 * in class /vendor/campaignmonitor/createsend-php/csrest_subscribers.php
	 */
	public function setRestartSubscriptionBasedAutoResponders($bool) {
		$this->restartSubscriptionBasedAutoResponders = $bool;
	}
	
	private $maxImportSize = 1000;
	/**
	 * Sets the maximum number of subscribers that can be imported in one http call
	 * Campaign Monitor API supports a maximum of 1000.
	 * @param int $n
	 */
	public function setMaxImportSize($n) {
		if(!(0 < $n && $n <= 1000)) throw new DataFlowServiceException("max import size should be between 1 and 1000", DataFlowServiceException::INVALID_ARGUMENT);
		$this->maxImportSize = $n;
	}
	/**
	 * Returns the maximum number of subscribers that can be imported in one http call	
	 */
	protected function getMaxImportSize() {
		return $this->maxImportSize;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {		
		// checks the injection of the CM client
		if(!isset($this->cs_REST_Subscribers)) throw new DataFlowServiceException("CM client has not been injected, please add one.", DataFlowServiceException::CONFIGURATION_ERROR);
		if(isset($this->cmSubscriberListId)) {
			$this->cs_REST_Subscribers->set_list_id($this->cmSubscriberListId);					
		}
		$this->subscribers = array();	
		$this->nSubscribers = 0;	
	}
	public function processDataChunk($data, $dataFlowContext) {
		if(is_null($data)) return;
		// if number of subscribers >= max import size then first flushes the buffer
		// by executing the pending import		
		if($this->nSubscribers >= $this->getMaxImportSize()) {
			$dataFlowContext->writeResultToOutput($this->executeSubsribersImport(), $this);
			// resets buffer
			$this->subscribers = array();
			$this->nSubscribers = 0;
		}
		// adds the current subscriber to the import buffer
		$sub = array();
		$customFields = array();
		$nCustom = 0;
		foreach($data as $property => $value) {
			switch($property) {
				case "EmailAddress":
				case "Name":
					$sub[$property] = $value;
					break;
				default:
					$customFields[] = array("Key"=>$property, "Value" =>$value);
					$nCustom++;
					break;
			}
		}	
		if($nCustom > 0) $sub["CustomFields"] = $customFields;
		
		$this->subscribers[] = $sub;
		$this->nSubscribers++;			
	}
	public function endOfStream($dataFlowContext) {
		$dataFlowContext->writeResultToOutput($this->executeSubsribersImport(), $this);
	}
		
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		return $this->endOfStream($dataFlowContext);
	}

	// implementation
	
	/** 
	 * Executes import and returns the result object
	 */
	private function executeSubsribersImport() {
		if($this->nSubscribers > 0) {			
			$result = $this->cs_REST_Subscribers->import($this->subscribers,
			($this->resubscribe == true),
			($this->queueSubscriptionBasedAutoResponders == true),
			($this->restartSubscriptionBasedAutoResponders == true));			
			//$result = $this->subscribers;
			$this->debugLogger()->write("imports into CM: http ".$result->http_status_code);
			//$this->debugLogger()->write("imports into CM");
			return $result;
		}
		else return null;
	}
}