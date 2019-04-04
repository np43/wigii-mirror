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
 * WigiiGraph service
 * A service which exposes some algorithms on graphs
 * Created by CWE on 9 juillet 2013
 */
class WigiiGraphService
{
	/**
	 * Recursively walks through a tree. The tree is an array of arrays.
	 * @param array $arrayTree the tree (an array of arrays) passed by reference
	 * @param string $actOnTreeNode an optional string representing a callback function (or method) name 
	 * which will be called for each visited tree node which are not leaves. 
	 * The function signature should be :
	 * 		$actOnTreeNode($key, $depth, $numberOfChildren)
	 * 		where : 
	 * 		$key is the array key of the current visited tree node
	 * 		$depth is the current depth in tree, root is 0.
	 * 		$numberOfChildren is the number of direct children of this tree node.
	 *		The callback function returns a boolean, if true, then the walk goes deeper in the tree,
	 *		else the visit stops at this level.
	 * 
	 * @param string $actAfterTreeNode an optional string representing a callback function (or method) name 
	 * which will be called for each visited tree node which are not leaves, after the children have been visited.
	 * The callback order on a tree node is : actOnTreeNode, if(true) visit children, actAfterTreeNode  
	 * The function signature should be :
	 * 		$actAfterTreeNode($key, $depth, $visitedAllChildren)
	 * 		where : 
	 * 		$key is the array key of the current visited tree node
	 * 		$depth is the current depth in tree, root is 0.
	 * 		$visitedAllChildren is true if all children nodes have been visited, else false.
	 *		The callback function returns a boolean, if true, then the walk continues with peer nodes on same level,
	 *		else the visit backtracks to parent node.
	 *
	 * @param string $actOnTreeLeaf an optional string representing a callback function (or method) name 
	 * which will be called for each visited tree leaf (a node with no children node).
	 * The function signature should be :
	 * 		$actOnTreeLeaf($key, $value, $depth)
	 * 		where : 
	 * 		$key is the array key of the current tree leaf
	 * 		$value is the array value of the current tree leaf 
	 * 		$depth is the current depth in tree, root is 0.
	 *		The callback function returns a boolean, if true, then the walk continues with peer leaves on same level,
	 *		else the visit backtracks to parent node.
	 *
	 * Note that only leaves have values. Other nodes are storing sub arrays as the tree is an array of arrays.
	 * 
	 * @param Object $object an optional object instance which exposes the callback methods
	 * 
	 * returns true if whole tree has been visited, else false.
	 * throws WigiiGraphServiceException in case of error.
	 */
	public function walkThroughArrayTree(&$arrayTree, $actOnTreeNode=null, $actAfterTreeNode=null, $actOnTreeLeaf=null, $object=null) {
		if(is_null($arrayTree)) throw new WigiiGraphServiceException("arrayTree cannot be null", WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!is_array($arrayTree)) throw new WigiiGraphServiceException("arrayTree should be an array", WigiiGraphServiceException::INVALID_ARGUMENT);
		// checks methods
		if(isset($object)) {
			if(isset($actOnTreeNode) && $actOnTreeNode != '') {
				if(!method_exists($object, $actOnTreeNode)) throw new WigiiGraphServiceException("method '$actOnTreeNode' is not defined on given object", WigiiGraphServiceException::INVALID_ARGUMENT);
				$doActOnTreeNode = true;
			}
			else $doActOnTreeNode = false;
			if(isset($actAfterTreeNode) && $actAfterTreeNode != '') {
				if(!method_exists($object, $actAfterTreeNode)) throw new WigiiGraphServiceException("method '$actAfterTreeNode' is not defined on given object", WigiiGraphServiceException::INVALID_ARGUMENT);
				$doActAfterTreeNode = true;			
			}
			else $doActAfterTreeNode = false;
			if(isset($actOnTreeLeaf) && $actOnTreeLeaf != '') {
				if(!method_exists($object, $actOnTreeLeaf)) throw new WigiiGraphServiceException("method '$actOnTreeLeaf' is not defined on given object", WigiiGraphServiceException::INVALID_ARGUMENT);
				$doActOnTreeLeaf = true;
			}
			else $doActOnTreeLeaf = false;
		}
		// checks functions
		else {
			if(isset($actOnTreeNode) && $actOnTreeNode != '') {
				if(!function_exists($actOnTreeNode)) throw new WigiiGraphServiceException("function '$actOnTreeNode' is not defined", WigiiGraphServiceException::INVALID_ARGUMENT);
				$doActOnTreeNode = true;
			}
			else $doActOnTreeNode = false;
			if(isset($actAfterTreeNode) && $actAfterTreeNode != '') {
				if(!function_exists($actAfterTreeNode)) throw new WigiiGraphServiceException("function '$actAfterTreeNode' is not defined", WigiiGraphServiceException::INVALID_ARGUMENT);
				$doActAfterTreeNode = true;			
			}
			else $doActAfterTreeNode = false;
			if(isset($actOnTreeLeaf) && $actOnTreeLeaf != '') {
				if(!function_exists($actOnTreeLeaf)) throw new WigiiGraphServiceException("function '$actOnTreeLeaf' is not defined", WigiiGraphServiceException::INVALID_ARGUMENT);
				$doActOnTreeLeaf = true;
			}
			else $doActOnTreeLeaf = false;
		}		

		return $this->doRecursiveWalkThroughArrayTree($arrayTree, 0, $doActOnTreeNode, $doActAfterTreeNode, $doActOnTreeLeaf, 
														$actOnTreeNode, $actAfterTreeNode, $actOnTreeLeaf, $object);
	}
	
	/**
	 * Walks recursively through the tree
	 * Precondition: $arrayTree is an array, $depth a positive integer. 
	 * Returns true if whole tree has been visited, else false.
	 */
	protected function doRecursiveWalkThroughArrayTree(&$arrayTree, $depth, 
		$doActOnTreeNode, $doActAfterTreeNode, $doActOnTreeLeaf,
		$actOnTreeNode, $actAfterTreeNode, $actOnTreeLeaf, $object) {		
		$returnValue = true; $continueVisit = true;
		foreach($arrayTree as $key => &$value) {
			// if cut peers then breaks loop
			if(!$continueVisit) {
				$returnValue = false; 
				break;
			}
			// if tree node			
			if(is_array($value)) {
				// acts on tree node
				if($doActOnTreeNode) {
					$nChildren = count($value);
					if(isset($object)) $continueVisit = $object->$actOnTreeNode($key, $depth, $nChildren);
					else $continueVisit = $actOnTreeNode($key, $depth, $nChildren);
				}
				// walks recursively if no cut
				if($continueVisit) {
					$visitedAllChildren = $this->doRecursiveWalkThroughArrayTree($value, $depth+1, $doActOnTreeNode, $doActAfterTreeNode, $doActOnTreeLeaf, $actOnTreeNode, $actAfterTreeNode, $actOnTreeLeaf, $object);
				}
				else $visitedAllChildren = false;
				$returnValue = $returnValue && $visitedAllChildren;
				
				// acts after tree node
				if($doActAfterTreeNode) {
					if(isset($object)) $continueVisit = $object->$actAfterTreeNode($key, $depth, $visitedAllChildren);
					else $continueVisit = $actAfterTreeNode($key, $depth, $visitedAllChildren);					
				}
			}
			// else leaf
			elseif($doActOnTreeLeaf) {
				if(isset($object)) $continueVisit = $object->$actOnTreeLeaf($key, $value, $depth);
				else $continueVisit = $actOnTreeLeaf($key, $value, $depth);
			}			
		}
		unset($value);
		return $returnValue;
	}
	
	/**
	 * Stores a node value into a tree. The tree is an array of arrays.
	 * @param array $arrayTree the tree (an array of arrays) passed by reference in which to set the value
	 * @param array $pathAndValue an array giving the path to the node and the value. The array should be of one dimension,
	 * with numerical sorted keys, of the form [key1, key2, key3, ..., keyn, value] where :
	 * key1, ..., keyn are the tree node keys of the path from the root to the node for which we want to set a value.
	 * The last value of the array is the value to be stored in the node.
	 * Example: if the path to the node in the tree, starting from root, is /a/b/c/d, then pass an array equal to array('a', 'b', 'c', 'd', value);
	 * The value will be stored for node 'd'.
	 * If the path does not exist or partially exist in the tree, then the missing nodes are created.
	 * @param mixed $operator a storage operator. Should be one of SUPDOP_SET, SUPDOP_CAT, SUPDOP_ADD, SUPDOP_DEL, SUPDOP_SET_IF_NULL.
	 * case SUPDOP_SET:
	 * 	If the node already exists, then its value is replaced.
	 * 	If the value is an array of arrays, then the tree grows at the given node with a subtree.
	 * 	If the value is null, then the given node becomes an empty leaf.
	 * 	If the value is not an array, then the given node becomes a leaf with the given value.
	 * case SUPDOP_CAT: 
	 * 	the value is added to the list of existing values attached to this node
	 * 	the list can have several times the same value
	 * 	if only one value exists, then a list is created with the two values
	 *  if no value exist yet, then the value is set as the node value
	 * case SUPDOP_ADD:
	 * 	like CAT except that the value is added to the list of values only if the value does not exist in the list
	 * case SUPDOP_DEL:
	 * 	removes the value from the existing list of values.
	 *  if list becomes null, then stores null
	 * case SUPDOP_SET_IF_NULL: sets the value only if node is empty.
	 */
	public function storeNodeValueInArrayTree(&$arrayTree, $pathAndValue, $operator=SUPDOP_SET) {
		if(is_null($arrayTree)) throw new WigiiGraphServiceException("arrayTree cannot be null", WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!is_array($arrayTree)) throw new WigiiGraphServiceException("arrayTree should be an array", WigiiGraphServiceException::INVALID_ARGUMENT);
		if(is_null($pathAndValue)) throw new WigiiGraphServiceException("pathAndValue cannot be null", WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!is_array($pathAndValue)) throw new WigiiGraphServiceException("pathAndValue should be an array", WigiiGraphServiceException::INVALID_ARGUMENT);
		
		switch($operator) {
			case SUPDOP_SET:
			case SUPDOP_CAT:
			case SUPDOP_ADD: 
			case SUPDOP_DEL: 
			case SUPDOP_SET_IF_NULL:
				/* OK */
				break;
			default: throw new WigiiGraphServiceException("invalid operator. Should be one of SUPDOP_SET, SUPDOP_CAT, SUPDOP_ADD, SUPDOP_DEL, SUPDOP_SET_IF_NULL", WigiiGraphServiceException::INVALID_ARGUMENT);
		}
		
		$count = count($pathAndValue);
		// if path + value
		if($count >= 2) {
			$this->doRecursivelyStoreNodeValueInArrayTree($arrayTree, $pathAndValue, 0, $count, $operator);
		}
		// else if only value
		elseif($count == 1) {			
			$val = array_pop($pathAndValue);
			if($operator == SUPDOP_ADD && !in_array($val, $arrayTree)) $arrayTree[] = $val;
			elseif($operator == SUPDOP_CAT) $arrayTree[] = $val;
			else throw new WigiiGraphServiceException("if pathAndValue has only one value, then supported operator are only SUPDOP_ADD and SUPDOP_CAT", WigiiGraphServiceException::INVALID_ARGUMENT); 	
		}
		// else error
		else throw new WigiiGraphServiceException("pathAndValue should be an array with at least to values: first value is a key, second value is the value to be set", WigiiGraphServiceException::INVALID_ARGUMENT);
	}	
	
	/**
	 * Walks recursively in the tree until the node is found and stores its value.
	 * Preconditions: 
	 *  $arrayTree is an array, $pathAndValue is an array, 
	 *  $index is a positive integer, range from 0 to count(pathAndValue)-2,
	 *  $count is a positive integer equal to count(pathAndValue)
	 *  $operator is one of SUPDOP_SET, SUPDOP_CAT, SUPDOP_ADD, SUPDOP_DEL, SUPDOP_SET_IF_NULL
	 * Postcondition: value is set in the tree at the given node, missing nodes are created.
	 */
	protected function doRecursivelyStoreNodeValueInArrayTree(&$arrayTree, $pathAndValue, $index, $count, $operator) {
		$key = $pathAndValue[$index];
		$node =& $arrayTree[$key];
		// if index not yet on node, goes down recursively
		if($index < $count-2) {			
			if(!is_array($node)) $node = array();
			$this->doRecursivelyStoreNodeValueInArrayTree($node, $pathAndValue, $index+1, $count, $operator);			
		}
		// else updates the value using the given operator
		else {
			$val = $pathAndValue[$count-1];
			switch($operator) {
				case SUPDOP_SET:
					$node = $val;
					break;
				case SUPDOP_CAT:
					if(is_array($node)) $node[] = $val;
					elseif(isset($node)) $node = array($node, $val);
					else $node = $val;
					break;
				case SUPDOP_ADD:
					if(is_array($node)) {
						if(!in_array($val, $node)) $node[] = $val;
					}
					elseif(isset($node)) {
						if($node != $val) $node = array($node, $val);
					}
					else $node = $val;
					break;
				case SUPDOP_DEL:
					if(is_array($node)) {											
						$delKeys = array_keys($node, $val);
						if(!empty($delKeys)) {
							$diff = array_diff_key($node, array_combine($delKeys, $delKeys));						
							if(empty($diff)) $node = null;
							elseif(count($diff) == 1) $node = array_pop($diff);
							else $node = $diff;
						}
					}
					elseif(isset($node)) {
						if($node == $val) $node = null;
					}					
					break;
				case SUPDOP_SET_IF_NULL:
					if(!isset($node)) $node = $val;
					break;
				default: 
					unset($node);
					throw new WigiiGraphServiceException("unsupported operator, should be one of SUPDOP_SET, SUPDOP_CAT, SUPDOP_ADD, SUPDOP_DEL, SUPDOP_SET_IF_NULL", WigiiGraphServiceException::INVALID_ARGUMENT);								
			}			
		}
		unset($node);
	}
	
	
}