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
 * Wigii Dimension DataFlow connector
 * A source of selected Dimensions exported as a flow of StdClasses of the form {value, attributes, label}
 * See cfgAttribut FuncExp for more details about the format.
 * Created by CWE on 29.06.2017
 */
class DimensionDataFlowConnector implements DataFlowDumpable {

	// Object lifecycle

	public function reset() {
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->sortOrder=3;
	}
	public function freeMemory() {
		unset($this->selector);
		unset($this->attrLogExp);
		unset($this->firstMatch);
		$this->lockedForUse = false;
	}

	public function isLockedForUse() {
		return $this->lockedForUse;
	}

	public static function createInstance($selector, $attrLogExp = null, $sortOrder = 3) {
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->setSelector($selector);
		if(isset($attrLogExp)) $returnValue->setAttrLogExp($attrLogExp);
		if(isset($sortOrder)) $returnValue->setSortOrder($sortOrder);
		return $returnValue;
	}

	// Dependency injection

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("DimensionDataFlowConnector");
		}
		return $this->_debugLogger;
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
	
	// Configuration

	private $selector;
	/**
	 * Sets the dimension selector. Can be a group id, a group name or a group log exp.
	 * @param String|Int|LogExp $selector
	 * @return DimensionDataFlowConnector for chaining
	 */
	public function setSelector($selector) {
		$this->selector = $selector;
		return $this;
	}

	private $attrLogExp;
	/**
	 * Sets a LogExp used to filter the list of attributes (for instance filtering some specific values, see module Dimensions for details about the available fields)
	 * @param LogExp $attrLogExp
	 * @return DimensionDataFlowConnector for chaining 
	 */
	public function setAttrLogExp($attrLogExp) {
	    $this->attrLogExp = $attrLogExp;
	    return $this;
	}

	private $sortOrder;
	/**
	 * Sets the sort order
	 * @param int $sortOrder One of 0 = no sorting, keep dimension element id ordering, 1 = ascending by value, 2 = descending by value, 3 = ascending by label, 4 = descending by label. (by default is ascending by label)
	 * @return DimensionDataFlowConnector for chaining
	 */
	public function setSortOrder($sortOrder) {
	    $this->sortOrder = $sortOrder;
	    return $this;
	}

	private $firstMatch;
	/**
	 * @param Boolean $bool if set to true, then returns only first match, else dumps whole selection. Not active by default.
	 * @return DimensionDataFlowConnector for chaining
	 */
	public function setFirstMatch($bool) {
	    $this->firstMatch = $bool;
	    return $this;
	}
	
	private $wigiiNamespace;
	/**
	 * Sets the WigiiNamespace into which to lookup for Dimensions module
	 * @param WigiiNamespace|String $wigiiNamespace a valid and accessible WigiiNamespace
	 * @return DimensionDataFlowConnector for chaining
	 */
	public function setWigiiNamespace($wigiiNamespace) {
		$this->wigiiNamespace = $wigiiNamespace;
		return $this;
	}
	
	// DataFlowDumpable implementation

	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext) {
	    if(empty($this->selector)) throw new DataFlowServiceException('The DimensionDataFlowConnector needs at least the dimension selector. Can be a group id, a group name or a group log exp.', DataFlowServiceException::INVALID_ARGUMENT);
	    if($this->sortOrder == null) $this->sortOrder = 3;
	    if($this->sortOrder < 0 || $this->sortOrder > 3) throw new DataFlowServiceException("sortOrder should be one of 0 = no sorting, keep dimension element id ordering, 1 = ascending by value, 2 = descending by value, 3 = ascending by label, 4 = descending by label.", DataFlowServiceException::INVALID_ARGUMENT);
	    $fxb = TechnicalServiceProvider::getFuncExpBuilder();
	    
	    // gets principal
	    $p = $dataFlowContext->getPrincipal();
	    
	    // gets setup wigii namespace and binds to it
	    if($this->wigiiNamespace) {
	    	if($this->wigiiNamespace instanceof WigiiNamespace) $setupNS = $this->wigiiNamespace;
	    	else $setupNS = $this->getWigiiNamespaceAdminService()->getWigiiNamespace($p, $this->wigiiNamespace);
	    }
	    else $setupNS =  $this->getWigiiNamespaceAdminService()->getSetupWigiiNamespace($p);
	    $origNS = $p->getWigiiNamespace();
	    $p->bindToWigiiNamespace($setupNS);
	    $cacheKey = $p->getUsername();
	    
	    // builds dimension selector
	    if(!($this->selector instanceof LogExp)) {
	        if(is_numeric($this->selector)) $selector = lxEq(fs('id'), $this->selector);
	        else $selector = lxEq(fs('groupname'), $this->selector);
	    }
	    else $selector = $this->selector;	    	    
	    $selector = lxAnd(lxEq(fs('module'), Module::DIMENSIONS_MODULE), lxEq(fs('wigiiNamespace'), $setupNS->getWigiiNamespaceName()), $selector);
	    $cacheKey .= ', '.$fxb->object2strfx($this->selector);
	    
	    // builds fskl
	    switch($this->sortOrder) {
	        case 0:
	            $fskl = fskl(fsk('__element', 'id', true));
	            $sortOrder = 0;
	            break;
	        case 1:
	            $fskl = fskl(fsk('value', null, true));
	            $sortOrder = 0;
	            break;
	        case 2:
	            $fskl = fskl(fsk('value', null, false));
	            $sortOrder = 0;
	            break;
	        case 3:
	            $fskl = null;
	            $sortOrder = 1;
	            break;
	        case 4:
	            $fskl = null;
	            $sortOrder = 2;
	            break;
	    }
	    
	    // builds list filter
	    $fsl = fsl(fs('value', 'value'), fs('idGroup', 'value'), fs('color', 'value'), fs('email', 'value'), fs('checked', 'value'), fs('label', 'value'));
	    if(isset($this->attrLogExp) || isset($fskl)) $lf = lf($fsl, $this->attrLogExp, $fskl);
	    else $lf = lf($fsl);
	    if($this->firstMatch) {$lf->setDesiredPageNumber(1); $lf->setPageSize(1);}
	    
	    $cacheKey .= ', '.$fxb->object2strfx($this->attrLogExp).', '.$fxb->object2strfx($this->sortOrder);
	    
	    $this->debugLogger()->write("dimension2df cache key = $cacheKey");
	    try {
	        $dataFlowService->processDataSource($p,
	            elementPList(lxInGR($selector), $lf),
	            dfasl(
	                dfas("MapElement2ValueDFA", "setElement2ValueFuncExp", fx('cfgAttribut',
	                    fs('value'),
	                    fx('newMap',
	                        'idGroup', fs('idGroup'),
	                        'color', fs('color'),
	                        'email', fs('email'),
	                        'checked', fx('ctlIf', fs('checked'), '1', '0'),
	                        'disabled', fx('ctlIf', fs_e('state_deprecated'), '1', '0')
	                        ),
	                    fs('label'))),
	                dfas("FilterDuplicatesAndSortDFA",
	                    "setObjectClass", 'cfgAttribut',
	                    "setSortOrder", $sortOrder
	                    ),
	                dfas('BridgeDFA','setDataFlowContext',$dataFlowContext,'setBridgeToBeginning',true) /* dumps dimensions in connected data flow */
	                ), true, null, 'dimension2df('.md5($cacheKey).')');
	    }
	    catch(Exception $e) {
	        $message="Problem when retrieving dimension ".$fxb->object2strfx($this->selector).".\nCheck that dimension exist in ". $setupNS->getWigiiNamespaceName()." namespace.\n[technical error is: ".$e->getCode().' '.$e->getMessage()."]";
	        throw new DataFlowServiceException($message,DataFlowServiceException::CONFIGURATION_ERROR);
	    }
	    $p->bindToWigiiNamespace($origNS);
	}

	/**
	 * Converts this Dimension data flow connector to its FuncExp equivalent
	 * @return FuncExp
	 */
	public function toFx() {
	    $fxb = TechnicalServiceProvider::getFuncExpBuilder();
		return fx('dimension2df', array(
		      $fxb->object2fx($this->selector),
		      $fxb->object2fx($this->attrLogExp),
		      $this->sortOrder		   
		));
	}
}