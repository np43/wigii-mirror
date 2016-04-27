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
 * A Wigii Graph implementation based on array storage
 * The whole graph resides into memory.
 * Created by CWE on 24 sept 2013
 */
class WigiiGraphArrayImpl extends Model implements WigiiGraph {
	private $parentGraphNode = null;
	protected $graphNodes = null;
	
	// Object lifecycle
	
	/**
	 * Creates a new instance of a Wigii Graph
	 * @param WigiiGraphNodeArrayImpl $parentGraphNode optionally links this graph to a parent graph node.
	 */
	public static function createInstance($parentGraphNode=null) {
		$returnValue = new self();
		$returnValue->setParentGraphNode($parentGraphNode);
		return $returnValue;
	}
		
	// Implementation
	
	public function getParentGraphNode() {
		return $this->parentGraphNode;
	}
	public function setParentGraphNode($wigiiGraphNodeArrayImpl) {
		if(isset($wigiiGraphNodeArrayImpl) && !($wigiiGraphNodeArrayImpl instanceof WigiiGraphNodeArrayImpl)) throw new WigiiGraphServiceException('parent graph node should be an instance of WigiiGraphNodeArrayImpl', WigiiGraphServiceException::INVALID_ARGUMENT);
		$this->parentGraphNode = $wigiiGraphNodeArrayImpl;
	}
	public function getGraphNodeIterator() {
		return $this->graphNodes;
	}
	public function isEmpty() {
		return empty($this->graphNodes);
	}
	public function countGraphNodes() {
		return count($this->graphNodes);
	}
	public function createGraphNode() {
		$returnValue = WigiiGraphNodeArrayImpl::createInstance($this);
		if(!isset($this->graphNodes)) $this->graphNodes = array();
		$this->graphNodes[] = $returnValue;
		return $returnValue;
	}		
}