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
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.ClientAdminService
 *  
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/* wigii ClientAdminService implementation
 * Created by CWE on 12 juin 09
 */
class ClientAdminServiceImpl implements ClientAdminService
{
	private $_debugLogger;
	private $_executionSink;
	private $authoS;
	private $dflowS;
	private $wigiiNamespaceAS;
	private $moduleAS;
	private $gAS;
	private $configS;
	private $translationService;
	
	/**
	 * client cache
	 * map clientName -> Client
	 */
	private $clientCache;


	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ClientAdminServiceImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("ClientAdminServiceImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

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
		
	public function setDataFlowService($dataFlowService)
	{
		$this->dflowS = $dataFlowService;
	}
	protected function getDataFlowService()
	{
		// autowired
		if(!isset($this->dflowS))
		{
			$this->dflowS = ServiceProvider::getDataFlowService();
		}
		return $this->dflowS;
	}
	
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
	
	public function setModuleAdminService($moduleAdminService)
	{
		$this->moduleAS = $moduleAdminService;
	}
	protected function getModuleAdminService()
	{
		// autowired
		if(!isset($this->moduleAS))
		{
			$this->moduleAS = ServiceProvider::getModuleAdminService();
		}
		return $this->moduleAS;
	}
	
	public function setGroupAdminService($groupAdminService)
	{
		$this->gAS = $groupAdminService;
	}
	protected function getGroupAdminService()
	{
		// autowired
		if(!isset($this->gAS))
		{
			$this->gAS = ServiceProvider::getGroupAdminService();
		}
		return $this->gAS;
	}
	
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
		
	public function setTranslationService($translationService){
		$this->translationService = $translationService;
		return $this;
	}
	protected function getTranslationService(){
		//autowired
		if(!isset($this->translationService)){
			$this->translationService = ServiceProvider::getTranslationService();
		}
		return $this->translationService;
	}
	
	// service implementation

	public function getClient($principal, $clientName)
	{
		$this->executionSink()->publishStartOperation("getClient", $principal);
		try
		{
			$returnValue = $this->getCachedClient($clientName);
			if(!isset($returnValue))
			{
				$returnValue = $this->createClientInstance($principal, $clientName);
				$this->cacheClient($returnValue);
			}
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getClient", $e, $principal);
			throw new ClientAdminServiceException('',ClientAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getClient", $principal);
		return $returnValue;
	}

	public function getEmptyClient()
	{
		$this->executionSink()->publishStartOperation("getEmptyClient");
		try
		{
			$returnValue = $this->getCachedClient($this->getEmptyClientName());
			if(!isset($returnValue))
			{
				$returnValue = $this->createEmptyClientInstance();
				$this->cacheClient($returnValue);
			}
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getEmptyClient", $e);
			throw new ClientAdminServiceException('',ClientAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getEmptyClient");
		return $returnValue;
	}

	/**
	 * This implementation returns EmptyClient
	 */
	public function getDefaultClient()
	{
		return $this->getEmptyClient();
	}
	
	public function syncDimension($principal, $source, $dimension, $parentGroupId=null,
			$checkDeletedValues=1, $markNewValuesAsImportant=true) {
		$this->executionSink()->publishStartOperation("syncDimension");
		try
		{
			if(empty($dimension)) throw new ClientAdminServiceException('dimension cannot be null. Should be either a group id or a unique group name', ClientAdminServiceException::INVALID_ARGUMENT);
			if($checkDeletedValues < 0 || $checkDeletedValues > 2) throw new ClientAdminServiceException('checkDeletedValues can be only one of 0=no check, 1=mark as deprecated, 2=delete values', ClientAdminServiceException::INVALID_ARGUMENT);
			// checks authorization
			$this->assertPrincipalAuthorizedForSyncDimension($principal, $dimension);
			if($principal->hasAdaptiveWigiiNamespace()) {
				$pNamespace = $principal->getWigiiNamespace();
				$principal->bindToWigiiNamespace($this->getWigiiNamespaceAdminService()->getSetupWigiiNamespace($principal));
			}
			else $pNamespace = null;
			
			// gets dimension group
			$dimGroupP = $this->getGroupPForDimension($principal, $dimension, $parentGroupId);
			if($dimGroupP->getRights() == null || !$dimGroupP->getRights()->canWriteElement()) $this->getAuthorizationService()->fail($principal, 'has no right to update dimension '.$dimension);
			
			$dfS = $this->getDataFlowService();
			// updates the values
			$updatedIds = $dfS->processDataSource($principal, $source, $this->getDfaslForUpdateDimension($dimGroupP->getId(), $markNewValuesAsImportant));
			
			// delete obsolete values (or mark them as deprecated)
			if($checkDeletedValues > 0) {
				$dfS->processDumpableObject($principal, 
						elementPList(lxInG(lxEq(fs('id'), $dimGroupP->getId())),
							lf(fsl(fs_e('state_important1'), fs_e('state_deprecated')), lxNotIn(fs_e('id'), $updatedIds))), 
						$this->getDfaslForDeleteDimension($checkDeletedValues));
			}
			$returnValue = count($updatedIds);
			if(isset($pNamespace)) $principal->bindToWigiiNamespace($pNamespace);
		}
		catch (ClientAdminServiceException $casE) {
			$this->executionSink()->publishEndOperationOnError("syncDimension", $casE, $principal);
			throw $casE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("syncDimension", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("syncDimension", $e);
			throw new ClientAdminServiceException('',ClientAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("syncDimension");
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForSyncDimension($principal, $dimension)
	{ 
		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "ClientAdminService", "syncDimension");
	}
	/**
	 * Gets or creates the group holding the dimension values.
	 * @param Principal $principal authenticated user performing the operation 
	 * @param Int|String $dimension the dimension identifier. If a number, then assumes it is a group id, if a string assumes it is a group name.
	 * @param Int $parentGroupId defines the parent group containing the dimension. Optional if dimension is already a group id.
	 * @return GroupP
	 * @throws ClientAdminServiceException if selected group is not unique or does not exist.
	 */
	protected function getGroupPForDimension($principal, $dimension, $parentGroupId=null) {
		// checks if group exists in db
		if(is_numeric($dimension)) {
			$groupLogExp = lxEq(fs('id'), $dimension);
			$isGroupId = true;
		}
		else {
			$isGroupId = false;
			$module = $this->getModuleAdminService()->getModule($principal, 'Dimensions');
			$wigiiNamespace = $this->getWigiiNamespaceAdminService()->getSetupWigiiNamespace($principal);
				
			$groupLogExp = lxAnd(
				lxEq(fs('wigiiNamespace'), $wigiiNamespace->getWigiiNamespaceName()),
				lxEq(fs('module'), $module->getModuleName()),				
				lxEq(fs('groupname'), $dimension)
			);
			if(isset($parentGroupId)) {
				$groupLogExp->addOperand(lxEq(fs('id_group_parent'), $parentGroupId));
			}
		}
		$groupPList = GroupPListArrayImpl::createInstance();
		$listFilter = lf(fsl(fs('id')), $groupLogExp, null, 1, 1); 
		$gaS = $this->getGroupAdminService();
		$gaS->getSelectedGroups($principal, $listFilter, $groupPList);
		switch($listFilter->getTotalNumberOfObjects()) {
			case 0: 
				// groupId not found
				if($isGroupId) throw new ClientAdminServiceException("dimension $dimension does not exist in database", ClientAdminServiceException::INVALID_ARGUMENT);
				// else creates new group
				else {
					$groupP = GroupP::createInstance(Group::createInstance(array(
						'groupname' => $dimension,
						'wigiiNamespace' => $wigiiNamespace,
						'module' => $module,
						'id_group_parent' => $parentGroupId
						)));					
					$gaS->persistGroup($principal, $groupP->getGroup());
					$groupP->setRights(PrincipalRights::createInstance(array('canWriteElement' => true)));					
				}
				break;
			case 1: $groupP = reset($groupPList->getListIterator()); break;
			default: throw new ClientAdminServiceException("group $dimension is not unique for module ".$module->getModuleName().' in namespace '.$wigiiNamespace->getWigiiNamespaceName(), ClientAdminServiceException::INVALID_ARGUMENT);
		}
		return $groupP;
	}
	/**
	 * @param Int $groupId the group id holding the dimension values.
	 * @param Boolean $markNewValuesAsImportant if true then newly inserted values in the dimension are marked as 'Important1' else no special markup.
	 * @return DataFlowActivitySelectorList which should return at last stage an array with the updated elements ids.
	 */
	protected function getDfaslForUpdateDimension($groupId, $markNewValuesAsImportant=true) {
		$returnValue = dfasl(
			dfas('FilterDuplicatesAndSortDFA'),
			dfas('BoxingDFA'),
			dfas('MapObject2ElementDFA', 
				'setGroupId', $groupId,
				'setElementSelectorMethod', CallableObject::createInstance('elementSelectorMethodForUpdateDimension', $this),
				'setFieldSelectorList', fsl(fs('value'), fs_e('state_important1'), fs_e('state_deprecated'))
			)
		);
		
		if($markNewValuesAsImportant) {
			$cfsMap = cfsMap(cfs('__element', false, 'state_deprecated'), 
					cfs('__element', fx('ctlIf', fx('eq', fs_e('id'), null), true, false), 'state_important1'),
					cfs('__element', fx('ctlIf', fx('eq', fs_e('id'), null), array2str(array('timestamp'=>time(), 'message'=>'syncDimensionNewValue')), null), 'state_important1Info'));			
		}
		else {
			$cfsMap = cfsMap(cfs('__element', false, 'state_deprecated'));
		}
		$returnValue->addDataFlowActivitySelectorInstance(dfas('ElementSetterDFA', 'setCalculatedFieldSelectorMap', $cfsMap));
		
		$returnValue->addDataFlowActivitySelectorInstance(dfas('ElementDFA', 
			'setMode', ElementDFA::MODE_PERSIST,
			'setIgnoreLockedElements', true,
			'setReloadElementAfterInsert', false));
		
		$returnValue->addDataFlowActivitySelectorInstance(dfas('MapElement2ValueDFA',
			'setElement2ValueFuncExp', fs_e('id')));
		
		$returnValue->addDataFlowActivitySelectorInstance(dfas('ArrayBufferDFA'));
		
		return $returnValue;
	}
	/**
	 * The elementSelectorMethod used by MapObject2ElementDFA when updating dimensions.
	 * Selects dimension values based on the value.
	 */
	public function elementSelectorMethodForUpdateDimension($data, $dataFlowContext) {
		return lxEq(fs('value'), $data->value);
	}
	
	/**
	 * @param Int $checkDeletedValues one of 1, 2. 
	 * If 1=MarkDeletedValuesAsDeprecated, then dimension values that do not exist anymore in the source are marked as 'Deprecated';
	 * If 2=DeleteNonExistingValues, then dimension values that do not exist anymore in the source are deleted.
	 * @return DataFlowActivitySelectorList
	 */
	protected function getDfaslForDeleteDimension($checkDeletedValues=1) {
		if($checkDeletedValues == 1) {
			return dfasl(dfas('ElementSetterDFA',
				'setCalculatedFieldSelectorMap', cfsMap(cfs('__element', true, 'state_deprecated'), 
						cfs('__element', array2str(array('timestamp'=>time(), 'message'=>'syncDimensionDeletedValue')), 'state_deprecatedInfo'),
						cfs('__element', false, 'state_important1'))),
			dfas('ElementDFA', 'setMode', ElementDFA::MODE_PERSIST, 'setIgnoreLockedElements', true));
		}
		elseif($checkDeletedValues == 2) {
			return dfasl(dfas('ElementDFA', 'setMode', ElementDFA::MODE_DELETE, 'setIgnoreLockedElements', true));
		}
		else throw new ClientAdminServiceException('checkDeletedValues can only take value 1 or 2', ClientAdminServiceException::INVALID_ARGUMENT);
	}
	
	
	public function syncCfgFields($principal, $groupId, $fileName=null,
			$checkDeletedFields=1, $markNewFieldsAsImportant=true) {
		$this->executionSink()->publishStartOperation("syncCfgFields");
		try
		{
			if(!isset($groupId)) throw new ClientAdminServiceException('groupId cannot be null.', ClientAdminServiceException::INVALID_ARGUMENT);
			if($checkDeletedFields < 0 || $checkDeletedFields > 2) throw new ClientAdminServiceException('checkDeletedFields can be only one of 0=no check, 1=mark as deprecated, 2=delete fields', ClientAdminServiceException::INVALID_ARGUMENT);
			// checks authorization
			$this->assertPrincipalAuthorizedForSyncCfgFields($principal);
			
			$configS = $this->getConfigService();
			// checks for method 'getClientConfigFolderPath'
			if(!method_exists($configS, 'getClientConfigFolderPath')) throw new ClientAdminServiceException('method syncCfgField depends on method ConfigService::getClientConfigFolderPath which does not exist in the injected implementation. Please change the ConfigService dependency.', ClientAdminServiceException::CONFIGURATION_ERROR);
			
			// gets root group
			$gAS = $this->getGroupAdminService();
			$rootGroup = $gAS->getGroup($principal, $groupId, $gAS->getFieldSelectorListForGroupWithoutDetail());
			if(!isset($rootGroup)) throw new ClientAdminServiceException("group $groupId does not exist into database", ClientAdminServiceException::INVALID_ARGUMENT);
			if($rootGroup->getRights() == null || !$rootGroup->getRights()->canWriteElement()) $this->getAuthorizationService()->fail($principal, 'has no right to synchronize configuration fields in group '.$groupId);
			$rootGroup = $rootGroup->getDbEntity();
			
			// gets configuration files
			$clientConfigPath = $configS->getClientConfigFolderPath($principal);
			if(!isset($fileName)) $fileName = '*_{config,config_g}.xml';
			$configFiles = glob($clientConfigPath.$fileName, GLOB_BRACE);
			//$this->debugLogger()->write("found config files :\n".implode("\n", $configFiles));
			
			// synchronizes each configuration file
			$pWigiiNamespace = $principal->getWigiiNamespace();
			$returnValue = count($configFiles);
			if($returnValue > 0) {
				$fieldList = FieldListArrayImpl::createInstance(true, true);
				$dfS = $this->getDataFlowService();
				foreach($configFiles as $configFile) {				
					$configFile = str_replace($clientConfigPath, '', $configFile);
					$groupConfig = (strpos($configFile, '_config_g.xml') > 0);
					if($groupConfig) $configFile = str_replace('_config_g.xml', '', $configFile);
					else $configFile = str_replace('_config.xml', '', $configFile);
					
					$configElements = explode('_', $configFile);
					if($groupConfig) {
						if(count($configElements) == 3) {
							$wigiiNamespace = $configElements[0];
							$module = $configElements[1];
							$group = $configElements[2];
						}
						else {
							$wigiiNamespace = null;
							$module = $configElements[0];
							$group = $configElements[1];
						}
					}
					else {
						if(count($configElements) == 2) {
							$wigiiNamespace = $configElements[0];
							$module = $configElements[1];
							$group = null;
						}
						else {
							$wigiiNamespace = null;
							$module = $configElements[0];
							$group = null;
						}
					}
					
					//$this->debugLogger()->write("analyses configuration of wigiiNamespace '$wigiiNamespace', module '$module' and group '$group'");
					
					// ignores Admin and Home module
					if($module != 'Admin' && $module != 'Home') {
						$fieldList->reset();
						// binds to namespace
						$wigiiNamespace = $this->getWigiiNamespaceAdminService()->getWigiiNamespace($principal, $wigiiNamespace);
						$principal->bindToWigiiNamespace($wigiiNamespace);
						// gets field list
						if(isset($group)) {
							$group = $gAS->getGroupWithoutDetail($principal, $group);
							$configS->getGroupFields($principal, $group, null, $fieldList);
							$module = $group->getModule();
						}
						else {
							$module = $this->getModuleAdminService()->getModule($principal, $module);
							$configS->getFields($principal, $module, null, $fieldList);
						}
						
						// creates folder path from root
						$principal->bindToWigiiNamespace($rootGroup->getWigiiNamespace());
						$g = $rootGroup;
						// namespace level
						$g = $gAS->getOrCreateSubGroupByName($principal, $g->getId(), $wigiiNamespace->getWigiiNamespaceUrl());
						// module level
						$g = $gAS->getOrCreateSubGroupByName($principal, $g->getId(), $module->getModuleUrl());
						// group level
						if(isset($group)) {
							$g = $gAS->getOrCreateSubGroupByName($principal, $g->getId(), $group->getId());
						}
												
						// updates the fields
						$updatedIds = $dfS->processFieldList($principal, $fieldList, $this->getDfaslForUpdateCfgFields($principal, $g->getGroup(), $markNewFieldsAsImportant));
						$fieldList->freeMemory();
							
						// delete obsolete fields (or mark them as deprecated)
						if($checkDeletedFields > 0) {
							$dfS->processDumpableObject($principal,
									elementPList(lxInG(lxEq(fs('id'), $g->getId())),
											lf(fsl(fs_e('state_important1'), fs_e('state_deprecated')), lxNotIn(fs_e('id'), $updatedIds))),
									$this->getDfaslForDeleteCfgFields($checkDeletedFields));
						}
					}
				}
				if(isset($pNamespace)) $principal->bindToWigiiNamespace($pWigiiNamespace);
			}			
		}
		catch (ClientAdminServiceException $casE) {
			$this->executionSink()->publishEndOperationOnError("syncCfgFields", $casE, $principal);
			throw $casE;
		}
		catch (AuthorizationServiceException $asE){
			$this->executionSink()->publishEndOperationOnError("syncCfgFields", $asE, $principal);
			throw $asE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("syncCfgFields", $e);
			throw new ClientAdminServiceException('',ClientAdminServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("syncCfgFields");
		return $returnValue;
	}
	protected function assertPrincipalAuthorizedForSyncCfgFields($principal)
	{
		if(!isset($principal)) throw new ClientAdminServiceException('principal cannot be null', ClientAdminServiceException::INVALID_ARGUMENT);
		// checks general authorization
		$this->getAuthorizationService()->assertPrincipalAuthorized($principal, "ClientAdminService", "syncCfgFields");
	}
	/**
	 * @param Principal $principal authenticated user performing the operation
	 * @param Group $group the group holding the configuration fields.
	 * @param Boolean $markNewFieldsAsImportant if true then newly inserted fields in the group are marked as 'Important1' else no special markup.
	 * @return DataFlowActivitySelectorList which should return at last stage an array with the updated elements ids.
	 */
	protected function getDfaslForUpdateCfgFields($principal, $group, $markNewFieldsAsImportant=true) {
		// builds field selector list with all fields from group
		$fsl = FieldSelectorListArrayImpl::createInstanceAsFieldList(true, false, true);
		$this->getConfigService()->getGroupFields($principal, $group, null, $fsl);
		// builds dfasl
		$returnValue = dfasl(
				dfas('CallbackDFA',
					'initializeContext', array('fieldCounter' => 0),
					'setProcessDataChunkCallback', CallableObject::createInstance('field2WigiiCfgFieldProcessDataChunkCallback', $this)
				),
				dfas('MapObject2ElementDFA',
						'setGroupId', $group->getId(),
						'setElementSelectorMethod', CallableObject::createInstance('elementSelectorMethodForUpdateCfgFields', $this),
						'setFieldSelectorList', $fsl
				)
		);
	
		if($markNewFieldsAsImportant) {
			$cfsMap = cfsMap(cfs('__element', false, 'state_deprecated'), 
					cfs('__element', fx('ctlIf', fx('eq', fs_e('id'), null), true, false), 'state_important1'),
					cfs('__element', fx('ctlIf', fx('eq', fs_e('id'), null), array2str(array('timestamp'=>time(), 'message'=>'syncCfgFieldsNewValue')), null), 'state_important1Info'));
		}
		else {
			$cfsMap = cfsMap(cfs('__element', false, 'state_deprecated'));
		}
		$returnValue->addDataFlowActivitySelectorInstance(dfas('ElementSetterDFA', 'setCalculatedFieldSelectorMap', $cfsMap));
	
		$returnValue->addDataFlowActivitySelectorInstance(dfas('ElementDFA',
				'setMode', ElementDFA::MODE_PERSIST,
				'setIgnoreLockedElements', true,
				'setReloadElementAfterInsert', false));
	
		$returnValue->addDataFlowActivitySelectorInstance(dfas('MapElement2ValueDFA',
				'setElement2ValueFuncExp', fs_e('id')));
	
		$returnValue->addDataFlowActivitySelectorInstance(dfas('ArrayBufferDFA'));
	
		return $returnValue;
	}
	/**
	 * The elementSelectorMethod used by MapObject2ElementDFA when updating configuration fields.
	 * Selects configuration field based on the field name.
	 */
	public function elementSelectorMethodForUpdateCfgFields($data, $dataFlowContext) {
		//$this->debugLogger()->write(json_encode($data));		
		return lxEq(fs('name'), $data->name);
	}
	/**
	 * The processDataChunk callback used by CallbackDFA when updating configuration fields.
	 * Converts a FieldWithSelectedSubfields instance to a WigiiCfgField StdClass.
	 * See module WigiiCfgField for details about the available fields.
	 */
	public function field2WigiiCfgFieldProcessDataChunkCallback($data, $callbackDFA) {
		$returnValue = array();
		
		// ordinal
		$returnValue['ordinal'] = $callbackDFA->getValueInContext('fieldCounter')+1;
		$callbackDFA->setValueInContext('fieldCounter', $returnValue['ordinal']);
		
		// field name and data type
		$field = $data->getField();
		$fName = $field->getFieldName();
		$returnValue['name'] = $fName;
		if($field->getDataType() != null) $returnValue['type'] = $field->getDataType()->getDataTypeName();
		
		// all xml attributes
		$xml = $field->getXml();		
		foreach($xml->attributes() as $name => $val) {
			$name = (string)$name;
			$val = (string)$val;
			if($name != 'type') {
				$returnValue[$name] = $val;
			}
		}
		
		// func exp
		$fx = $field->getFuncExp();
		if(isset($fx)) $returnValue['funcExp'] = TechnicalServiceProvider::getFieldSelectorFuncExpParser()->funcExpToString($fx);
		
		// field xml
		$returnValue['xml'] = $xml->asXml();
		
		// labels
		$ts = $this->getTranslationService();
		$p = $callbackDFA->getDataFlowContext()->getPrincipal();
		$labels = array();
		foreach($ts->getVisibleLanguage() as $lang => $language) {
			$labels[$lang] = $ts->t($p, $fName, $xml, $lang);
		}
		$returnValue['label'] = $labels;
		//$this->debugLogger()->write(json_encode($labels));
		
		// pushes object into data flow
		$callbackDFA->writeResultToOutput((object)$returnValue);
	}
	/**
	 * @param Int $checkDeletedFields one of 1, 2.
	 * If 1=MarkDeletedFieldsAsDeprecated, then configuration fields that do not exist anymore in the source are marked as 'Deprecated';
	 * If 2=DeleteNonExistingFields, then configuration fields that do not exist anymore in the source are deleted.
	 * @return DataFlowActivitySelectorList
	 */
	protected function getDfaslForDeleteCfgFields($checkDeletedFields=1) {
		if($checkDeletedFields == 1) {
			return dfasl(dfas('ElementSetterDFA',
					'setCalculatedFieldSelectorMap', cfsMap(cfs('__element', true, 'state_deprecated'),
							cfs('__element', array2str(array('timestamp'=>time(), 'message'=>'syncCfgFieldsDeletedValue')), 'state_deprecatedInfo'),
							cfs('__element', false, 'state_important1'))),
					dfas('ElementDFA', 'setMode', ElementDFA::MODE_PERSIST, 'setIgnoreLockedElements', true));
		}
		elseif($checkDeletedFields == 2) {
			return dfasl(dfas('ElementDFA', 'setMode', ElementDFA::MODE_DELETE, 'setIgnoreLockedElements', true));
		}
		else throw new ClientAdminServiceException('checkDeletedFields can only take value 1 or 2', ClientAdminServiceException::INVALID_ARGUMENT);
	}
	
	
	
	/**
	 * creates a client instance with the given name
	 * extension point to use when wanting to create an instance based on a serialized object.
	 * returns a Client instance
	 */
	protected function createClientInstance($principal, $clientName)
	{
		$returnValue = Client::createInstance();
		$returnValue->setClientName($clientName);
		return $returnValue;
	}
	protected function createEmptyClientInstance()
	{
		$returnValue = Client::createInstance();
		$returnValue->setClientName($this->getEmptyClientName());
		return $returnValue;
	}

	/**
	 * Returns the name to use for the empty client.
	 * This implementation returns ''
	 */
	protected function getEmptyClientName()
	{
		return '';
	}

	// Cache management

	protected function getCachedClient($clientName)
	{
		if(!isset($this->clientCache)) return null;
		$returnValue = $this->clientCache[$clientName];
		if(!isset($returnValue))
		{
			$this->debugLogger()->write("$clientName not found in cache");
			 return null;
		}
		return $returnValue;
	}

	protected function cacheClient($client)
	{
		if(is_null($client)) return;
		$clientName = $client->getClientName();
		$this->clientCache[$clientName] = $client;
		$this->debugLogger()->write("stores client $clientName");
	}
}


