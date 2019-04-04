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
 * A batch which synchronises Campaign Monitor subscriber lists with wigii data.
 * For more information about Campaign Monitor, visit http://www.campaignmonitor.com/
 * This batch uses the Campaign Monitor PHP client installed wich composer
 * see CORE_PATH."/api/libs/vendor/campaignmonitor/createsend-php"
 * or visit http://campaignmonitor.github.io/createsend-php/
 * Created by CWE on 12.11.2013
 */
class CMSyncSubscriberListBatch extends WigiiBatch {

	//dependency injection

	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("CMSyncSubscriberListBatch");
		}
		return $this->_debugLogger;
	}
		
	private $_executionSink;
	private function executionSink() {
		if (!isset ($this->_executionSink)) {
			$this->_executionSink = ExecutionSink :: getInstance("CMSyncSubscriberListBatch");
		}
		return $this->_executionSink;
	}
	
	private $cmClientForSubscribers;
	/**
	 * Returns the Campaign Monitor client to use to interacts with subscribers
	 * @throws ServiceException if authentication key has not been set.
	 */
	public function getCMClientForSubscribers() {
		if(!isset($this->cmClientForSubscribers)) {
			if(!isset($this->cmApiKey)) throw new ServiceException("API key has not been defined, please enter one.", ServiceException::CONFIGURATION_ERROR);
			$this->cmClientForSubscribers = new CS_REST_Subscribers('undefined', $this->cmApiKey);
		}
		return $this->cmClientForSubscribers;
	}
	public function setCMClientForSubscribers($cmClientForSubscribers) {
		$this->cmClientForSubscribers = $cmClientForSubscribers;
	}
	
	private $cmClientForLists;
	/**
	 * Returns the Campaign Monitor client to use to interacts with lists
	 * @throws ServiceException if authentication key has not been set.
	 */
	public function getCMClientForLists() {
		if(!isset($this->cmClientForLists)) {
			if(!isset($this->cmApiKey)) throw new ServiceException("API key has not been defined, please enter one.", ServiceException::CONFIGURATION_ERROR);
			$this->cmClientForLists = new CS_REST_Lists('undefined', $this->cmApiKey);
		}
		return $this->cmClientForLists;
	}
	public function setCMClientForLists($cmClientForLists) {
		$this->cmClientForLists = $cmClientForLists;
	}
	
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
	
	private $configS;
	public function setConfigService($configService)
	{
		$this->configS = $configService;
	}
	protected function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = ServiceProvider::getConfigService();
		}
		return $this->configS;
	}
	
	private $gAS;
	public function setGroupAdminService($groupAdminService) {
		$this->gAS = $groupAdminService;
	}
	protected function getGroupAdminService() {
		// autowired
		if(!isset($this->gAS)) {
			$this->gAS = ServiceProvider::getGroupAdminService();
		}
		return $this->gAS;
	}
	
	// Configuration
	
	private $cmApiKey;
	/**
	 * Sets the CM API key to use for authentication and authorization
	 * See http://www.campaignmonitor.com/api/getting-started/#authenticating_with_an_api_key
	 * @param String $apiKey
	 */
	public function setCMAPIKey($apiKey) {
		$this->cmApiKey = $apiKey;
	}	
	
	private $selExpForUpdateCMSubscribers, $dfaslForUpdateCMSubscribers, $fxpMappingListForUpdateCMSubscribers;
	/**
	 * Sets the whole DataFlow expression used to select contacts in wigii,
	 * run the data flow to transform contacts into CM subscribers objects,
	 * and import them into CM. 
	 * @param DataFlowDumpable $selExp the selector expression to get contacts into wigii
	 * @param DataFlowActivitySelectorList $dfasl the data flow description
	 * @param FuncExpParameterMappingList $fxpMappingList a func exp parameter mapping list, to update several subscriber lists
	 */
	public function setDataFlowExpForUpdateCMSubscribers($selExp, $dfasl, $fxpMappingList) {
		$this->selExpForUpdateCMSubscribers = $selExp;
		$this->dfaslForUpdateCMSubscribers = $dfasl;
		$this->fxpMappingListForUpdateCMSubscribers = $fxpMappingList;
	}	
	
	private $selExpForUpdateWigiiContacts, $dfaslForUpdateWigiiContacts, $fxpMappingListForUpdateWigiiContacts;
	/**
	 * Sets the whole DataFlow expression used to select subscribers,
	 * run the data flow to get some information into CM and update associated wigii contacts with the information. 
	 * @param DataFlowDumpable $selExp the selector expression to select subscribers
	 * @param DataFlowActivitySelectorList $dfasl the data flow description
	 * @param FuncExpParameterMappingList $fxpMappingList a func exp parameter mapping list, to read several subscriber lists
	 */
	public function setDataFlowExpForUpdateWigiiContacts($selExp, $dfasl, $fxpMappingList) {
		$this->selExpForUpdateWigiiContacts = $selExp;
		$this->dfaslForUpdateWigiiContacts = $dfasl;
		$this->fxpMappingListForUpdateWigiiContacts = $fxpMappingList;
	}	
	
	private $cmListsCreationDetails;
	private $cmListsCustomFields;
	/**
	 * Sets the details to create Lists into Campaign Monitor
	 * See class /vendor/campaignmonitor/createsend-php/csrest_lists.php
	 * in /data/wigiiSystem/core/api/libs folder.
	 * Methods create and create_custom_field
	 * @param Array $listCreationDetails the array defining the lists to be created
	 * The array is of the form : (CMClientID => array(array(list 1 details), array(list 2 details), ...))
	 * @param Array $customFields an optional array defining custom fields. 
	 * The same custom fields are created for all the created lists.
	 * The array is of the form : array(array(custom field 1 details), array(custom field 2 details), ...)
	 */
	public function setListCreationDetails($listCreationDetails, $customFields=null) {
		$this->cmListsCreationDetails = $listCreationDetails;
		$this->cmListsCustomFields = $customFields;
	}
	
	private $groupIdForBatchResult;
	/**
	 * Sets the wigii group ID where to store the batch result
	 * This group should have a configuration containing the 3 following fields : 
	 * - batchName : Strings
	 * - batchRun : Dates (including time)
	 * - batchOutput : Blobs.
	 * @param int|String $groupId the group id
	 */
	public function setGroupIdForBatchResult($groupId) {
		$this->groupIdForBatchResult = $groupId;
	}
	
	// Batch execution

	public function run($argc, $argv, $subArgIndex) {
		$principal = $this->getPrincipal();
		// runs only the specified commands
		if($subArgIndex < $argc) {
			while($subArgIndex < $argc) {
				$this->runCmd($argv[$subArgIndex], $principal);
				$subArgIndex++;
			}
		}
		// runs all the commands
		else {
			$this->runCmd(1, $principal);
			$this->runCmd(2, $principal);
		}
	}
	/**
	 * Runs the specified command given the index
	 */
	private function runCmd($index, $principal) {
		$cmdName = 'cmd'.$index;
		if(!method_exists($this, $cmdName)) {
			$this->executionSink()->log("unrecognized command index '$index'. Does nothing.");
		}
		else {
			$this->executionSink()->publishStartOperation($cmdName, $principal);
			try {
				$batchOut = $this->{$cmdName}($principal);
				if(isset($batchOut) && isset($this->groupIdForBatchResult)) {
					$this->saveBatchOutputIntoElement($batchOut, get_class($this)." cmd ".$index , $this->groupIdForBatchResult, $principal);
				}
			}
			catch(Exception $e) {
				$this->executionSink()->publishEndOperationOnError($cmdName, $e, $principal);
				throw $e;
			}		
			$this->executionSink()->publishEndOperation($cmdName, $principal);
		}
	}
	/**
	 * updates subscribers in CM based on wigii elements
	 */
	private function cmd1($principal) {
		if(isset($this->selExpForUpdateCMSubscribers)) {
			if(isset($this->fxpMappingListForUpdateCMSubscribers)) {
				$dfOut = DataFlowResultListArrayImpl::createInstance();
				multiSel($principal, $this->selExpForUpdateCMSubscribers, $this->dfaslForUpdateCMSubscribers, $this->fxpMappingListForUpdateCMSubscribers, $dfOut);
				$returnValue = '';
				foreach($dfOut->getListIterator() as $key => $result) {
					$returnValue .= $key.":\n".$result."\n\n";
				}				
			}
			else {
				$returnValue = sel($principal, $this->selExpForUpdateCMSubscribers, $this->dfaslForUpdateCMSubscribers);
			}
			return $returnValue;
		}
	}
	/**
	 * updates wigii elements with information coming from CM subscribers
	 */
	private function cmd2($principal) {
		if(isset($this->selExpForUpdateWigiiContacts)) {
			if(isset($this->fxpMappingListForUpdateWigiiContacts)) {
				multiSel($principal, $this->selExpForUpdateWigiiContacts, $this->dfaslForUpdateWigiiContacts, $this->fxpMappingListForUpdateWigiiContacts);
			}
			else {
				sel($principal, $this->selExpForUpdateWigiiContacts, $this->dfaslForUpdateWigiiContacts);
			}
			return "OK";
		}
	}
	
	/**
	 * Creates lists and custom fields into CM	 
	 */
	private function cmd3($principal) {
		// Creates lists
		if(is_array($this->cmListsCreationDetails)) {
			$listIds = array();
			foreach($this->cmListsCreationDetails as $clientId => $lists) {
				if(is_array($lists)) {
					foreach($lists as $listCreationDetails) {
						// if listCreationDetails contains a listID then does not create the list but only adds the id
						if(!is_array($listCreationDetails)) throw new ServiceException("listCreationDetails should be an array", ServiceException::CONFIGURATION_ERROR);
						$listId = $listCreationDetails['ID'];
						if(isset($listId)) $listIds[] = $listId;
						else $listIds[] = $this->executeCreateList($clientId, $listCreationDetails);
					}
				}
			}
			$this->executionSink()->log("Created the following lists into CM: ".implode(", ", $listIds));
			
			// Creates custom fields
			if(is_array($this->cmListsCustomFields)) {
				// prepares custom fields array
				$customFields = array();
				foreach($this->cmListsCustomFields as $customFieldDetail) {
					$customFields[$customFieldDetail['FieldName']] = $customFieldDetail;
				}
				$addedCustomFields = array();
				$deletedCustomFields = array();
				foreach($listIds as $listId) {
					// gets existing custom fields
					$currentCustomFields = $this->executeGetCustomFields($listId);
					// deletes unwanted custom fields
					$customFieldsToDelete = array_diff_key($currentCustomFields, $customFields);
					if(!empty($customFieldsToDelete)) {
						$deletedCustomFields = array_merge($deletedCustomFields, $customFieldsToDelete);
						$this->executeDeleteCustomFields($listId, $customFieldsToDelete);
					}
					
					// adds new custom fields
					$customFieldsToAdd = array_diff_key($customFields, $currentCustomFields);
					if(!empty($customFieldsToAdd)) {
						foreach($customFieldsToAdd as $customFieldDetail) {
							$key = $this->executeCreateCustomField($listId, $customFieldDetail);
							$addedCustomFields[$customFieldDetail['FieldName']] = $key;
						}					
					}
				}
				$this->executionSink()->log("The following custom fields where created into the lists : ".implode(', ', $addedCustomFields).
				", the following fields where deleted from CM: ".implode(', ', $deletedCustomFields).
				", the following fields where already present in CM: ".implode(', ', array_keys(array_diff_key($customFields, $addedCustomFields))));
			}
		}		
	}
	private function executeCreateList($clientId, $listCreationDetails) {
		$result = $this->getCMClientForLists()->create($clientId, $listCreationDetails);
		if($result->was_successful()) {
			//$this->debugLogger()->write(json_encode($result));
			return $result->response;
		}		
		else {
			throw new ServiceException("Error in creating list '".$listCreationDetails['Title']."' on Campaign Monitor. Response is : ".json_encode($result), $result->http_status_code);
		}
	}
	private function executeCreateCustomField($listId, $customFieldDetail) {
		$cm = $this->getCMClientForLists();
		$cm->set_list_id($listId);
		$result = $cm->create_custom_field($customFieldDetail);
		if($result->was_successful()) {
			//$this->debugLogger()->write(json_encode($result));
			return $result->response;
		}		
		else {
			throw new ServiceException("Error in creating custom field '".$customFieldDetail['FieldName']."' on Campaign Monitor. Response is : ".json_encode($result), $result->http_status_code);
		}
	}
	private function executeDeleteCustomFields($listId, $customFields) {
		if(!empty($customFields)) {
			$cm = $this->getCMClientForLists();
			$cm->set_list_id($listId);
			
			foreach($customFields as $fieldName=>$key) {
				$result = $cm->delete_custom_field($key);
				if(!empty($result) && $result->http_status_code != 200) {
					$response = $result->response;
					if(!(is_object($response) && $response->{'Code'} == 253)) throw new ServiceException("Error in deleting custom field '".$fieldName."' on Campaign Monitor. Response is : ".json_encode($result), $result->http_status_code);
				}
			}		
		}
	}
	private function executeGetCustomFields($listId) {
		$cm = $this->getCMClientForLists();
		$cm->set_list_id($listId);
		$result = $cm->get_custom_fields();
		if($result->was_successful()) {
			// fills an array [custom field name => custom field key]
			$returnValue = array();
			foreach($result->response as $customField) {
				$returnValue[$customField->{'FieldName'}] = $customField->{'Key'};
			}
			return $returnValue;
		}
		else {
			throw new ServiceException("Error in getting custom field list for list '".$listId."' on Campaign Monitor. Response is : ".json_encode($result), $result->http_status_code);
		}
	}
	
	/**
	 * Saves the batch output into a new element stored into the given group
	 * @param mixed $batchOutput the batch output. A string or any object. If any object, then converts to json.
	 * @param String $batchName the batch name.
	 * @param int|String $groupId the group id in which to store the element
	 * @param Principal $principal the principal executing the batch
	 */
	protected function saveBatchOutputIntoElement($batchOutput, $batchName, $groupId, $principal) {
		// gets group
		$group = $this->getGroupAdminService()->getGroupWithoutDetail($principal, $groupId);
		// gets group configuration
		$fieldList = FieldListArrayImpl::createInstance();
		$this->getConfigService()->getGroupFields($principal, $group, null, $fieldList);
		// creates element
		$element = Element::createInstance($group->getModule(), $fieldList, WigiiBagBaseImpl::createInstance());
		// fills element with given values
		$element->setFieldValue($batchName, 'batchName');
		$element->setFieldValue(date('Y-m-d H:i:s'), 'batchRun');
		if(!is_string($batchOutput)) $batchOutput = json_encode($batchOutput);
		$element->setFieldValue($batchOutput, 'batchOutput');
		// creates field selector list
		$fsl = fsl(fs('batchName'), fs('batchRun'), fs('batchOutput'));
		// inserts element into given group
		$this->getElementService()->insertElement($principal, $element, $groupId, $fsl);
	}
}

// Functions

/**
 * Creates a DataFlow connector which selects all the active subscribers of a given list since a starting date
 * @param String $listId the CM subscriber list ID
 * @param String|int $addedSince the starting subscription date in YYYY-MM-DD format or a timestamp
 * @param String $sortingByField|FieldSortingKey the field by which the flow of subscribers should be sorted, should be one of email, name, date
 * If a FieldSortingKey is provided instead of a string, then the fieldname should be one of email, name or date
 * and the FieldSortingKey->isAscending property is taken instead of the provided $ascending argument. 
 * @param Boolean $ascending the sorting order, if true ascending else descending
 * @param int $limit sets an upper limit to the number of downloaded subscribers, if null, downloads all subscribers in the list
 */
function cmActiveSubscribers($listId, $addedSince=null, $sortingByField=null, $ascending=true, $limit=null) {
	$returnValue = ServiceProvider::getExclusiveAccessObject('CMActiveSubscribersDFC');	
	$returnValue->setCMSubscriberListId($listId);
	if(isset($sortingByField)) $returnValue->setSortingByField($sortingByField, $ascending);
	if(isset($addedSince)) $returnValue->setAddedSince($addedSince);
	if(isset($limit)) $returnValue->setMaxNumberOfSubscribers($limit);
	if($listId instanceof FuncExpParameter) $listId->registerSetterMethod('setCMSubscriberListId', $returnValue);
	if($sortingByField instanceof FuncExpParameter) $sortingByField->registerSetterMethod('setSortingByField', $returnValue);
	if($addedSince instanceof FuncExpParameter) $addedSince->registerSetterMethod('setAddedSince', $returnValue);
	if($limit instanceof FuncExpParameter) $limit->registerSetterMethod('setMaxNumberOfSubscribers', $returnValue);
	return $returnValue;
}

/**
 * Creates a DataFlow connector which selects all the unsubscribed subscribers of a given list since a starting date
 * @param String $listId the CM subscriber list ID
 * @param String|int $unsubscribedSince the ending subscription date in YYYY-MM-DD format or a timestamp
 * @param String $sortingByField|FieldSortingKey the field by which the flow of subscribers should be sorted, should be one of email, name, date
 * If a FieldSortingKey is provided instead of a string, then the fieldname should be one of email, name or date
 * and the FieldSortingKey->isAscending property is taken instead of the provided $ascending argument. 
 * @param Boolean $ascending the sorting order, if true ascending else descending
 * @param int $limit sets an upper limit to the number of downloaded subscribers, if null, downloads all subscribers in the list
 */
function cmUnsubscribedSubscribers($listId, $unsubscribedSince=null, $sortingByField=null, $ascending=true, $limit=null) {
	$returnValue = ServiceProvider::getExclusiveAccessObject('CMUnsubscribedSubscribersDFC');	
	$returnValue->setCMSubscriberListId($listId);
	if(isset($sortingByField)) $returnValue->setSortingByField($sortingByField, $ascending);
	if(isset($unsubscribedSince)) $returnValue->setUnsubscribedSince($unsubscribedSince);
	if(isset($limit)) $returnValue->setMaxNumberOfSubscribers($limit);
	if($listId instanceof FuncExpParameter) $listId->registerSetterMethod('setCMSubscriberListId', $returnValue);
	if($sortingByField instanceof FuncExpParameter) $sortingByField->registerSetterMethod('setSortingByField', $returnValue);
	if($unsubscribedSince instanceof FuncExpParameter) $unsubscribedSince->registerSetterMethod('setUnsubscribedSince', $returnValue);
	if($limit instanceof FuncExpParameter) $limit->registerSetterMethod('setMaxNumberOfSubscribers', $returnValue);
	return $returnValue;
}