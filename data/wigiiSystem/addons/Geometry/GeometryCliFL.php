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
 * Geometry Namespace CLI Func Exp library 
 * Created by CWE on 26 january 2013
 */
class GeometryCliFL extends FuncExpVMAbstractFL
{	
	// Dependency injection
	
	private $dflowS;
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
	
	// Configuration
	
	private $sequenceRepositoryId;
	/**
	 * Sets the group id in which to store generated sequences
	 * @param int $groupId should be an existing and accessible group id.
	 */
	public function setSequenceRepositoryId($groupId) {
		$this->sequenceRepositoryId = $groupId;
	}
	/**
	 * Returns the group id in which are stored the sequences
	 * @return int the group id or null if not configured.
	 */
	public function getSequenceRepositoryId() {
		return $this->sequenceRepositoryId;
	}
	
	// Geometry CLI
	
	/**
	 * Returns a DataFlowSelector that generates a sequence<br/>
	 * FuncExp signature : <code>sequence_gen(title, source, dfasl)</code><br/>
	 * Where arguments are :
	 * - Arg(0) title : evaluates to a string which will be the title of the sequence
	 * - Arg(1) source : funcExp which should return a DataFlowDumpable object which is used as a source of values.
	 * - Arg(2) dfasl : optional argument. If defined, funcExp which should return a DataFlowSelectorList which contains some dataflow activities which transforms the flow of values. 
	 * @return DataFlowSelector
	 */
	public function sequence_gen($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException("sequence_gen takes at least two arguments: the sequence title and the source of values", FuncExpEvalException::INVALID_ARGUMENT);
		$title = $this->evaluateArg($args[0]);
		$source = $args[1];
		if($nArgs > 2) $dfasl = $args[2];
		else $dfasl = null;
		$mandatoryDfasl = fx('dfasl', 
			fx('dfas', 'BoxingDFA'),
			fx('dfas', 'MapObject2ElementDFA',
				'setInsertOnlyMode', true,
				'setLinkSelector', fx('ls', fs_e('id'), 'values')),
			fx('dfas', 'ElementDFA', 'setMode', '1')
		);
		if(isset($dfasl)) $dfasl = fx('appendDfasl', $dfasl, $mandatoryDfasl);
		else $dfasl = $mandatoryDfasl;
		
		return dfs(newElement($this->getSequenceRepositoryId()), dfasl(
			dfas('ElementSetterDFA',
				'setCalculatedFieldSelectorMap', cfsMap(
					cfs('title', $title),
					cfs('values', fx('dfs', $source, $dfasl))
				)
			)
		));
	}
	
	/**
	 * Returns the sequence ID given its title
	 * (This function can also be called as a normal function, i.e. by passing directly the arguments instead of passing an array).
	 * @return Scalar returns the ID or null if not found
	 */
	public function sequenceIdFromTitle($args) {				
		// funcExp call
		if(is_array($args)) {
			$nArgs = $this->getNumberOfArgs($args);
			if($nArgs < 1) throw new FuncExpEvalException("funcExp takes one non null argument which is the sequence title", FuncExpEvalException::INVALID_ARGUMENT);
			$title = $this->evaluateArg($args[0]);
		}
		// normal call
		elseif(isset($args)) $title = $args;
		else throw new FuncExpEvalException("funcExp takes one non null argument which is the sequence title", FuncExpEvalException::INVALID_ARGUMENT);
		
		// fetches the id given the title
		return $this->getDataFlowService()->processDumpableObject($this->getPrincipal(),
				elementPList(lxInG(lxEq(fs('id'),$this->getSequenceRepositoryId())), lf(null,lxEq(fs('title'),$title),null,1,1)),
				dfasl(
					dfas('MapElement2ValueDFA', 'setElement2ValueFuncExp', fs_e('id'))
				), true, null, "sequenceIdFromTitle($title)");
	}
	
	/**
	 * Fetches an existing sequence given its ID or title and 
	 * returns a DataFlowDumpable object which can be used as a dataflow source.
	 * The dataflow will be a flow of values.<br/>	
	 * FuncExp signature : <code>sequenceAsValues(idOrTitle, limit=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) idOrTitle : evaluates to a string which will be the element id or the title of the sequence
	 * - Arg(1) limit : optional argument. If set, then evaluates to a positive integer indicating the maximum number of terms to be fetched.
	 * @return DataFlowDumpable
	 */
	public function sequenceAsValues($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("funcExp sequence should have at least one argument: the element id or the sequence title", FuncExpEvalException::INVALID_ARGUMENT);
		$returnValue = ServiceProvider::getExclusiveAccessObject('SequenceDFC');
		$id = $this->evaluateArg($args[0]);
		// if title then fetches id
		if(!is_numeric($id)) $id = $this->sequenceIdFromTitle($id);	
		$returnValue->setId($id);	
		if($nArgs > 1) $returnValue->setLimit($this->evaluateArg($args[1]));
		$returnValue->setSequenceRepositoryId($this->getSequenceRepositoryId());
		return $returnValue;
	}
	
	/**
	 * Fetches an existing sequence given its ID or title and 
	 * returns a DataFlowDumpable object which can be used as a dataflow source.
	 * The dataflow will be a flow of sub-elements (one element per sequence value).<br/>	
	 * FuncExp signature : <code>sequenceAsElements(idOrTitle, limit=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) idOrTitle : evaluates to a string which will be the element id or the title of the sequence
	 * - Arg(1) limit : optional argument. If set, then evaluates to a positive integer indicating the maximum number of terms to be fetched.
	 * @return DataFlowDumpable
	 */
	public function sequenceAsElements($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("funcExp sequence should have at least one argument: the element id or the sequence title", FuncExpEvalException::INVALID_ARGUMENT);		
		$idOrTitle = $this->evaluateArg($args[0]);
		if(!isset($idOrTitle)) throw new FuncExpEvalException("funcExp sequence should have at least one argument: the element id or the sequence title", FuncExpEvalException::INVALID_ARGUMENT);				
		if($nArgs > 1) $limit = $this->evaluateArg($args[1]);
		else $limit = null;
				
		$principal = $this->getPrincipal();
		
		// fetches the sequence id
		if(is_numeric($idOrTitle)) $eltId = $idOrTitle;
		else $eltId = $this->sequenceIdFromTitle($idOrTitle);	

		// sets limit
		if($limit > 0) {
			$pN = 1; $pS = $limit;
		}
		else {
			$pN = null; $pS = null;
		}
		// fetches the sub-elements of the sequence				
		return elementPList(ls($eltId, 'values', cs(lxEq(fs('id'), $this->getSequenceRepositoryId()))),
							lf(null, null, fskl(fsk('__element', 'id')), $pN, $pS));			
	}	
}

class SequenceAsValuesDFC implements DataFlowDumpable {
	private $lockedForUse = true;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();	
		$this->lockedForUse = true;		
	}	
	public function freeMemory() {		
		$this->lockedForUse = false;	
		unset($this->id);		
		unset($this->limit);
		unset($this->sequenceRepositoryId);
	}
		
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public static function createInstance($id) {
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->setId($id);		
		return $returnValue;
	}	
	
	// Configuration
	
	private $id;
	public function setId($id) {
		$this->id = $id;
	}	
	
	private $limit;
	public function setLimit($limit) {		
		$this->limit = $limit;
	}
	
	private $sequenceRepositoryId;
	public function setSequenceRepositoryId($id) {
		$this->sequenceRepositoryId = $id;		
	}
	
	// DataFlowDumpable implementation
	
	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext) {
		$principal = $dataFlowContext->getPrincipal();
	
		// sets limit
		if($this->limit > 0) {
			$pN = 1; $pS = $this->limit;
		}
		else {
			$pN = null; $pS = null;
		}
		// fetches the values of the sequence				
		$dataFlowService->processDumpableObject($principal,
			elementPList(ls($this->id, 'values', cs(lxEq(fs('id'), $this->sequenceRepositoryId))),
							lf(null, null, fskl(fsk('__element', 'id')), $pN, $pS)),
			dfasl(
				dfas('MapElement2ValueDFA', 'setElement2ValueFuncExp', fs('value')),
				dfas('BridgeDFA', 'setDataFlowContext', $dataFlowContext, 'setBridgeToBeginning', true)
			));				
	}
}