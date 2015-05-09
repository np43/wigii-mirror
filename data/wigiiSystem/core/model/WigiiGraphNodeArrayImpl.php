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
 * A Wigii Graph Node implementation based on array storage
 * Created by CWE on 24 sept 2013
 */
class WigiiGraphNodeArrayImpl extends Model implements WigiiGraphNode {
	private $wigiiGraph = null;
	private $value = null;
	private $wigiiGraphNodeListener = null;
	private $attributes = null;
	private $links = null;
	private $linkAttributes = null;
	
	
	// Object lifecycle
		
	/**
	 * Creates an instance of a WigiiGraphNode living in a given graph
	 * @param WigiiGraph $wigiiGraph the wigii graph hosting this node
	 */
	public static function createInstance($wigiiGraph) {
		$returnValue = new self();
		if(is_null($wigiiGraph) || !($wigiiGraph instanceof WigiiGraph)) throw new WigiiGraphServiceException('the wigii graph should be a non null instance of a WigiiGraph', WigiiGraphServiceException::INVALID_ARGUMENT);
		$returnValue->wigiiGraph = $wigiiGraph;
		return $returnValue;
	}
		
	// Graph Node Implementation
	
	public function getWigiiGraph() {
		return $this->wigiiGraph;
	}
	
	public function getValue() {
		return $this->value;
	}
	
	public function setValue($val) {
		$oldVal = null; $l = $this->getListener();
		if(isset($l) && !$l->listenToValueChange($this)) unset($l); 
		if(isset($l)) $oldVal = $this->value;
		$this->value = $val;
		if(isset($l)) $l->wgn_setValue($this, $oldVal, $val);
	}
	
	public function setListener($wigiiGraphNodeListener) {
		if(isset($wigiiGraphNodeListener) && !($wigiiGraphNodeListener instanceof WigiiGraphNodeListener)) throw new WigiiGraphServiceException('the wigii graph node listener should be an instance of a WigiiGraphNodeListener', WigiiGraphServiceException::INVALID_ARGUMENT);
		$this->wigiiGraphNodeListener = $wigiiGraphNodeListener;
	}
	
	public function getListener() {
		return $this->wigiiGraphNodeListener;
	}
	
	
	// Graph Node attributes
	
	public function setAttribute($key, $val) {
		if(is_null($key)) throw new WigiiGraphServiceException('graph node attribute key cannot be null', WigiiGraphServiceException::INVALID_ARGUMENT);		
		$oldVal = null; $l = $this->getListener();
		if(isset($l) && !$l->listenToAttributeChange($this)) unset($l);
		if(!isset($this->attributes)) $this->attributes = array();
		elseif(isset($l)) $oldVal = $this->attributes[$key];
		$this->attributes[$key] = $val;
		if(isset($l)) $l->wgn_setAttribute($this, $key, $oldVal, $val);
	}
	
	public function setAttributesFromArray($attributes) {
		if(empty($attributes)) return;
		if(!is_array($attributes)) throw new WigiiGraphServiceException('attributes should be an array', WigiiGraphServiceException::INVALID_ARGUMENT);
		$oldAttr = null; $l = $this->getListener();
		if(isset($l) && !$l->listenToAttributeChange($this)) unset($l);
		if(!isset($this->attributes)) $this->attributes = $attributes;
		else {
			if(isset($l)) $oldAttr = array_intersect_key($this->attributes, $attributes);
			$this->attributes = array_merge($this->attributes, $attributes);						
		}
		if(isset($l)) $l->wgn_setAttributes($this, $oldAttr, $attributes);
	}
	
	public function getAttribute($key) {
		if(is_null($key)) throw new WigiiGraphServiceException('graph node attribute key cannot be null', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!isset($this->attributes)) return null;
		else return $this->attributes[$key];
	}
	
	public function getAttributesIterator() {
		return $this->attributes;
	}
	
	public function hasAttributes() {
		return !empty($this->attributes);
	}
	
	public function countAttributes() {
		return count($this->attributes);
	}
	
	public function removeAttributes($keys) {
		if(empty($this->attributes)) return;
		if(empty($keys)) return;
		$oldAttr = null; $l = $this->getListener();
		if(isset($l) && !$l->listenToAttributeChange($this)) unset($l);
		if(is_array($keys)) {
			$keys = array_combine($keys, $keys);
			if(isset($l)) $oldAttr = array_intersect($this->attributes, $keys);
			$this->attributes = array_diff_key($this->attributes, $keys); 
		}
		else {
			if(isset($l)) $oldAttr = array($keys=>$this->attributes[$keys]);
			unset($this->attributes[$keys]);
		} 
		if(isset($l)) {
			if(empty($this->attributes)) $l->wgn_removeAllAttributes($this, $oldAttr);
			else $l->wgn_removeAttributes($this, $oldAttr);
		}
	}
	
	public function removeAllAttributes($exceptTheseKeys=null) {
		if(empty($this->attributes)) return;
		$oldAttr = null; $l = $this->getListener();
		if(isset($l) && !$l->listenToAttributeChange($this)) unset($l);
		if(isset($exceptTheseKeys)) {
			if(is_array($exceptTheseKeys)) {
				$exceptTheseKeys = array_combine($exceptTheseKeys, $exceptTheseKeys);
				if(isset($l)) $oldAttr = array_diff_key($this->attributes, $exceptTheseKeys);
				$this->attributes = array_intersect_key($this->attributes, $exceptTheseKeys);
			}
			else {
				if(isset($l)) $oldAttr = array_diff_key($this->attributes, array($exceptTheseKeys=>$exceptTheseKeys));
				$this->attributes = array($exceptTheseKeys=>$this->attributes[$exceptTheseKeys]);
			}
		}
		else {
			if(isset($l)) $oldAttr = $this->attributes;
			unset($this->attributes);
		}
		if(isset($l)) {
			if(empty($this->attributes)) $l->wgn_removeAllAttributes($this, $oldAttr);
			else $l->wgn_removeAttributes($this, $oldAttr);
		}
	}
	
	
	// Graph Node links
	
	public function setLink($linkName, $wigiiGraphNode, $keepExistingAttributes=false) {
		if(is_null($linkName)) throw new WigiiGraphServiceException('link name cannot be null', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!isset($this->links)) {
			if(is_null($wigiiGraphNode)) return;
			else $this->links = array();
		}
		$oldNode = $this->links[$linkName];
		if($wigiiGraphNode !== $oldNode) {
			$l = $this->getListener(); $exc = null;
			if(isset($l) && !$l->listenToLinkChange($this)) unset($l);
			$this->links[$linkName] = $wigiiGraphNode;
			if(!$keepExistingAttributes) $this->removeAllLinkAttributes($linkName);
			if(isset($oldNode)) {
				$dl = $oldNode->getListener();
				if(isset($dl) && $dl !== $l && $dl->listenToLinkChange($oldNode)) {
					try {
						$dl->wgn_unlinkedFrom($oldNode, $linkName, $this);
					}	
					catch(Exception $e) {if(!isset($exc)) $exc = $e;}
				}
			}
			if(isset($l)) {
				try {
					$l->wgn_setLink($this, $linkName, $oldNode, $wigiiGraphNode, $keepExistingAttributes);
				}	
				catch(Exception $e) {if(!isset($exc)) $exc = $e;}
			}
			if(isset($wigiiGraphNode)) {
				$dl = $wigiiGraphNode->getListener();
				if(isset($dl) && $dl !== $l && $dl->listenToLinkChange($wigiiGraphNode)) {
					try {
						$dl->wgn_linkedFrom($wigiiGraphNode, $linkName, $this);
					}
					catch(Exception $e) {if(!isset($exc)) $exc = $e;}
				}
			}
			if(isset($exc)) throw $exc;
		}		
	}
	
	public function getLink($linkName) {
		if(is_null($linkName)) throw new WigiiGraphServiceException('link name cannot be null', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!isset($this->links)) return null;
		else return $this->links[$linkName];
	}
	
	public function getLinksIterator() {
		return $this->links;
	}
	
	public function getLinkNamesIterator() {
		if(!isset($this->links)) return null;
		else return array_keys($this->links);
	}
	
	public function hasLink($linkName) {
		if(is_null($linkName)) throw new WigiiGraphServiceException('link name cannot be null', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!isset($this->links)) return false;
		else return !is_null($this->links[$linkName]);
	}
	
	public function hasLinks() {
		return !empty($this->links);
	}
	
	public function countLinks() {
		return count($this->links);
	}
	
	public function removeLinks($linkNames) {
		if(empty($this->links)) return;
		if(empty($linkNames)) return;
		$oldLinks = null; $oldLinkAttributes = null; $l = $this->getListener(); $exc = null;
		if(isset($l) && !$l->listenToLinkChange($this)) unset($l);
		if(is_array($linkNames)) {
			$linkNames = array_combine($linkNames, $linkNames);
			$oldLinks = array_intersect_key($this->links, $linkNames);
			$this->links = array_diff_key($this->links, $linkNames);
			if(isset($this->linkAttributes)) {
				if(isset($l)) $oldLinkAttributes = array_intersect_key($this->linkAttributes, $linkNames);
				$this->linkAttributes = array_diff_key($this->linkAttributes, $linkNames);
			} 			
		}
		else {
			$oldLinks = array($linkNames=>$this->links[$linkNames]);
			unset($this->links[$linkNames]);
			if(isset($this->linkAttributes)) {
				if(isset($l)) $oldLinkAttributes = array($linkNames=>$this->linkAttributes[$linkNames]);
				unset($this->linkAttributes[$linkNames]);
			}
		} 
		if(isset($l)) {
			try {
				if(empty($this->links)) $l->wgn_removeAllLinks($this, $oldLinks, $oldLinkAttributes);
				else $l->wgn_removeLinks($this, $oldLinks, $oldLinkAttributes);
			}
			catch(Exception $e) {if(!isset($exc)) $exc = $e;}			
		}
		if(!empty($oldLinks)) {
			foreach($oldLinks as $linkName => $oldNode) {
				if(isset($oldNode)) {
					$dl = $oldNode->getListener();					
					if(isset($dl) && $dl !== $l && $dl->listenToLinkChange($oldNode)) {
						try {
							$dl->wgn_unlinkedFrom($oldNode, $linkName, $this);
						}
						catch(Exception $e) {if(!isset($exc)) $exc = $e;}
					}
				}
			}
		}
		if(isset($exc)) throw $exc;
	}
	
	public function removeAllLinks($exceptTheseLinkNames=null) {
		if(empty($this->links)) return;
		$oldLinks = null; $oldLinkAttributes = null; $l = $this->getListener(); $exc = null;
		if(isset($l) && !$l->listenToLinkChange($this)) unset($l);
		if(isset($exceptTheseLinkNames)) {
			if(is_array($exceptTheseLinkNames)) {
				$exceptTheseLinkNames = array_combine($exceptTheseLinkNames, $exceptTheseLinkNames);
				$oldLinks = array_diff_key($this->links, $exceptTheseLinkNames);
				$this->links = array_intersect_key($this->links, $exceptTheseKeys);
				if(isset($this->linkAttributes)) {
					if(isset($l)) $oldLinkAttributes = array_diff_key($this->linkAttributes, $exceptTheseLinkNames);
					$this->linkAttributes = array_intersect($this->linkAttributes, $exceptTheseLinkNames);
				}
			}
			else {
				$exceptTheseLinkNames = array($exceptTheseLinkNames=>$exceptTheseLinkNames);
				$oldLinks = array_diff_key($this->links, $exceptTheseLinkNames);
				$this->links = array($exceptTheseLinkNames=>$this->links[$exceptTheseLinkNames]);
				if(isset($this->linkAttributes)) {
					if(isset($l)) $oldLinkAttributes = array_diff_key($this->linkAttributes, $exceptTheseLinkNames);
					$this->linkAttributes = array($exceptTheseLinkNames=>$this->linkAttributes[$exceptTheseLinkNames]);
				}
			}
		}
		else {
			$oldLinks = $this->links;
			unset($this->links);
			if(isset($l)) $oldLinkAttributes = $this->linkAttributes;
			unset($this->linkAttributes);
		}
		if(isset($l)) {
			try {
				if(empty($this->links)) $l->wgn_removeAllLinks($this, $oldLinks, $oldLinkAttributes);
				else $l->wgn_removeLinks($this, $oldLinks, $oldLinkAttributes);
			}
			catch(Exception $e) {if(!isset($exc)) $exc = $e;}			
		}
		if(!empty($oldLinks)) {
			foreach($oldLinks as $linkName => $oldNode) {
				if(isset($oldNode)) {
					$dl = $oldNode->getListener();
					if(isset($dl) && $dl !== $l && $dl->listenToLinkChange($oldNode)) {
						try {
							$dl->wgn_unlinkedFrom($oldNode, $linkName, $this);
						}	
						catch(Exception $e) {if(!isset($exc)) $exc = $e;}
					}
				}
			}
		}
		if(isset($exc)) throw $exc;
	}
	
	
	// Graph Node link attributes
	
	public function setLinkAttribute($linkName, $attrKey, $attrVal) {
		if(is_null($attrKey)) throw new WigiiGraphServiceException('graph node link attribute key cannot be null', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!$this->hasLink($linkName)) throw new WigiiGraphServiceException('the given link name is undefined', WigiiGraphServiceException::INVALID_ARGUMENT);
		$oldVal = null; $l = $this->getListener();
		if(isset($l) && !$l->listenToLinkAttributeChange($this)) unset($l);
		if(!isset($this->linkAttributes)) $this->linkAttributes = array();
		if(!isset($this->linkAttributes[$linkName])) $this->linkAttributes[$linkName] = array();
		elseif(isset($l)) $oldVal = $this->linkAttributes[$linkName][$attrKey];
		$this->linkAttributes[$linkName][$attrKey] = $attrVal;
		if(isset($l)) $l->wgn_setLinkAttribute($this, $linkName, $attrKey, $oldVal, $attrVal);
	}
	
	public function setLinkAttributesFromArray($linkName, $attributes) {
		if(!$this->hasLink($linkName)) throw new WigiiGraphServiceException('the given link name is undefined', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(empty($attributes)) return;
		if(!is_array($attributes)) throw new WigiiGraphServiceException('attributes should be an array', WigiiGraphServiceException::INVALID_ARGUMENT);
		$oldAttr = null; $l = $this->getListener();
		if(isset($l) && !$l->listenToLinkAttributeChange($this)) unset($l);
		if(!isset($this->linkAttributes)) $this->linkAttributes = array();
		if(!isset($this->linkAttributes[$linkName])) $this->linkAttributes[$linkName] = $attributes;
		else {
			if(isset($l)) $oldAttr = array_intersect_key($this->linkAttributes[$linkName], $attributes);
			$this->linkAttributes[$linkName] = array_merge($this->linkAttributes[$linkName], $attributes);
		}		
		if(isset($l)) $l->wgn_setLinkAttributes($this, $linkName, $oldAttr, $attributes);
	}
	
	public function getLinkAttribute($linkName, $attrKey) {
		if(is_null($attrKey)) throw new WigiiGraphServiceException('graph node link attribute key cannot be null', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!$this->hasLink($linkName)) throw new WigiiGraphServiceException('the given link name is undefined', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!isset($this->linkAttributes)) return null;
		if(!isset($this->linkAttributes[$linkName])) return null;
		else return $this->linkAttributes[$linkName][$attrKey];
	}
	
	public function getLinkAttributesIterator($linkName) {		
		if(!$this->hasLink($linkName)) throw new WigiiGraphServiceException('the given link name is undefined', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!isset($this->linkAttributes)) return null;
		return $this->linkAttributes[$linkName];		
	}
	
	public function hasLinkAttributes($linkName) {
		if(!$this->hasLink($linkName)) throw new WigiiGraphServiceException('the given link name is undefined', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!isset($this->linkAttributes)) return false;
		return !empty($this->linkAttributes[$linkName]);
	}
	
	public function countLinkAttributes($linkName) {
		if(!$this->hasLink($linkName)) throw new WigiiGraphServiceException('the given link name is undefined', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(!isset($this->linkAttributes)) return 0;
		return count($this->linkAttributes[$linkName]);
	}
	
	public function removeLinkAttributes($linkName, $keys) {
		if(!$this->hasLink($linkName)) throw new WigiiGraphServiceException('the given link name is undefined', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(empty($this->linkAttributes)) return;
		if(empty($this->linkAttributes[$linkName])) return;
		if(empty($keys)) return;
		$oldAttr = null; $l = $this->getListener();
		if(isset($l) && !$l->listenToLinkAttributeChange($this)) unset($l);
		if(is_array($keys)) {
			$keys = array_combine($keys, $keys);
			if(isset($l)) $oldAttr = array_intersect($this->linkAttributes[$linkName], $keys);
			$this->linkAttributes[$linkName] = array_diff_key($this->linkAttributes[$linkName], $keys); 
		}
		else {
			if(isset($l)) $oldAttr = array($keys=>$this->linkAttributes[$linkName][$keys]);
			unset($this->linkAttributes[$linkName][$keys]);
		} 
		if(isset($l)) {
			if(empty($this->linkAttributes[$linkName])) $l->wgn_removeAllLinkAttributes($this, $linkName, $oldAttr);
			else $l->wgn_removeLinkAttributes($this, $linkName, $oldAttr);
		}
	}
	
	public function removeAllLinkAttributes($linkName, $exceptTheseKeys=null) {
		if(!$this->hasLink($linkName)) throw new WigiiGraphServiceException('the given link name is undefined', WigiiGraphServiceException::INVALID_ARGUMENT);
		if(empty($this->linkAttributes)) return;		
		if(empty($this->linkAttributes[$linkName])) return;
		$oldAttr = null; $l = $this->getListener();
		if(isset($l) && !$l->listenToLinkAttributeChange($this)) unset($l);
		if(isset($exceptTheseKeys)) {
			if(is_array($exceptTheseKeys)) {
				$exceptTheseKeys = array_combine($exceptTheseKeys, $exceptTheseKeys);
				if(isset($l)) $oldAttr = array_diff_key($this->linkAttributes[$linkName], $exceptTheseKeys);
				$this->linkAttributes[$linkName] = array_intersect_key($this->linkAttributes[$linkName], $exceptTheseKeys);
			}
			else {
				if(isset($l)) $oldAttr = array_diff_key($this->linkAttributes[$linkName], array($exceptTheseKeys=>$exceptTheseKeys));
				$this->linkAttributes[$linkName] = array($exceptTheseKeys=>$this->linkAttributes[$linkName][$exceptTheseKeys]);
			}
		}
		else {
			if(isset($l)) $oldAttr = $this->linkAttributes[$linkName];
			unset($this->linkAttributes[$linkName]);
		}
		if(isset($l)) {
			if(empty($this->linkAttributes[$linkName])) $l->wgn_removeAllLinkAttributes($this, $linkName, $oldAttr);
			else $l->wgn_removeLinkAttributes($this, $linkName, $oldAttr);
		}
	}		
}