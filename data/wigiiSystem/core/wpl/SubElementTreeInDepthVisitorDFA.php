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
 * A data flow activity which goes through a tree of subelements.
 * The input should be a flow of elements (can be root elements or sub elements).
 * Then given a linkName and/or a recursiveLinkName, goes through the tree of the sub-elements of the input element,
 * and pushes further in the flow StdClasses of the form :
 * 1. {depth=0, step=1, element=input element, nbOfChildren=nb of subelements for linkName}
 * 2. foreach subelement :
 * 		{depth>0, step=1, element=subelement, nbOfChildren=nb of subelements for linkName for depth+1}
 * 		go down recursively
 * 		{depth>0, step=2, element=subelement, visitedAllChildren=Boolean}
 * 3. {depth=0, step=2, element=input element, visitedAllChildren=Boolean}
 * Where step=1=actOnTreeNode, step=2=actAfterTreeNode
 * See interface TreeInDepthVisitor for details about generated events.
 *
 * Created by CWE on 18 mars 2014
 */
class SubElementTreeInDepthVisitorDFA implements DataFlowActivity, TreeInDepthVisitor, ElementPList
{	
	private $_debugLogger;
	private $apiClient;
	private $dataFlowContext;
	private $linkName;
	private $recursiveLinkName;
	private $actOnTreeNodeCallback;
	private $actAfterTreeNodeCallback;
	private $includeStoppingNodeInOutput;
	private $cutIndex;
	private $depth;
	private $nElements;
	
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("SubElementTreeInDepthVisitorDFA");
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
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
		$this->includeStoppingNodeInOutput = true;		
		$this->cutIndex = array();
		$this->nElements = array();
		$this->depth = 0;
	}	
	public function freeMemory() {
		unset($this->linkName);
		unset($this->recursiveLinkName);
		unset($this->actOnTreeNodeCallback);
		unset($this->actAfterTreeNodeCallback);
		unset($this->eltS); /* unsets injected element service because it can come from the data flow context */
		unset($this->apiClient);
		unset($this->dataFlowContext);
		unset($this->cutIndex);
		unset($this->nElements);
	}

	// configuration

	/**
	 * Sets the name of the link to follow from the input element.
	 * @param String $linkName the link name. If recursiveLinkName is not defined, then this link name will be used to visit the whole sub elements tree,
	 * else this link is only used for first level (depth = 0).
	 */
	public function setLinkName($linkName) {
		$this->linkName = $linkName;
	}
	protected function getLinkName() {
		return $this->linkName;
	}
	
	/**
	 * Sets the name of the link that will be recursively followed when visiting the sub elements tree.
	 * @param String $recLinkName the recursive link name. If linkName is not defined, then this recursive link name will also be used to visit the first level (depth = 0), 
	 * else this recursive link name will be used only for next levels (depth > 0).
	 */
	public function setRecursiveLinkName($recLinkName) {
		$this->recursiveLinkName = $recLinkName;
	}
	protected function getRecursiveLinkName() {
		return $this->recursiveLinkName;
	}
	
	/**
	 * Sets the callback that should be called on the actOnTreeNode visitor event.<br/>
	 * The callback signature is : <code>actOnTreeNode(element, depth, numberOfChildren) return boolean</code><br/>,
	 * where arguments are :
	 * - Arg(0) element: Element|ElementP, the current element or elementP
	 * - Arg(1) depth: Int, the current depth in the tree, root is 0.
	 * - Arg(2) numberOfChildren: Int, the number of direct sub elements.
	 * Returns boolean: if false, then visits stops at this level, else it continues deeper
	 * See the interface TreeInDepthVisitor for behavior contract details.
	 * @param Closure|String $method a closure representing the code that should be executed or
	 * a string which is an object method name or a function name
	 * @param Any $obj an optional object instance which holds the method instance that should be executed.
	 */
	public function setActOnTreeNodeCallback($method, $obj=null) {
		$this->actOnTreeNodeCallback = CallableObject::createInstance($method, $obj);
	}
	/**
	 * @return CallableObject
	 */
	protected function getActOnTreeNodeCallback() {
		return $this->actOnTreeNodeCallback;
	}
	
	/**
	 * Sets the callback that should be called on the actAfterTreeNode visitor event.<br/>
	 * The callback signature is : <code>actAfterTreeNode(element, depth, visitedAllChildren) return boolean</code><br/>,
	 * where arguments are :
	 * - Arg(0) element: Element|ElementP, the current element or elementP
	 * - Arg(1) depth: Int, the current depth in the tree, root is 0.
	 * - Arg(2) visitedAllChildren: Boolean, true if all children have been visited.
	 * Returns boolean: if false, then visits backtracks to parent, else continues by visiting peers on same level
	 * See the interface TreeInDepthVisitor for behavior contract details.
	 * @param Closure|String $method a closure representing the code that should be executed or
	 * a string which is an object method name or a function name
	 * @param Any $obj an optional object instance which holds the method instance that should be executed.
	 */
	public function setActAfterTreeNodeCallback($method, $obj=null) {
		$this->actAfterTreeNodeCallback = CallableObject::createInstance($method, $obj);
	}
	/**
	 * @return CallableObject
	 */
	protected function getActAfterTreeNodeCallback() {
		return $this->actAfterTreeNodeCallback;
	}
	
	/**
	 * If true (default behavior), then node which has triggered a tree cut is sent to ouput,
	 * else if false, then node that triggered a tree cut is not sent to output.
	 */
	public function setIncludeStoppingNodeInOutput($bool) {
		$this->includeStoppingNodeInOutput = $bool;
	}
	protected function getIncludeStoppingNodeInOutput() {
		return $this->includeStoppingNodeInOutput;
	}
	
	// Context
	
	/**
	 * Returns current data flow context that can be used to generate some output.
	 * @return DataFlowContext
	 */
	protected function getDataFlowContext() {
		return $this->dataFlowContext;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		// checks the presence of a GroupBasedWigiiApiClient		
		// in that case, takes the provided element service instance
		$this->apiClient = $dataFlowContext->getAttribute('GroupBasedWigiiApiClient');
		if(!isset($this->eltS) && isset($this->apiClient)) $this->setElementService($this->apiClient->getElementService());		
	}
	public function processDataChunk($data, $dataFlowContext) {
		$this->dataFlowContext = $dataFlowContext;		
		$this->visitSubElements($data, 0);
	}
	public function endOfStream($dataFlowContext) {
		/* nothing to do */
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
	
	// TreeInDepthVisitor implementation
	
	public function actOnTreeNode($object, $depth, $numberOfChildren) {
		$this->getDataFlowContext()->writeResultToOutput((object)array(
			'depth' => $depth,
			'step' => 1,
			'element' => $object,
			'nbOfChildren' => $numberOfChildren	
		), $this);
		return true;
	}
	
	public function actAfterTreeNode($object, $depth, $visitedAllChildren) {
		$this->getDataFlowContext()->writeResultToOutput((object)array(
			'depth' => $depth,
			'step' => 2,
			'element' => $object,
			'visitedAllChildren' => $visitedAllChildren	
		), $this);
		return true;
	}
	
	// Implementation
	
	/**
	 * Recursively visits all sub elements from the given element, 
	 * using the linkName (if depth = 0 || recursiveLinkName not defined) or 
	 * recursiveLinkName (if depth > 0 || linkName not defined)
	 * @param Element|ElementP $element the current element for which to visit sub elements
	 * @param Int $depth the current depth, 0 if root.
	 */
	protected function visitSubElements($elementP, $depth) {
		$element = $elementP->getDbEntity();
		if($depth == 0) {
			$linkName = $this->getLinkName();
			if(empty($linkName)) $linkName = $this->getRecursiveLinkName();
		}
		else {
			$linkName = $this->getRecursiveLinkName();
			if(empty($linkName)) $linkName = $this->getLinkName();
		}
		$nbChildren = $element->getFieldValue($linkName);
		
		// 1. acts on current node
		$callback = $this->getActOnTreeNodeCallback();
		if(isset($callback)) $continue = $callback->invoke($elementP, $depth, $nbChildren);
		else $continue = true;
		if($continue || $this->getIncludeStoppingNodeInOutput()) {
			 $continue = $this->actOnTreeNode($elementP, $depth, $nbChildren) && $continue;
		}
		// 2. goes down the subtree
		if($continue) {
			$this->depth = $depth+1;
			$this->cutIndex[$this->depth] = 0;
			$this->nElements[$this->depth] = 0;
			$this->getElementService()->getSubElementsForField($this->getDataFlowContext()->getPrincipal(), 
					$element->getId(), $linkName, $this);
		}
		// 3. acts after current node
		$callback = $this->getActAfterTreeNodeCallback();
		$visitedAllChildren = ($this->cutIndex[$depth+1] == 0) || ($this->cutIndex[$depth+1] == $nbChildren);
		if(isset($callback)) $continue = $callback->invoke($elementP, $depth, $visitedAllChildren);
		if($continue || $this->getIncludeStoppingNodeInOutput()) {
			$continue = $this->actAfterTreeNode($elementP, $depth, $visitedAllChildren) && $continue;			
		}
		return $continue;			
	}
	
	// ElementPList implementation
	
	public function addElementP($elementP) {
		$depth = $this->depth;
		$this->nElements[$depth] += 1;
		if($this->cutIndex[$depth] == 0) {
			if(!$this->visitSubElements($elementP, $depth)) $this->cutIndex[$depth] = $this->nElements[$depth];
		}
		$this->depth = $depth;
	}
	
	public function getListIterator() {throw new ElementServiceException("The SubElementTreeInDepthVisitorDFA cannot be iterated. It is a forward only push of elements into the data flow.", ElementServiceException::UNSUPPORTED_OPERATION);}
	public function isEmpty() {return $this->nElements[$this->depth] == 0;}	
	public function count() {return $this->nElements[$this->depth];}
	public function createFieldList() {return FieldListArrayImpl::createInstance();}	
	public function createWigiiBag() {return WigiiBagBaseImpl::createInstance();}
}