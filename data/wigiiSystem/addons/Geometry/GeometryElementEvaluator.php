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
 * Geometry addon ElementEvaluator
 * This class inherits from ReportingElementEvaluator defined in Reporting addon.
 * Created by CWE 20 février 2014
 */
class GeometryElementEvaluator extends ReportingElementEvaluator
{	
	private $_debugLogger;
	private $_executionSink;
	private $transS;
	private $innerFieldsConfig;	
	private $rewriteTags;
	
	// object lifecycle

	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	public function reset() {
		parent::reset();
		// sets default reporting facade to GeometryReportingFacade
		$this->setDefaultReportingFacadeClassName('GeometryReportingFacade');
		if(!isset($this->rewriteTags)) $this->rewriteTags = array('red1', 'redn', 'red1G', 'rednG', 'red1E', 'rednE', 'lxInput');
	}
	public function freeMemory()
	{		
		unset($this->internalState);
		unset($this->nextStateExp);
		unset($this->innerFieldsConfig);		
		parent::freeMemory();
	}	
	
	// dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("GeometryElementEvaluator");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("GeometryElementEvaluator");
		}
		return $this->_executionSink;
	}
	
	protected function getTranslationService() {
		// autowired
		if(!isset($this->transS)) {
			$this->transS = ServiceProvider::getTranslationService();
		}
		return $this->transS;
	}
	public function setTranslationService($transS) {
		$this->transS = $transS;
	}	
	
	// Configuration				
	
		
	
	// Geometry interactive FuncExp API

	const COMPUTATION_STATE_NEW = 0;
	const COMPUTATION_STATE_PREPARE = 1;
	const COMPUTATION_STATE_READY = 2;
	
	
	/**
	 * Executes a computation based on the given input.
	 * Precondition: the current Field datatype should be Blobs and is used to save the internal computation state.<br/>
	 * FuncExp signature : <code>executeComputation(input, statusBarField, innerFieldsConfig, innerFieldsData)</code><br/>
	 * Where arguments are :
	 * - Arg(0) input: evaluates to a String or a FuncExp instance which represents the functional expression to be executed
	 * - Arg(1) statusBarField: evaluates to a FieldSelector instance which points to a Field present in the record of type Strings where to store a status message.	
	 * - Arg(2) innerFieldsConfig: evaluates to a FieldSelector instance which points to a Field present in the record of type Blobs containing the xml configuration of the inner fields
	 * Postcondition: the computation is completed or paused into an internal status waiting for input from the user.
	 * In that latter case, the status message stored in the status bar field explains what to do next and the user must click on modify to display a form to enter some input.	 
	 */
	public function executeComputation($args) {
		$principal = $this->getPrincipal();		
		$statusBar=''; $returnValue = null;
		$this->executionSink()->publishStartOperation("executeComputation", $principal);
		try
		{									
			// checks status bar field
			$nArgs = $this->getNumberOfArgs($args);
			if($nArgs < 2 || is_null($args[1])) throw new GeometryException('status bar field is not defined. FuncExp second argument should point to a Field present in the record and of type Strings', GeometryException::INVALID_ARGUMENT);
			if($args[1] instanceof FieldSelector) $statusBarFieldSelector = $args[1];
			else {
				$statusBarFieldSelector = $this->evaluateArg($args[1]);
				if(is_null($statusBarFieldSelector) || !($statusBarFieldSelector instanceof FieldSelector)) throw new GeometryException('status bar field is not defined or invalid. FuncExp second argument should point to a Field present in the record and of type Strings', GeometryException::INVALID_ARGUMENT); 
			}					
			if(!$this->getElement()->getFieldList()->doesFieldExist($statusBarFieldSelector->getFieldName())) throw new GeometryException('status bar field is not defined. FuncExp second argument should point to a Field present in the record and of type Strings', GeometryException::INVALID_ARGUMENT);			
												
			$ts = $this->getTranslationService();
			$funcExpP = TechnicalServiceProvider::getFieldSelectorFuncExpParser();
			
			// read flow
			if($this->isCalculatingOnFetch()) {
				// $this->debugLogger()->write('readflow');
				$statusBar = $this->getFieldValue($statusBarFieldSelector);
				if(empty($statusBar)) $statusBar = $ts->t($principal, 'computationStatusModifyInputExp');
				
				//$this->debugLogger()->write('current state = '.$this->getCurrentFieldValue());
				$this->setIgnoreReturnValue(true);
			}
			// write flow
			else {	
				$this->debugLogger()->write('writeflow');			
				// loads internal state
				$this->loadInternalStateFromString($this->getCurrentFieldValue());
				$s = $this->internalState['computationState'];
				if(is_null($s)) $s = self::COMPUTATION_STATE_NEW;				
				
				// if combo action has been set to restart then state is always new.
				if($this->getElement()->hasInnerField('action') && $this->getElement()->getFieldValue('action') == 'restart') {
					$s = self::COMPUTATION_STATE_NEW;
					// clears all inner fields
					$elt = $this->getElement();
					$innerFieldList = $elt->getInnerFieldListContainingField('action');
					foreach($innerFieldList->getListIterator() as $f) {
						$elt->setFieldValue(null, $f->getFieldName());
					}
					// sets state to continue
					$this->setFieldValue(fs('action'), 'continue');
				}
				
				// if state is NEW
				// - takes input and tags it
				// - if tags then next state is PREPARE else next state is READY
				// if state is PREPARE
				// - replaces tags in simplified exp with inner fields values and puts input in readonly
				// - continues tagging of tagged exp
				// - if no new tags, then next state is READY
				// if state is READY does nothing.
				//$this->debugLogger()->write("state $s");
				
				if($s == self::COMPUTATION_STATE_NEW) {
					// gets input
					$inputExp = $this->evaluateArg($args[0]);
					if(empty($inputExp)) $inputExp = null;
					elseif($inputExp instanceof FuncExp) {
						// clones it
						$inputExpS = $funcExpP->funcExpToString($inputExp);
						$inputExp = $funcExpP->createFuncExpFromString($inputExpS);
					}
					elseif(is_string($inputExp)) {
						$inputExpS = stripslashes($inputExp);
						$inputExp = $funcExpP->createFuncExpFromString($inputExpS);
					}
					else throw new GeometryException('input expression should be a FuncExp instance or a String representing a FuncExp', GeometryException::INVALID_ARGUMENT);
					// tags input exp
					if(isset($inputExp)) {
						$this->innerFieldsConfig = $this->createInnerFieldsConfig();
						$lastTagIndex = $this->tagFuncExpAndGenerateInnerFieldsConfig($inputExp, $this->innerFieldsConfig);
						// if something has been tagged then persists all these informations and switches to PREPARE state
						if($lastTagIndex > 0) {
							// creates simplified exp based on clone of input exp
							$simplifiedExp = $funcExpP->createFuncExpFromString($inputExpS);
							// tags simplified exp
							if($this->tagFuncExpAndGenerateInnerFieldsConfig($simplifiedExp, null) != $lastTagIndex) throw new GeometryException("tags mismatch between tagged and simplified expressions", GeometryException::INVALID_STATE);
							// persist tagged expression in inner field config
							$this->innerFieldsConfig->taggedExp['funcExp'] = $funcExpP->funcExpToString(fx('ctlIf', fx('isCalculatingOnFetch'), fx('fx2str', $inputExp), fx('ctlIgnoreReturnValue')));
							$this->innerFieldsConfig->taggedExp['calculateOnFetch'] = '1';
							// persist simplified expression in inner field config
							$this->innerFieldsConfig->simplifiedExp['funcExp'] = $funcExpP->funcExpToString(fx('ctlIf', fx('isCalculatingOnFetch'), fx('fx2str', $simplifiedExp), fx('ctlIgnoreReturnValue')));
							$this->innerFieldsConfig->simplifiedExp['calculateOnFetch'] = '1';
							// persist inner field config
							if($nArgs < 3 || is_null($args[2])) throw new GeometryException('inner fields configuration field is not defined. FuncExp third argument should point to a Field present in the record and of type Blobs', GeometryException::INVALID_ARGUMENT);
							if($args[2] instanceof FieldSelector) $innerFieldsConfigSelector = $args[2];
							else {
								$innerFieldsConfigSelector = $this->evaluateArg($args[2]);
								if(is_null($innerFieldsConfigSelector) || !($innerFieldsConfigSelector instanceof FieldSelector)) throw new GeometryException('inner fields configuration field is not defined or invalid. FuncExp third argument should point to a Field present in the record and of type Blobs', GeometryException::INVALID_ARGUMENT);
							}
							$this->saveInnerFieldsConfig($innerFieldsConfigSelector);
							// saves lastTagIndex into state
							$this->nextStateExp['lastTagIndex'] = $lastTagIndex;
							// switch to PREPARE state
							$s = self::COMPUTATION_STATE_PREPARE;
							// informs status bar
							$statusBar = $ts->t($principal, 'computationStatusSelectTaggedValues');							
						}
						// else do not persist anything and switch to READY state
						else $s = self::COMPUTATION_STATE_READY;											
					}
					else $statusBar = $ts->t($principal, 'computationStatusNotDefined');
					$this->nextStateExp['computationState'] = $s;
					$returnValue = $this->serializeInternalState();
				}
				elseif($s == self::COMPUTATION_STATE_PREPARE) {
					// reads inner fields config
					if($nArgs < 3 || is_null($args[2])) throw new GeometryException('inner fields configuration field is not defined. FuncExp third argument should point to a Field present in the record and of type Blobs', GeometryException::INVALID_ARGUMENT);
					if($args[2] instanceof FieldSelector) $innerFieldsConfigSelector = $args[2];
					else {
						$innerFieldsConfigSelector = $this->evaluateArg($args[2]);
						if(is_null($innerFieldsConfigSelector) || !($innerFieldsConfigSelector instanceof FieldSelector)) throw new GeometryException('inner fields configuration field is not defined or invalid. FuncExp third argument should point to a Field present in the record and of type Blobs', GeometryException::INVALID_ARGUMENT);
					}
					$this->loadInnerFieldsConfig($innerFieldsConfigSelector);
					if(!isset($this->innerFieldsConfig)) throw new GeometryException('inner fields config has not been persisted', GeometryException::INVALID_STATE);
											
					// reads simplified exp
					$simplifiedExp = $this->getFieldValue(fs('simplifiedExp'));
					if(empty($simplifiedExp)) throw new GeometryException('simplifiedExp has not been persisted', GeometryException::INVALID_STATE);
					$simplifiedExp = $funcExpP->createFuncExpFromString(stripslashes($simplifiedExp));				
					
					// replace tags in simplified exp with selected values from inner fields
					$this->replaceTaggedFuncExpWithInnerFieldValue($simplifiedExp, $this->innerFieldsConfig);
					//$this->debugLogger()->write('tag replacement='.$funcExpP->funcExpToString($simplifiedExp));
					
					// reads tagged exp
					$taggedExp = $this->getFieldValue(fs('taggedExp'));
					if(empty($taggedExp)) throw new GeometryException('taggedExp has not been persisted', GeometryException::INVALID_STATE);
					$taggedExp = $funcExpP->createFuncExpFromString(stripslashes($taggedExp));
					
					// unsets func exp of tagged expression in inner field config
					$this->innerFieldsConfig->taggedExp['funcExp'] = null;
					$this->innerFieldsConfig->taggedExp['calculateOnFetch'] = null;
					// unsets func exp of simplified expression in inner field config
					$this->innerFieldsConfig->simplifiedExp['funcExp'] = null;
					$this->innerFieldsConfig->simplifiedExp['calculateOnFetch'] = null;
					
					// continues tagging
					$lastTagIndex = $this->internalState['lastTagIndex'];
					if($lastTagIndex < 1) $lastTagIndex = 0;
					$startTagIndex = $lastTagIndex + 1;
					$lastTagIndex = $this->tagFuncExpAndGenerateInnerFieldsConfig($taggedExp, null, $startTagIndex);
										
					// if something has been tagged then also tags simplified exp and saves last tag index
					if($lastTagIndex > 0) {						
						// tags simplified exp
						if($this->tagFuncExpAndGenerateInnerFieldsConfig($simplifiedExp, $this->innerFieldsConfig, $startTagIndex) != $lastTagIndex) throw new GeometryException("tags mismatch between tagged and simplified expressions", GeometryException::INVALID_STATE);						
						// saves lastTagIndex into state
						$this->nextStateExp['lastTagIndex'] = $lastTagIndex;						
						// informs status bar
						$statusBar = $ts->t($principal, 'computationStatusSelectTaggedValues');						
					}
					// else switches to READY state
					else {	
						$s = self::COMPUTATION_STATE_READY;					
						$statusBar = $ts->t($principal, 'computationStatusReady');
					}
					
					// persists tagged expressions
					$this->setFieldValue(fs('taggedExp'), $funcExpP->funcExpToString($taggedExp));
					$this->setFieldValue(fs('simplifiedExp'), $funcExpP->funcExpToString($simplifiedExp));
					
					// persist inner field config
					$this->saveInnerFieldsConfig($innerFieldsConfigSelector);
					// persists internal state
					$this->nextStateExp['computationState'] = $s;
					$returnValue = $this->serializeInternalState();
				}
				elseif($s == self::COMPUTATION_STATE_READY) {
					$this->setIgnoreReturnValue(true);
				}
				else throw new GeometryException("invalid internal state '$s'", GeometryException::INVALID_STATE);				
			}
			$this->setFieldValue($statusBarFieldSelector, $statusBar);			
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("executeComputation", $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation("executeComputation", $principal);
		// $this->debugLogger()->write('internalState serialization: '.$returnValue);
		return $returnValue;		
	}
	
	/**
	 * Builds a report based on the result of the computation.<br/>
	 * FuncExp signature : <code>buildComputationResultReport(reportName, statusBarField, internalStateField)</code><br/>
	 * Where arguments are :
	 * - Arg(0) reportName: evaluates to a String fixing the name of the report
	 * - Arg(1) statusBarField: evaluates to a FieldSelector instance which points to a Field present in the record of type Strings where to store a status message.
	 * - Arg(2) internalStateField: evaluates to a FieldSelector instance which points to a Field present in the record of type Blobs and
	 * used as the internal state storage by the 'executeComputation' funcExp (the field on which the executeComputation is fixed).
	 */
	public function buildComputationResultReport($args) {
		// if write flow
		if(!$this->isCalculatingOnFetch()) {
			$nArgs = $this->getNumberOfArgs($args);
			if($nArgs < 3) throw new GeometryException('funcExp buildComputationResultReport takes three non null arguments: reportName, statusBarField and internalStateField', GeometryException::INVALID_ARGUMENT);			
			// checks internal state field
			if(is_null($args[2])) throw new GeometryException('internal state field is not defined. FuncExp third argument should point to a Field present in the record and of type Blobs', GeometryException::INVALID_ARGUMENT);
			if($args[2] instanceof FieldSelector) $internalStateFieldSelector = $args[2];
			else {
				$internalStateFieldSelector = $this->evaluateArg($args[2]);
				if(is_null($internalStateFieldSelector) || !($internalStateFieldSelector instanceof FieldSelector)) throw new GeometryException('internal state field is not defined or invalid. FuncExp third argument should point to a Field present in the record and of type Blobs', GeometryException::INVALID_ARGUMENT);
			}
			// loads state
			$this->loadInternalStateFromField($internalStateFieldSelector);
			
			// if state is ready then builds the report
			if($this->internalState['computationState'] == self::COMPUTATION_STATE_READY) {
				// checks status bar field
				if(is_null($args[1])) throw new GeometryException('status bar field is not defined. FuncExp second argument should point to a Field present in the record and of type Strings', GeometryException::INVALID_ARGUMENT);
				if($args[1] instanceof FieldSelector) $statusBarFieldSelector = $args[1];
				else {
					$statusBarFieldSelector = $this->evaluateArg($args[1]);
					if(is_null($statusBarFieldSelector) || !($statusBarFieldSelector instanceof FieldSelector)) throw new GeometryException('status bar field is not defined or invalid. FuncExp second argument should point to a Field present in the record and of type Strings', GeometryException::INVALID_ARGUMENT);
				}
				if(!$this->getElement()->getFieldList()->doesFieldExist($statusBarFieldSelector->getFieldName())) throw new GeometryException('status bar field is not defined. FuncExp second argument should point to a Field present in the record and of type Strings', GeometryException::INVALID_ARGUMENT);
				
				// executes the report
				try {
					$this->evaluateArg(fx('executeReport', $args[0], 'TXT'));
					$statusBar = $this->getTranslationService()->t($this->getPrincipal(), 'computationStatusDone');
				}	
				catch(Exception $e) {
					$statusBar = $this->getTranslationService()->t($this->getPrincipal(), 'computationStatusFailed');
					throw $e;
				}	
				$this->setFieldValue($statusBarFieldSelector, $statusBar);
			}
		}
		$this->setIgnoreReturnValue(true);
	}
			
	// Implementation
	
	/**
	 * @param FuncExp $funcExp the func exp to be tagged
	 * @param SimpleXMLElement $innerFieldsConfig the XML node containing the inner fields configuration to be updated,
	 * should be an existing node of the form <fields>...</fields>. 
	 * If null, then no configuration is generated.
	 * @param int $tagStartIndex the index from which to start tag numbering
	 * @return int the last generated tag index or 0 if no generated tag.
	 * Postcondition: the funcexp is modified and tag nodes have been inserted.
	 */
	private function tagFuncExpAndGenerateInnerFieldsConfig($funcExp, $innerFieldsConfig, $tagStartIndex=1) {
		if(is_null($funcExp)) return 0;
		if(!($funcExp instanceof FuncExp)) return 0;
		$fName = $funcExp->getName();
		if($fName == 'tag') return 0;		
		// walks recursively down the func exp		
		$args = $funcExp->getArguments();
		$lastTagIndex = 0;
		if(isset($args) && !empty($args)) {		
			$taggedArg = false;		
			for($i = 0; $i < count($args); $i++) {
				$o = $this->tagFuncExpAndGenerateInnerFieldsConfig($args[$i], $innerFieldsConfig, $tagStartIndex);
				// if argument has beed tagged then replaces it with the tagged value
				if($o instanceof FuncExp) {
					$args[$i] = $o; $taggedArg = true;
					$lastTagIndex = $tagStartIndex;
					$tagStartIndex++;
				}
				// else if tags have been inserted deeper in the tree, then updates the tagStartIndex and lastTagIndex.
				elseif($o > 0) {
					$tagStartIndex = $o + 1;
					$lastTagIndex = $o; 
				}
			}
			// replaces the func exp arguments if they have changed.
			if($taggedArg) $funcExp->setArguments($args);
		}
		// if current function is a rewrite tag and not tagged deeper, then prepares config for user input and returns tagged exp.
		if($lastTagIndex == 0 && in_array($fName, $this->rewriteTags)) {
			$label = '';
			if(isset($innerFieldsConfig)) {
				$label = $this->prepareConfigForRewriteTag($funcExp, $innerFieldsConfig, $tagStartIndex);				
			}
			if(empty($label)) $label = '__'.$tagStartIndex.'__'; 
			return fx('tag', $label, $funcExp, $tagStartIndex);
		}
		return $lastTagIndex;		
	}
	/**
	 * Prepares the config to get some information from the user
	 * @param FuncExp $rewriteExp the rewrite tag which has been detected
	 * @param SimpleXMLElement $innerFieldsConfig the inner fields configuration to update
	 * @param int $tagIndex the current tag index that can be used as a key to identify the configuration fields associated to this tag.
	 * @return String an optional label to display in the tag
	 */
	protected function prepareConfigForRewriteTag($rewriteExp, $innerFieldsConfig, $tagIndex) {	
		$fxParser = TechnicalServiceProvider::getFieldSelectorFuncExpParser();
		$fName = $rewriteExp->getName();
		$args = $rewriteExp->getArguments();
		if(!empty($args)) $nArgs = count($args); else $nArgs = 0;
		$tagLabel = '__'.$tagIndex.'__';
		$fieldName = 'tag'.$tagLabel;
		$fieldLabel = 'Tag '.$tagLabel;
		$genFields = null;
		
		// lxInput
		if($fName == 'lxInput') {			
			$genFields = sel($this->getPrincipal(), array2df(
					cfgField($fieldName,
						array('type' => 'Blobs', 'expand' => '1', 'height' => '100'),
						$fieldLabel
					)
				),
				dfasl(dfas('CfgField2XmlDFA')));
		}
		// red1
		elseif($fName == 'red1') {
			if($nArgs < 1) throw new FuncExpEvalException('funcExp red1 takes at least one argument which evaluates to a data flow selector', FuncExpEvalException::INVALID_ARGUMENT);
			$genFields = sel($this->getPrincipal(), array2df(
					cfgField($fieldName, 
						array('type' => 'Attributs', 'expand' => '1'),
						$fieldLabel,
						cfgAttributeExp($args[0])
					)
				), 
				dfasl(dfas('CfgField2XmlDFA')));
		}
		// redn
		elseif($fName == 'redn') {
			if($nArgs < 1) throw new FuncExpEvalException('funcExp redn takes at least one argument which evaluates to a data flow selector', FuncExpEvalException::INVALID_ARGUMENT);
			$genFields = sel($this->getPrincipal(), array2df(
					cfgField($fieldName,
							array('type' => 'MultipleAttributs', 'expand' => '1'),
							$fieldLabel,
							cfgAttributeExp($args[0])
					)
				),
				dfasl(dfas('CfgField2XmlDFA')));
		}
		// red1G
		elseif($fName == 'red1G') {
			if($nArgs < 1) throw new FuncExpEvalException('funcExp red1G takes at least one argument which is the module name or a group log exp', FuncExpEvalException::INVALID_ARGUMENT);
			// gets module name and wigii namespace name
			if(is_string($args[0])) {
				if($nArgs > 1) $wigiiNamespaceName = $args[1];
				else $wigiiNamespaceName = $this->getPrincipal()->getWigiiNamespace()->getWigiiNamespaceName();
				$groupLogExpFx = strlx2fx('module = "'.$args[0].'" && wigiiNamespace = "'.$wigiiNamespaceName.'" && id_group_parent = NULL');
			}
			// gets log exp
			else $groupLogExpFx = $args[0];
			
			$genFields = sel($this->getPrincipal(), array2df(
					cfgField($fieldName,
							array('type' => 'Attributs', 'expand' => '1'),
							$fieldLabel,
							cfgAttributeExp('dfs(groupList('.$fxParser->funcExpToString($groupLogExpFx).', logTrue()), dfasl(dfas("MapObject2ValueDFA", "setObject2ValueFuncExp", fx(cfgAttribut(id, NULL, groupname)))))')
					)
				),
				dfasl(dfas('CfgField2XmlDFA')));
		}
		// rednG
		elseif($fName == 'rednG') {
			if($nArgs < 1) throw new FuncExpEvalException('funcExp red1G takes at least one argument which is the module name or a group log exp', FuncExpEvalException::INVALID_ARGUMENT);
			// gets module name and wigii namespace name
			if(is_string($args[0])) {
				if($nArgs > 1) $wigiiNamespaceName = $args[1];
				else $wigiiNamespaceName = $this->getPrincipal()->getWigiiNamespace()->getWigiiNamespaceName();
				$groupLogExpFx = strlx2fx('module = "'.$args[0].'" && wigiiNamespace = "'.$wigiiNamespaceName.'" && id_group_parent = NULL');
			}
			// gets log exp
			else $groupLogExpFx = $args[0];
				
			$genFields = sel($this->getPrincipal(), array2df(
					cfgField($fieldName,
							array('type' => 'MultipleAttributs', 'expand' => '1'),
							$fieldLabel,
							cfgAttributeExp('dfs(groupList('.$fxParser->funcExpToString($groupLogExpFx).', logTrue()), dfasl(dfas("MapObject2ValueDFA", "setObject2ValueFuncExp", fx(cfgAttribut(id, NULL, groupname)))))')
					)
			),
					dfasl(dfas('CfgField2XmlDFA')));
		}
		// red1E
		elseif($fName == 'red1E') {
			if($nArgs < 1) throw new FuncExpEvalException('funcExp red1E takes at least one argument which evaluates to a data flow selector', FuncExpEvalException::INVALID_ARGUMENT);
			$genFields = sel($this->getPrincipal(), array2df(
					cfgField($fieldName,
							array('type' => 'Attributs', 'expand' => '1'),
							$fieldLabel,
							cfgAttributeExp($args[0])
					)
			),
					dfasl(dfas('CfgField2XmlDFA')));
		}
		// rednE
		elseif($fName == 'rednE') {
			if($nArgs < 1) throw new FuncExpEvalException('funcExp rednE takes at least one argument which evaluates to a data flow selector', FuncExpEvalException::INVALID_ARGUMENT);
			$genFields = sel($this->getPrincipal(), array2df(
					cfgField($fieldName,
							array('type' => 'MultipleAttributs', 'expand' => '1'),
							$fieldLabel,
							cfgAttributeExp($args[0])
					)
			),
					dfasl(dfas('CfgField2XmlDFA')));
		}
		// else
		else throw new FuncExpEvalException("rewrite tag '$fName' is not supported.", FuncExpEvalException::UNSUPPORTED_OPERATION);
		
		// adds generated fields to existing inner fields.
		if(isset($genFields)) {
			simplexml_appendChildren($innerFieldsConfig, $genFields);
		}
		return $label;
	}
	
	/**
	 * @param FuncExp $taggedExp
	 * @param SimpleXMLElement $innerFieldsConfig the XML node containing the inner fields configuration to be updated,
	 * should be an existing node of the form <fields>...</fields>.
	 * @return int the number of replacements
	 * Postcondition: taggedExp is modified and all the tag nodes have been deleted and replaced with an appropriate
	 * expression built using the inner fields values.
	 */
	private function replaceTaggedFuncExpWithInnerFieldValue($taggedExp, $innerFieldsConfig) {
		return $this->doRecReplaceTaggedFuncExpWithInnerFieldValue($taggedExp, $innerFieldsConfig)->replaceCount;
	}		
	/**
	 * @return stdClass a StdClass instance of the form {isTagValue:bool, tagValue:Any, replaceCount:int}
	 */
	private function doRecReplaceTaggedFuncExpWithInnerFieldValue($taggedExp, $innerFieldsConfig) {
		$returnValue = (object)array('isTagValue'=> false, 'tagValue' => null, 'replaceCount' => 0);
		if(is_null($taggedExp)) return $returnValue;
		if(!($taggedExp instanceof FuncExp)) return $returnValue;
		$fName = $taggedExp->getName();
		//$this->debugLogger()->write("passed through $fName");
		$args = $taggedExp->getArguments();
		// if tag then returns the replacement value found in the associated inner field
		if($fName == 'tag') {			
			$returnValue->tagValue = $this->getTagReplacementValue($args[2], $args[0], $args[1], $innerFieldsConfig);
			$returnValue->isTagValue = true;
			$returnValue->replaceCount = 1;		
			//$this->debugLogger()->write("Tag replacement prepare: ".json_encode($returnValue));			
		}		
		// walks recursively down the func exp		
		elseif(isset($args) && !empty($args)) {		
			$taggedArg = false;		
			for($i = 0; $i < count($args); $i++) {
				$o = $this->doRecReplaceTaggedFuncExpWithInnerFieldValue($args[$i], $innerFieldsConfig);
				// if argument has beed tagged then replaces it with the tagged value
				if($o->isTagValue) {
					$args[$i] = $o->tagValue; $taggedArg = true;
				 	//$this->debugLogger()->write("Tag replacement done: ".json_encode($o));
				}				
				$returnValue->replaceCount += $o->replaceCount;
			}
			// replaces the func exp arguments if they have changed.
			if($taggedArg) $taggedExp->setArguments($args);
		}
		return $returnValue;
	}
	
	/**
	 * Returns the replacement value for the given tag.
	 * @param int $tagIndex the tag index
	 * @param String $label the user friendly label given to this tag
	 * @param FuncExp $rewriteExp the original func exp which generated the tag.
	 * @param SimpleXMLElement $innerFieldsConfig the inner fields configuration to update if needed (sets readonly, unset func exp, etc)
	 * @return Any the replacement value for this tag.
	 */
	protected function getTagReplacementValue($tagIndex, $label, $rewriteExp, $innerFieldsConfig) {
		$fName = $rewriteExp->getName();
		$fieldName = 'tag__'.$tagIndex.'__';
		$returnValue = null;
		
		switch ($fName) {
			case 'lxInput':
				$returnValue = strlx2fx(stripslashes($this->getFieldValue(fs($fieldName))));
				//$this->debugLogger()->write('lxInput='.TechnicalServiceProvider::getFieldSelectorFuncExpParser()->funcExpToString($returnValue));
				break;
			default: $returnValue = $this->getFieldValue(fs($fieldName));
		}
			
		// updates config, puts fields in readonly and desactivates any func exp
		$innerFieldsConfig->{$fieldName}['readonly'] = '1';
		$innerFieldsConfig->{$fieldName}['funcExp'] = null;
		$innerFieldsConfig->{$fieldName}['calculateOnFetch'] = null;
		
		return $returnValue;
	}
	
	private $internalState; // current state as an array of objects (indexed by keys)
	private $nextStateExp; // next state as an array of func exp
	/**
	 * @param FieldSelector $internalStateFieldSelector
	 * Postcondition: internalState and nextStateExp instance variables are arrays
	 */
	private function loadInternalStateFromField($internalStateFieldSelector) {				
		if(!isset($this->internalState)) {
			// checks existence of internal state field
			if(!isset($internalStateFieldSelector)) throw new GeometryException('internalStateFieldSelector cannot be null', GeometryException::INVALID_ARGUMENT);
			if(!$this->getElement()->getFieldList()->doesFieldExist($internalStateFieldSelector->getFieldName())) throw new GeometryException("Field '".$internalStateFieldSelector->getFieldName()."' is not defined in the record", GeometryException::CONFIGURATION_ERROR);
			// loads internal state func exp
			$this->loadInternalStateFromString($this->getFieldValue($internalStateFieldSelector));
		}		
	}
	/**
	 * @param String $internalState
	 * Postcondition: internalState and nextStateExp instance variables are arrays
	 */
	private function loadInternalStateFromString($internalState) {
		// $this->debugLogger()->logBeginOperation('loadInternalStateFromString');
		if(!isset($this->internalState)) {
			// $this->debugLogger()->write('loads internal state '.$internalState);
			if(empty($internalState)) $internalState = null;
			else $internalState = TechnicalServiceProvider::getFieldSelectorFuncExpParser()->createFuncExpFromString(stripslashes($internalState));
			// executes internal state func exp to build the objects
			if(isset($internalState)) $this->internalState = $this->evaluateArg($internalState);
			else $this->internalState = array();
			$this->nextStateExp = array();
		}
		// $this->debugLogger()->logEndOperation('loadInternalStateFromString');
	}	
	private function serializeInternalState() {		
		if(!empty($this->nextStateExp)) {			
			$returnValue = TechnicalServiceProvider::getFieldSelectorFuncExpParser()->funcExpToString(fx('newMap', 
					fx('newList', array_keys($this->nextStateExp)), 
					fx('newList', array_values($this->nextStateExp)))
			);
			unset($this->internalState);
		}
		else $returnValue = '';
		return $returnValue;
	}
	
	/**
	 * Creates the base configuration for the inner fields and returns a SimpleXmlElement
	 * @return SimpleXMLElement
	 */
	protected function createInnerFieldsConfig() {
		return simplexml_load_string(<<<IFC
<?xml version='1.0' standalone='yes'?>
<fields>
	<action type="Attributs" expand="1">
		<label_l01>Action</label_l01><label_l02>Action</label_l02>
		<attribute checked="1">continue<label_l01>Continue</label_l01><label_l02>Continuer</label_l02></attribute>
		<attribute>restart<label_l01>Change input and restart</label_l01><label_l02>Changer l&apos;expression d&apos;entrée et recommencer</label_l02></attribute>
	</action>
	<taggedExp type="Blobs"	require="0" readonly="1" searchable="1" expand="1" excelExport="auto" height="200" reportParam="0"><label_l01>Tagged expression</label_l01><label_l02>Expression taggée</label_l02></taggedExp>
	<simplifiedExp type="Blobs"	require="0" readonly="1" searchable="1" expand="1" excelExport="auto" height="200" reportParam="1"><label_l01>Simplified expression</label_l01><label_l02>Expression simplifiée</label_l02></simplifiedExp>
</fields>
IFC
		);
	}
	
	/**
	 * @param FieldSelector $innerFieldsConfigSelector
	 */
	private function loadInnerFieldsConfig($innerFieldsConfigSelector) {
		$xml = $this->getFieldValue($innerFieldsConfigSelector);
		if(!empty($xml)) $this->innerFieldsConfig = simplexml_load_string(stripslashes($xml));
		else unset($this->innerFieldsConfig);
	}
	/**
	 * @param FieldSelector $innerFieldsConfigSelector
	 */
	private function saveInnerFieldsConfig($innerFieldsConfigSelector) {
		if(isset($this->innerFieldsConfig)) $xml = $this->innerFieldsConfig->asXML();
		else $xml = null;
		$this->setFieldValue($innerFieldsConfigSelector, $xml);
		// $this->debugLogger()->write('saves inner field config:'.$xml);
	}
}