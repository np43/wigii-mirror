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
 * A data flow connector used to fetch active subscribers into Campaign Monitor
 * Created by CWE on 10 décembre 2013
 */
class CMActiveSubscribersDFC implements DataFlowDumpable
{	
	private $lockedForUse = true;	
	private $nSubscribers;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->lockedForUse = true;	
		$this->maxNumberOfSubscribers = -1;			
		$this->nSubscribers = 0;
	}	
	public function freeMemory() {		
		unset($this->cmSubscriberListId);
		unset($this->fsk);
		unset($this->startingDate);			
		$this->lockedForUse = false;
	}
	
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("CMActiveSubscribersDFC");
		}
		return $this->_debugLogger;
	}
	
	// configuration
	
	private $cs_REST_Lists;
	/**
	 * Sets the CS_REST_Lists instance to be used to fetch active subscribers
	 * See class /vendor/campaignmonitor/createsend-php/csrest_lists.php
	 * in /data/wigiiSystem/core/api/libs folder.
	 */
	public function setCS_REST_Lists($cs_REST_Lists) {
		$this->cs_REST_Lists = $cs_REST_Lists;
	}

	private $cmSubscriberListId;
	/**
	 * Sets the CM subscriber list ID which should be fetched
	 * @param String $listId
	 */	
	public function setCMSubscriberListId($listId) {
		$this->cmSubscriberListId = $listId;
	}
	
	private $fsk;
	/**
	 * Sets the field by which the flow of subscribers is sorted
	 * @param String|FieldSortingKey $fieldName should be one of email, name, date. 
	 * If a FieldSortingKey is provided instead of a string, then the fieldname should be one of email, name or date
	 * and the FieldSortingKey->isAscending property is taken instead of the provided $ascending argument.
	 * @param Boolean $ascending the sorting order, if true ascending else descending
	 */
	public function setSortingByField($fieldName, $ascending=true) {
		if($fieldName instanceof FieldSortingKey) {
			switch(strtolower($fieldName->getFieldName())) {
				case 'email':
				case 'name':
				case 'date':
					$this->fsk = $fieldName;
					break;
				default: throw new DataFlowServiceException("sorting field name should be one of 'email', 'name' or 'date'", DataFlowServiceException::INVALID_ARGUMENT);
			}
		}
		elseif(is_string($fieldName)) {
			switch(strtolower($fieldName)) {
				case 'email':
				case 'name':
				case 'date':
					$this->fsk = FieldSortingKey::createInstance($fieldName, null, $ascending);
					break;
				default: throw new DataFlowServiceException("sorting field name should be one of 'email', 'name' or 'date'", DataFlowServiceException::INVALID_ARGUMENT);
			}
		}
		else $this->fsk = $fieldName;
	}
	
	private $startingDate;
	/**
	 * Sets the starting date from which to fetch subscribers
	 * @param String|timestamp $date the starting date in YYYY-MM-DD format or a timestamp
	 */
	public function setAddedSince($date) {
		if(is_int($date)) $this->startingDate = date('Y-m-d', $date);
		else $this->startingDate = $date;
	}
	protected function getAddedSince() {
		if(!isset($this->startingDate)) return '';
		else return $this->startingDate;
	}
	
	private $maxNumberOfSubscribers = -1;
	/**
	 * Sets the maximum number of subscribers to retrieve in the list
	 * @param int $limit the limit, should be a positivie integer
	 */
	public function setMaxNumberOfSubscribers($limit) {
		if(!($limit instanceof FuncExpParameter) && $limit <= 0) throw new DataFlowServiceException("max number of subscribers should be a positive integer", DataFlowServiceException::INVALID_ARGUMENT);
		$this->maxNumberOfSubscribers = $limit;
	}
	
	private $maxPageSize = 1000;
	/**
	 * Sets the maximum page size (in number of subscribers) that should 
	 * be downloaded in one http call
	 * CM supports from 10 to 1000.
	 * @param int $n a positive integer
	 */
	public function setMaxPageSize($n) {
		if(!($n instanceof FuncExpParameter)) {
			if($n < 10) $n = 10;
			elseif($n > 1000) throw new DataFlowServiceException("max import size should be between 10 and 1000", DataFlowServiceException::INVALID_ARGUMENT);
		}		
		$this->maxPageSize = $n;
	}

	// DataFlowDumpable implementation
	
	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext) {		
		// checks the injection of the CM client
		if(!isset($this->cs_REST_Lists)) throw new DataFlowServiceException("CM client has not been injected, please add one.", DataFlowServiceException::CONFIGURATION_ERROR);
		if(isset($this->cmSubscriberListId)) {
			$this->cs_REST_Lists->set_list_id($this->cmSubscriberListId);					
		}
		
		$this->nSubscribers = 0; $p = 1;
		// gets first page
		$result = $this->executeGetActiveSubscribers($p);
		$p++;
		$nPages = $result->NumberOfPages;
		$this->debugLogger()->write("total number of selected active subscribers : ".($result->TotalNumberOfRecords > 0 ? $result->TotalNumberOfRecords : 0));
		// dumps first page
		$this->dumpResultIntoDataFlow($result->Results, $dataFlowService, $dataFlowContext);

		// gets remaining pages or until read max number of subscribers
		while($p <= $nPages && 
			($this->maxNumberOfSubscribers < 0 || $this->nSubscribers < $this->maxNumberOfSubscribers)) {
			$result = $this->executeGetActiveSubscribers($p);
			$p++;
			$this->dumpResultIntoDataFlow($result->Results, $dataFlowService, $dataFlowContext);
		}
	}

	// implementation
		
	private function executeGetActiveSubscribers($page) {
		$this->debugLogger()->write("fetches page #".$page);
		$result = $this->cs_REST_Lists->get_active_subscribers($this->getAddedSince(),
			$page,
			$this->maxPageSize,
			(isset($this->fsk)? $this->fsk->getFieldName() : null),
			(isset($this->fsk)? ($this->fsk->isAscending() ? 'ASC':'DESC'): null));
		if($result->was_successful()) {
			//$this->debugLogger()->write(json_encode($result));
			return $result->response;
		}		
		else throw new DataFlowServiceException("Error in getting active subscribers on Campaign Monitor. Response is : ".json_encode($result), DataFlowServiceException::WRAPPING, $result->http_status_code);
	}
	
	private function dumpResultIntoDataFlow($results, $dataFlowService, $dataFlowContext) {
		//$this->debugLogger()->write('dumps results into dataflow : '.json_encode($results));
		if(is_array($results)) {
			foreach($results as $result) {
				if($this->maxNumberOfSubscribers < 0 || $this->nSubscribers < $this->maxNumberOfSubscribers) {
					$this->debugLogger()->write('dumps result #'.($this->nSubscribers+1).' into dataflow');
					$dataFlowService->processDataChunk($result, $dataFlowContext);
					$this->nSubscribers++;
				}
				else return;
			}
		}
	}
}