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
 * A data flow activity which maps PHP stdClass instances or Array of arrays to elements
 * This DataFlowActivity cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * Created by CWE on 6 décembre 2013
 * Modified by Medair (CWE) on 15.12.2016 to protect against Cross Site Scripting
 * Modified by Medair (CWE) on 28.04.2017 to attach stamped ElementInfo on fetch
 * Modified by Medair (CWE) on 05.07.2017 to fetch element only if LogExp is defined + ensure that fetched element always has all fields if no FieldSelectorList is defined
 */
class MapObject2ElementDFA implements DataFlowActivity, ElementPList
{			
	private $_debugLogger;
	private $dataFlowContext;
	private $data;
	private $nElements;
	private $instanciatedApiClient;
	private $calculatedFieldSelectorList;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
		$this->instanciatedApiClient = false;
		$this->insertOnlyMode = false;		
	}	
	public function freeMemory() {
		unset($this->inGroupLogExp);
		unset($this->groupId);
		unset($this->linkSelector);
		unset($this->fieldSelectorList);
		unset($this->calculatedFieldSelectorList);	
		unset($this->dataFlowContext);
		unset($this->data);
		unset($this->elementSelectorMethod);
		unset($this->object2ElementMappingMethod);
		unset($this->object2ElementMap);
		unset($this->fieldMappingMethod);
		unset($this->authoSStamp);
		$this->nElements = 0;	
	}
	
	// dependency injection
		
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("MapObject2ElementDFA");
		}
		return $this->_debugLogger;
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
	
	private $transS;	
	public function setTranslationService($translationService)
	{
		$this->transS = $translationService;
	}
	protected function getTranslationService()
	{
		// autowired
		if(!isset($this->transS))
		{
			$this->transS = ServiceProvider::getTranslationService();
		}
		return $this->transS;
	}
	
	private $wigiiNamespaceAS;
	public function setWigiiNamespaceAdminService($wigiiNamespaceAdminService)
	{
		$this->wigiiNamespaceAS = $wigiiNamespaceAdminService;
	}
	protected function getWigiiNamespaceAdminService()
	{
		// autowired
		if(!isset($this->wigiiNamespaceAS))
		{
			$this->wigiiNamespaceAS = ServiceProvider::getWigiiNamespaceAdminService();
		}
		return $this->wigiiNamespaceAS;
	}
	
	private $authoS;
	public function setAuthorizationService($authorizationService)
	{
	    $this->authoS = $authorizationService;
	}
	protected function getAuthorizationService()
	{
	    // autowired
	    if(!isset($this->authoS))
	    {
	        $this->authoS = ServiceProvider::getAuthorizationService();
	    }
	    return $this->authoS;
	}
	
	private $authoSStamp;
	public function setAuthorizationServiceStamp($stamp) {
	    $this->authoSStamp = $stamp;
	}
	
	// configuration
	
	private $insertOnlyMode;
	/**
	 * If true, then elements are always created, 
	 * else elements are first selected in the database and updated. 
	 * New elements are created only if they do not exist in the database.
	 * By default, insertOnlyMode is desactivated.
	 * If insertOnlyMode is activated, then a groupId should be configured 
	 * or/and a linkSelector should be set to create subItems or linked elements. 
	 */
	public function setInsertOnlyMode($bool) {
		$this->insertOnlyMode = $bool;
	}
	
	private $groupId;
	/**
	 * Sets the group ID that should be used to get the configuration
	 * when creating new elements in insertOnlyMode.
	 * Or the groupID that should be used to select elements in update mode.
	 * If the inGroupLogExp is set, then it is combined with an OR.
	 */
	public function setGroupId($groupId) {
		$this->groupId = $groupId;
	}
	
	private $elementSelectorMethod;
	/**
	 * Sets the selector method that should be executed on each object in the flow in order
	 * to build a logical expression used to select the associated element.
	 * The method signature takes one argument which is the current object in the data flow and
	 * a second argument which is the current data flow context.
	 * The method should return a LogExp instance that will be used to select the appropriate element.
	 * @param Closure|String $method a closure representing the code that should be executed or 
	 * a string which is an object method name or a function name
	 * @param Any $object an optional object instance which holds the method instance that should be executed.
	 */
	public function setElementSelectorMethod($method, $obj=null) {
		$this->elementSelectorMethod = CallableObject::createInstance($method, $obj);
	}
	
	private $object2ElementMappingMethod;
	/**
	 * Sets the method that should be executed on each pair of object/element in the flow in order
	 * to map the current object to the element.
	 * The method signature takes one argument which is the current object in the data flow,
	 * a second argument which is the selected elementP (Element if it is a new element because none was selected), and
	 * a third argument which is reference to this object that can be used to get the 
	 * DataFlowContext and write output using the writeResultToOutput helper method.
	 * @param Closure|String $method a closure representing the code that should be executed or 
	 * a string which is an object method name or a function name
	 * @param Any $object an optional object instance which holds the method instance that should be executed.
	 */
	public function setObject2ElementMappingMethod($method, $obj=null) {
		$this->object2ElementMappingMethod = CallableObject::createInstance($method, $obj);
	}
	
	private $fieldMappingMethod;
	/**
	 * Sets the method that should be executed to map one specific object attribute to the element
	 * The method signature takes one argument which is the object field value, 
	 * a second argument which is the element, a third argument which is the DataFlowContext
	 * @param String $fieldName the object field name for which to set a closure
	 * @param Closure|String $method a closure representing the code that should be executed or 
	 * a string which is an object method name or a function name
	 * @param Any $object an optional object instance which holds the method instance that should be executed.
	 */
	public function setFieldMappingMethod($fieldName, $method, $obj=null) {
		if(empty($fieldName)) throw new DataFlowServiceException("fieldName cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		if(!isset($this->fieldMappingMethod)) $this->fieldMappingMethod = array();
		$this->fieldMappingMethod[$fieldName] = CallableObject::createInstance($method, $obj);
	}
	protected function getFieldMappingMethod($fieldName) {
		if(isset($this->fieldMappingMethod)) return $this->fieldMappingMethod[$fieldName];
		else return null;
	}
	/**
	 * Adds a list of field mapping closures
	 * Calls the method setFieldMappingMethod for each pair in the array.
	 * @param Array $closureMap an array [fieldName => field mapping closure]
	 */
	public function addFieldMappingsMethods($closureMap) {
        if(!is_array($closureMap)) throw new DataFlowServiceException('addFieldMappingsMethods takes an array of field mapping closures', DataFlowServiceException::INVALID_ARGUMENT);
        foreach($closureMap as $fieldName => $closure) {
            $this->setFieldMappingMethod($fieldName, $closure);
        }
	}
	
	private $object2ElementMap;
	/**
	 * Sets an optional map used to map object attributes to element fields
	 * @param Array $map an array which keys are the object attribute names and values are field selectors on the element
	 * example: array('first_name' => fs('first_name'), 'address' => array('street' => fs('address', 'street'), 'city' => fs('address', 'city')))	 
	 */
	public function setObject2ElementMap($map) {
		if(!isset($map)) unset($this->object2ElementMap);
		if(!is_array($map)) throw new DataFlowServiceException("object2ElementMap should be an array", DataFlowServiceException::INVALID_ARGUMENT);
		$this->object2ElementMap = $map;
	}	
	
	private $inGroupLogExp;
	/**
	 * Sets the group log exp used to select the elements
	 * @param LogExp $inGroupLogExp a in group logexp
	 */
	public function setInGroupLogExp($inGroupLogExp) {
		$this->inGroupLogExp = $inGroupLogExp;
	}
	
	private $linkSelector;
	/**
	 * Sets the link selector used to select the linked elements or subitems
	 * Or to specify where to attach the element in insert mode.
	 * @param LinkSelector $ls
	 */
	public function setLinkSelector($ls) {
		$this->linkSelector = $ls;
	}
		
	private $fieldSelectorList;
	private $selectedLanguages = null;
	/**
	 * Sets an optional field selector list that should be used to filter
	 * the fields that are selected and the languages that should be mapped.
	 * @param FieldSelectorList $fieldSelectorList an optional field selector list that should be passed
	 * to ElementService methods getSelectedElementsInGroups
	 * If a mapping between object fields and field selectors has been set using the 'setObject2ElementMap',
	 * then before calling ElementService::getSelectedElementsInGroups, then the field selector list is completed
	 * with any additional field selectors that are present in the mapping but missing in the list.
	 */
	public function setFieldSelectorList($fieldSelectorList) {
		unset($this->selectedLanguages);
		if(isset($fieldSelectorList)) {
			// sets selected languages
			$this->selectedLanguages = $fieldSelectorList->getSelectedLanguages();
			if(isset($this->selectedLanguages)) {
				ArgValidator::assertArrayInclusion('selected languages are not valid installed languages',
					$this->selectedLanguages,
					$this->getTranslationService()->getVisibleLanguage());
			}						
		}
		$this->fieldSelectorList = $fieldSelectorList;
	}
	
	// helpers
	
	/**
	 * Returns a reference on the current DataFlowContext object
	 */
	public function getDataFlowContext() {
		return $this->dataFlowContext;
	}
	
	/**
	 * Writes some data to the output data flow
	 * See DataFlowContext::writeResultToOuput
	 */
	public function writeResultToOutput($resultData) {
		$this->dataFlowContext->writeResultToOutput($resultData, $this);
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$dataFlowContext->assertOriginIsNotPublic();
		if(!isset($this->inGroupLogExp) && !isset($this->groupId) && !isset($this->linkSelector)) throw new DataFlowServiceException("No in group log exp or group id or link selector have been set to define where to fetch elements, add one using the 'setInGroupLogExp' or 'setGroupId' or 'setLinkSelector' method.", DataFlowServiceException::CONFIGURATION_ERROR);

		// builds field selector list by merging provided field selector list and
		// field selectors found in mapping array
		$this->calculatedFieldSelectorList = $this->fieldSelectorList;
		if(isset($this->object2ElementMap)) {
			if(!isset($this->calculatedFieldSelectorList)) $this->calculatedFieldSelectorList = FieldSelectorListArrayImpl::createInstance();
			foreach($this->object2ElementMap as $fs) {
				// if subfields
				if(is_array($fs)) {
					foreach($fs as $fssubfields) {
						if($fssubfields instanceof FieldSelector) {
							if(!$this->calculatedFieldSelectorList->containsFieldSelector($fssubfields->getFieldName(), $fssubfields->getSubFieldName())) {
								$this->calculatedFieldSelectorList->addFieldSelectorInstance($fssubfields);
							}
						}
						else {
							$c = get_class($fssubfields);
							if(!$c) if(is_array($fssubfields)) $c = "Array"; else $c = "Scalar";							
							throw new DataFlowServiceException("object2elementMap should be an array associating object attribut names to field selector instances. Current mapping is not a FieldSelector, but an instanceof '$c'", DataFlowServiceException::CONFIGURATION_ERROR);
						}
					}
				}
				// if field selector
				elseif($fs instanceof FieldSelector) {
					if(!$this->calculatedFieldSelectorList->containsFieldSelector($fs->getFieldName(), $fs->getSubFieldName())) {
						$this->calculatedFieldSelectorList->addFieldSelectorInstance($fs);
					}
				}
				else {
					$c = get_class($fs);
					if(!$c) if(is_array($fs)) $c = "Array"; else $c = "Scalar";							
					throw new DataFlowServiceException("object2elementMap should be an array associating object attribut names to field selector instances. Current mapping is not a FieldSelector, but an instanceof '$c'", DataFlowServiceException::CONFIGURATION_ERROR);
				}
			}
		}
		// stores FieldSelectorList into data flow context so that it can be used in next stages
		if(isset($this->calculatedFieldSelectorList)) {
			$dataFlowContext->setAttribute('FieldSelectorList', $this->calculatedFieldSelectorList);
		}
		
		// checks nature of elements to fetch
		$isSubitem = isset($this->linkSelector) && !isset($this->groupId);
		
		// if subitem then instantiates a GroupBasedWigiiApiClient centered on defined subitems
		// elseif insertOnlyMode, then instantiates a GroupBasedWigiiApiClient centered on the provided group						
		if($this->insertOnlyMode || $isSubitem) {						
			if(!$this->instanciatedApiClient) {				
				$principal = $dataFlowContext->getPrincipal();
				$apiClient = null;
				
				if($isSubitem) {
					// sets configuration of root element if defined
					$configSel = $this->linkSelector->getRootConfigSelector();			
					if(isset($configSel)) {
						// a wigiiNamespace has been specified --> adapts the Principal if needed
						$confWigiiNamespace = $configSel->getWigiiNamespaceName();
						if(isset($confWigiiNamespace)) $confWigiiNamespace = $this->getWigiiNamespaceAdminService()->getWigiiNamespace($principal, $confWigiiNamespace);
						if(isset($confWigiiNamespace) && $principal->hasAdaptiveWigiiNamespace()) {
							$principal->bindToWigiiNamespace($confWigiiNamespace);
						}						
						// a groupLogExp has been specified --> creates a GroupBasedWigiiApiClient centered on theses groups
						$groupExp = $configSel->getGroupLogExp();
					}
				}
				else {
					if(!isset($this->groupId)) throw new DataFlowServiceException("mode INSERT ONLY, but no groupId has been defined to select configuration. Add one using the 'setGroupId' method.", DataFlowServiceException::CONFIGURATION_ERROR);
					$groupExp = lxEq(fs('id'), $this->groupId);
					$confWigiiNamespace = null;
				}
				
				if(isset($groupExp)) {
					$apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, $groupExp);
					// gets wigiiNamespace
					$initialized = false; $firstWigiiNamespace = null;
					$oneWigiiNamespace = true;
					foreach($apiClient->getGroupList()->getListIterator() as $group)
					{					
						$wigiiNamespace = $group->getWigiiNamespace();					
						if($initialized)
						{
							// checks wigiiNamespace unicity						
							if($wigiiNamespace !== $firstWigiiNamespace) $oneWigiiNamespace = false;
						}
						else
						{
							$firstWigiiNamespace = $wigiiNamespace;
							$initialized = true;
						}
					}
					// adapts wigii namespace if needed
					if(is_null($confWigiiNamespace) && $oneWigiiNamespace && $principal->hasAdaptiveWigiiNamespace()) {
						$principal->bindToWigiiNamespace($firstWigiiNamespace);
					}
				}
				// if subitem and API client is still null, then instantiates one to support sub elements.
				if($isSubitem) {					
					if(!isset($apiClient)) $apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, null);
					// centers API client configuration on subitem
					if($this->insertOnlyMode) $apiClient->getConfigService()->selectSubElementsConfig($principal, $this->linkSelector->getOwnerElementId(), $this->linkSelector->getFieldName());
				}
				
				// stores api client in context
				$dataFlowContext->setAttribute('GroupBasedWigiiApiClient', $apiClient, true);
				$this->instanciatedApiClient = true;
			}			
		}
		if(isset($this->linkSelector)) $dataFlowContext->setAttribute('linkSelector', $this->linkSelector);
		if(isset($this->groupId)) $dataFlowContext->setAttribute('groupId', $this->groupId);
	}
	public function processDataChunk($data, $dataFlowContext) {
		if(!isset($data)) return;
		$principal = $dataFlowContext->getPrincipal();
		$apiClient = $dataFlowContext->getAttribute('GroupBasedWigiiApiClient');
		$isSubitem = isset($this->linkSelector) && !isset($this->groupId);
		
		// stores current obj and dataflow context for mapping
		$this->data = $data;
		$this->dataFlowContext = $dataFlowContext;
		
		// if update mode then selects the elements in the DB
		if(!$this->insertOnlyMode) {
			// builds selection log exp using the closure if defined
			if(isset($this->elementSelectorMethod)) $logExp = $this->elementSelectorMethod->invoke($data, $dataFlowContext);
			// else calls the protected method
			else $logExp = $this->buildElementLogExp($data, $dataFlowContext);
			
			// if log exp is not defined, the builds a false log exp on Element ID null in order to still get configuration setup.
			if(is_null($logExp)) $logExp = lxAnd(lxEq(fs_e('id'),null),lxEq(fs_e('id'),false));
			// list filter (puts calculated field selector list only if a custom field selector list has been set, else takes all fields)
			$lf = lf((isset($this->fieldSelectorList)?$this->calculatedFieldSelectorList:null), $logExp);
									
			// if subitem
			if($isSubitem) {
				// selects the sub element(s) and maps the object to them
				$nElt = $apiClient->getElementService()->getSubElementsForField($principal, 
					$this->linkSelector->getOwnerElementId(), $this->linkSelector->getFieldName(), $this, $lf);	
			}
			// else if root elements
			else {
				// if we have a groupId then combines the groupId with the inGroupLogExp 
				if(isset($this->groupId)) {
					$inGroupLogExp = lxInG(lxEq(fs('id'), $this->groupId));
					if(isset($this->inGroupLogExp)) {
						$inGroupLogExp = lxOr($inGroupLogExp, $this->inGroupLogExp);
					}
				}
				else $inGroupLogExp = $this->inGroupLogExp;
				
				// selects the element(s) and maps the object to them
				$nElt = $this->getElementService()->getSelectedElementsInGroups($principal, 
					$inGroupLogExp, $this, $lf);	
				// refreshes API client with instance created by ElementService
				$apiClient = $dataFlowContext->getAttribute('GroupBasedWigiiApiClient');						
			}
			$this->debugLogger()->write("found $nElt elements matching object in flow.");
		}
		// else always creates new element
		else $nElt = 0;
				
		if($nElt < 1) {
			
			// if no selected elements -> creates a new empty instance
			if($isSubitem) $element = $this->createNewSubElement($principal, $apiClient->getConfigService());
			else {
				$element = $this->createNewElement($principal, $apiClient->getGroupList());				
			}
			
			// maps the new element to the object
			// using the closure if defined
			if(isset($this->object2ElementMappingMethod)) $this->object2ElementMappingMethod->invoke($data, $element, $this);   
			// else calls the protected method
			else $this->mapObject2Element($data, $element, $dataFlowContext) ;
		}						
	}
	public function endOfStream($dataFlowContext) {
		/* nothing to do */
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		return $this->endOfStream($dataFlowContext);
	}	
	
	// implementation

	/**
	 * Builds a LogExp to select the element based on the current object in the data flow	 
	 * @param StdClass|Array $obj the current data in the data flow
	 * @param DataFlowContext $dataFlowContext the current data flow context
	 * @return LogExp a logical expression that will be used to select the element, 
	 * for example : return lxEq(fs_e('id'), $obj->id)
	 * @throws DataFlowServiceException in case of error
	 */
	protected function buildElementLogExp($obj, $dataFlowContext) {
		// by default maps obj->id to element->id if obj->id is defined
		$id = null;
		if($obj instanceof stdClass) {
			$id = $obj->id;
		}
		elseif(is_array($obj)) {
			$id = $obj['id'];
		}
		if(isset($id)) return lxEq(fs_e('id'), $id); 
		// else
		else throw new DataFlowServiceException("implement the method 'buildElementLogExp' into a subclass or set a closure using the 'setElementSelectorMethod' method", DataFlowServiceException::CONFIGURATION_ERROR);
	}
	
	/**
	 * Maps the current object in the data flow to the selected element
	 * @param StdClass|Array $obj the current data in the dataflow
	 * @param ElementP|Element $elementP the selected element with current principal rights 
	 * or a new Element if none was selected.
	 * @param DataFlowContext $dataFlowContext the current data flow context, 
	 * that can be used to write some output in the data flow for next steps
	 * @throws DataFlowServiceException in case of error
	 */
	protected function mapObject2Element($obj, $elementP, $dataFlowContext) {
		// by default maps obj attribute name to element field with same name
		// if obj has a sub array attached to attribute, then maps with element subfields
		// non matching fields/subfields are ignored
		// pRights are ignored
		$element = $elementP->getDbEntity();		
		if(isset($this->object2ElementMap)) {
		    $this->mapObject2ElementUsingMap($obj, $element, $dataFlowContext, $this->object2ElementMap);
		}
		else {
		    $this->mapObject2ElementUsingNames($obj, $element, $dataFlowContext);
		} 
		// if not last stage in the data flow, then pushes the elementP further.
		if(!$dataFlowContext->isCurrentStepTheLastStep()) {
			$dataFlowContext->writeResultToOutput($elementP, $this);
		}
	}

	private function mapObject2ElementUsingNames($obj, $element, $dataFlowContext) {
	    $principal = $dataFlowContext->getPrincipal();
	    $fl = $element->getFieldList();			
		foreach($obj as $attr => $val) {
			// checks for the existence of a field mapping closure
			$c = $this->getFieldMappingMethod($attr);
			// if found, then executes it
			if(isset($c)) $c($val, $element, $dataFlowContext);
			// else uses the default mapping based on name matching
			else {
				$field = $fl->doesFieldExist($attr);
				if($field) {
					$dt = $field->getDataType();				
					if(isset($dt)) {
						$valArr = is_array($val);
						// if subfields then goes through sub array
						if($valArr && $dt->hasSubfields()) {
							$x = $dt->getXml(); 
							foreach($val as $subFieldName => $subfFieldValue) {
								$dbFieldParams = $x->xpath($subFieldName);
								// if matching subfield then trie to set value
								if($dbFieldParams) {
									$dbFieldParams = $dbFieldParams[0];
									if(is_array($subfieldValue) && 
										strtolower((string)$dbFieldParams['type']) != 'multiple-select' &&
										((string)$dbFieldParams['multiLanguage']) != '1'
									) throw new DataFlowServiceException("values as Array are only permitted for subfields of type 'multiple-select' or having 'multiLanguage' set to 1", DataFlowServiceException::DATA_FORMAT_ERROR);
									else $this->setFieldValue($subfFieldValue, fs($attr, $subFieldName), $element, $principal);
								}
							}
						}
						// else tries to set default 'value' subfield if exist
						else {
							$dbFieldParams = $dt->getXml()->xpath('value');
							if($dbFieldParams) {
								$dbFieldParams = $dbFieldParams[0];
								if($valArr && 
									strtolower((string)$dbFieldParams['type']) != 'multiple-select' &&
									((string)$dbFieldParams['multiLanguage']) != '1'
								) {
									//$this->debugLogger()->write('type='.(string)$dbFieldParams['type'].', multiLanguage='.(string)$dbFieldParams['multiLanguage']);
									//$this->debugLogger()->write($dbFieldParams);
									throw new DataFlowServiceException("values as Array are only permitted for subfields of type 'multiple-select' or having 'multiLanguage' set to 1", DataFlowServiceException::DATA_FORMAT_ERROR);
								}
								else $this->setFieldValue($val, fs($attr, 'value'), $element, $principal);
							}
							else throw new DataFlowServiceException("default subfield 'value' does not exist in data type config, the desired subfield name should be specified in the object in the data flow");
						}
					}				
				}
			}
		}
	}
	
	private function mapObject2ElementUsingMap($obj, $element, $dataFlowContext, $map) {
	    $principal = $dataFlowContext->getPrincipal();
	    foreach($obj as $attr => $val) {
			// checks for the existence of a field mapping closure
			$c = $this->getFieldMappingMethod($attr);
			// if found, then executes it
			if(isset($c)) $c($val, $element, $dataFlowContext);
			// else uses the defined mapping
			else {			
				// gets mapping
				$mapping = $map[$attr];
				if(isset($mapping)) {
					if($mapping instanceof FieldSelector) {
						// validates mapping et sets value
						$this->validateMapping($val, $mapping, $element);
						$this->setFieldValue($val, $mapping, $element, $principal);
					}
					elseif(is_array($mapping)) {
						if(!is_array($val)) throw new DataFlowServiceException("object2element mapping defines some subfields for object attribute '$attr', but attribute value is not an array", DataFlowServiceException::DATA_FORMAT_ERROR);
						// goes through subfields
						foreach($val as $subFieldName => $subFieldVal) {
							$subFieldMapping = $mapping[$subFieldName];
							if(isset($subFieldMapping)) {
								if($subFieldMapping instanceof FieldSelector) {
									// validates mapping et sets value
									$this->validateMapping($subFieldVal, $subFieldMapping, $element);
									$this->setFieldValue($subFieldVal, $subFieldMapping, $element, $principal);
								}
								else throw new DataFlowServiceException("the mapping between an object subfield and an element can only be a FieldSelector instance", DataFlowServiceException::CONFIGURATION_ERROR); 
							}
						}
					}
					else throw new DataFlowServiceException("the mapping between an object attribute and an element can only be a FieldSelector instance or an array of subfields mapping", DataFlowServiceException::CONFIGURATION_ERROR);
				}
			}
		}
	}
	private function validateMapping($val, $fieldSelector, $element) {
		$field = $element->getFieldList()->doesFieldExist($fieldSelector->getFieldName());
		if($field) {						
			$dt = $field->getDataType();
			if(!isset($dt)) throw new DataFlowServiceException("Field '".$fieldSelector->getFieldName()."' used in object2Element map has no DataType in element configuration.", DataFlowServiceException::CONFIGURATION_ERROR);
			$subFieldName = $fieldSelector->getSubFieldName();
			if(empty($subFieldName)) $subFieldName = 'value';
			$dbFieldParams = $dt->getXml()->xpath($subFieldName);
			if($dbFieldParams) {
				$dbFieldParams = $dbFieldParams[0];
				if(is_array($val) && 
					strtolower((string)$dbFieldParams['type']) != 'multiple-select' &&
					((string)$dbFieldParams['multiLanguage']) != '1'
				) throw new DataFlowServiceException("values as Array are only permitted for subfields of type 'multiple-select' or having 'multiLanguage' set to 1", DataFlowServiceException::DATA_FORMAT_ERROR);				
			}
			else throw new DataFlowServiceException("Subfield '$subFieldName' used in object2Element map is not defined in DataType '".$dt->getDataTypeName()."' configuration.", DataFlowServiceException::CONFIGURATION_ERROR);
		}
		else throw new DataFlowServiceException("Field '".$fieldSelector->getFieldName()."' used in object2Element map is not defined in element configuration.", DataFlowServiceException::CONFIGURATION_ERROR);
	}
	
	/**
	 * Sets a value in the current element given a field selector
	 * Precondition: the fieldselector has already been validated against the element configuration and
	 * the value type (Array or Scalar) has already been validated against 'multiple-select' or 'multiLanguage' configuration attributes.
	 * Some custom checks can still be added if needed.
	 * @param Any $value object attribute value
	 * @param FieldSelector $fieldSelector the field selector describing which field should be updated in the element 
	 * @param Element $element the element to update
	 * @param Principal $principal the current principal doing the job
	 */
	protected function setFieldValue($value, $fieldSelector, $element, $principal) {
		// gets data type xml configuration	
		$fieldName = $fieldSelector->getFieldName();
		$subFieldName = $fieldSelector->getSubFieldName();
		if(empty($subFieldName)) $subFieldName = 'value';	
		$dbFieldParams = $element->getFieldList()
			->getField($fieldName)
			->getDataType()->getXml()->xpath($subFieldName);
		$dbFieldParams = $dbFieldParams[0];
		
		// processes multiple-select value
		if(strtolower((string)$dbFieldParams['type']) === 'multiple-select' && is_scalar($value)) {
			$value = array($value => $value);
		}
		// processes multiLanguage value
		elseif(((string)$dbFieldParams['multiLanguage']) == '1') {
			// if an array of translated values, then only keeps selected or valid languages
			if(is_array($value)) {
				if(isset($this->selectedLanguages)) $languages = $this->selectedLanguages;
				else $languages = $this->getTranslationService()->getVisibleLanguage();
				$value = array_intersect_key($value, $languages);				
			}
			else $value = array($this->getTranslationService()->getLanguage() => $value);
		}
		
		// sets value into the element
		$element->setFieldValue($value, $fieldName, $subFieldName);
	}
	
	/**
	 * Creates a new empty element instance to be inserted based on 
	 * the configuration given by the provided groupList.
	 * @return Element
	 */
	protected function createNewElement($principal, $groupList) {
		if(!isset($groupList) || $groupList->isEmpty()) throw new DataFlowServiceException("groupList cannot be empty", DataFlowServiceException::INVALID_ARGUMENT);
		// retrieves Module using first group in the list
		$module = null;
		foreach($groupList->getListIterator() as $group) {
			$module = $group->getModule();
			break;
		}
		// creates element FieldList and fills it with configuration given by grouplist
		$fieldList = FieldListArrayImpl::createInstance(true,true);
		$this->getConfigService()->getGroupsFields($principal, $groupList, null, $fieldList);
		// creates element instance
		return Element::createInstance($module, $fieldList, WigiiBagBaseImpl::createInstance());				
	}
	
	/**
	 * Creates a new empty sub element instance to be inserted based on 
	 * the configuration given by the provided Sub element config Service.
	 * @param ConfigServiceSubElementImpl $subElementConfigService
	 * @return Element
	 */
	protected function createNewSubElement($principal, $subElementConfigService) {
		$module = $subElementConfigService->getCurrentModule();
		// creates element FieldList and fills it with the configuration of the sub element
		$fieldList = FieldListArrayImpl::createInstance(true,true);
		$subElementConfigService->getFields($principal, $module, null, $fieldList);
		// creates element instance
		return Element::createInstance($module, $fieldList, WigiiBagBaseImpl::createInstance());				
	}	
	
	// ElementPList implementation		
	
	public function addElementP($elementP) {
	    // extracts element info and stamps it
	    if(!$this->authoSStamp) $this->getAuthorizationService()->getStamp($this, "setAuthorizationServiceStamp");
	    if($this->authoSStamp) $elementP->computeElementInfo($this->dataFlowContext->getPrincipal(),null,$this->authoSStamp);
	    
		// maps the element to the object
		// using the closure if defined
		if(isset($this->object2ElementMappingMethod)) $this->object2ElementMappingMethod->invoke($this->data, $elementP, $this);   
		// else calls the protected method
		else $this->mapObject2Element($this->data, $elementP, $this->dataFlowContext);		
	}
	
	public function createFieldList() {return FieldListArrayImpl::createInstance();}
	
	public function createWigiiBag() {return WigiiBagBaseImpl::createInstance();}
		
	public function getListIterator() {throw new ElementServiceException("The ElementPListDataFlowConnector cannot be iterated. It is a forward only push of elements into the data flow.", ElementServiceException::UNSUPPORTED_OPERATION);}
	
	public function isEmpty() {return ($this->nElements == 0);}
	
	public function count() {return $this->nElements;}
	
	public function notifyCalculatedGroupList($groupList) {
		if(!$this->instanciatedApiClient) {
			$this->dataFlowContext->setAttribute('GroupBasedWigiiApiClient', ServiceProvider::getGroupBasedWigiiApiClient($this->dataFlowContext->getPrincipal(), $groupList), true);
			$this->instanciatedApiClient = true;
		} 
	}
}