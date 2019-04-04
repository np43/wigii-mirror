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
 * A Wigii Graph Node
 * Created by CWE on 24 sept 2013
 */
interface WigiiGraphNode {
	/**
	 * Returns a reference to the WigiiGraph in which this node lives.
	 */
	public function getWigiiGraph();
	
	/**
	 * Returns the value of the node if set, else returns null
	 */
	public function getValue();
	/**
	 * Stores a value in the node. Can be any object, any value or null.
	 */
	public function setValue($val);
	/**
	 * Links a WigiiGraphNodeListener to this node which will be notified of any changes of values
	 * Set null to detach an existing listener.
	 */
	public function setListener($wigiiGraphNodeListener);
	/**
	 * Returns the currently attached WigiiGraphNodeListener or null if none
	 */
	public function getListener();
	
	/**
	 * Characterizes this node with an attribute
	 * @param String $key the attribute key
	 * @param mixed $val the attribute value. Accepts null. Any existing value under the given key is replaced.
	 */
	public function setAttribute($key, $val);
	/**
	 * Sets the attributes as defined the given array. 
	 * Uses the array key as attribute keys and the array values as attribute values.
	 * The values are shallow copied from the given array.
	 */
	public function setAttributesFromArray($attributes);
	/**
	 * Returns the value of an attribute characterizing this node or null if not defined
	 */
	public function getAttribute($key);
	/**
	 * Returns an iterator on the list of attributes attached to this node
	 * The iterator is compatible with the foreach control structure : foreach(WigiiGraphNode->getAttributeIterator() as $key=>$val){...}
	 */
	public function getAttributesIterator();
	/**
	 * Returns true if this node has some attributes defined (only counts the existence of keys, not the values)
	 */
	public function hasAttributes();
	/**
	 * Returns the number of attributes attached to this node. Only counts the existence of keys, not the values.
	 */
	public function countAttributes();
	/**
	 * Removes the given node attributes if defined
	 * Ignores invalid keys.
	 * @param array $keys an array of keys or one single key
	 */
	public function removeAttributes($keys);
	/**
	 * Removes all node attributes, except the given array of keys if defined
	 */
	public function removeAllAttributes($exceptTheseKeys=null);
	
	/**
	 * Links the node to another node under a given link name
	 * If a link already exists under this name, the linked node is replaced
	 * Also accepts null. In that case, the link is destroyed (with all its attributes).
	 * @param boolean $keepExistingAttributes optional parameter. 
	 * If true, then any existing link attributes under the given link name are preserved for the new linked node, else removes any link attributes.
	 */
	public function setLink($linkName, $wigiiGraphNode, $keepExistingAttributes=false);
	/**
	 * Returns the linked node under the given link name or null if not defined.
	 */
	public function getLink($linkName);
	/**
	 * Returns an iterator on the list of links departing from this node
	 * The iterator is compatible with the foreach control structure : foreach(WigiiGraphNode->getLinksIterator() as $linkName=>$wigiiGraphNode){...}
	 */
	public function getLinksIterator();
	/**
	 * Returns an iterator on the list of links departing from this node, but only returns the link name, not the linked node.
	 * The iterator is compatible with the foreach control structure : foreach(WigiiGraphNode->getLinkNamesIterator() as $linkName){...}
	 */
	public function getLinkNamesIterator();
	/**
	 * Returns true if this node is linked to another node with the given name, else returns false
	 */
	public function hasLink($linkName);
	/**
	 * Returns true if this node has some links to other nodes, else false
	 */
	public function hasLinks();
	/**
	 * Returns the number of links this node has to other nodes
	 */
	public function countLinks();
	/**
	 * Removes the given links if defined
	 * Ignores invalid names.
	 * @param array $linkNames an array of link names or one single link name
	 */
	public function removeLinks($linkNames);
	/**
	 * Removes all links, except the given array of link names if defined
	 * @param array $exceptTheseLinkNames an array of link names or one single link name
	 */
	public function removeAllLinks($exceptTheseLinkNames=null);
	
	/**
	 * Characterizes the link with an attribute
	 * @param String $linkName the name of the link for which to set an attribute. The link must exist, else throws an InvalidArgument Exception
	 * @param String $attrKey the attribute key
	 * @param mixed $attrVal the attribute value. Accepts null. Any existing value under the given key is replaced.
	 */
	public function setLinkAttribute($linkName, $attrKey, $attrVal);
	/**
	 * Sets the attributes for a given link as defined the given array. 
	 * Uses the array key as attribute keys and the array values as attribute values.
	 * The values are shallow copied from the given array.
	 * @param String $linkName the name of the link for which to set the attributes. The link must exist, else throws an InvalidArgument Exception.
	 */
	public function setLinkAttributesFromArray($linkName, $attributes);
	/**
	 * Returns the value of an attribute for a given link or null if the attribute is not defined.
	 * @param String $linkName the name of the link for which to retrieve the attribute value. The link must exist, else throws an InvalidArgument Exception.
	 * @param String $attrKey the name of the key for which to retrieve the attribute value.
	 * @return mixed the attribute value or null if the key is not defined
	 */
	public function getLinkAttribute($linkName, $attrKey);
	/**
	 * Returns an iterator on the list of attributes attached to this link
	 * The iterator is compatible with the foreach control structure : foreach(WigiiGraphNode->getLinkAttributeIterator($linkName) as $key=>$val){...}
	 * @param String $linkName the name of the link for which to retrieve the attribute values. The link must exist, else throws an InvalidArgument Exception.
	 */
	public function getLinkAttributesIterator($linkName);
	/**
	 * Returns true if this link has some attributes defined (only counts the existence of keys, not the values)
	 * @param String $linkName the name of the link for which to checks the existence of attributes. The link must exist, else throws an InvalidArgument Exception.
	 */
	public function hasLinkAttributes($linkName);
	/**
	 * Returns the number of attributes attached to this link. Only counts the existence of keys, not the values.
	 * @param String $linkName the name of the link for which to count the attributes. The link must exist, else throws an InvalidArgument Exception.
	 */
	public function countLinkAttributes($linkName);
	/**
	 * Removes the given link attributes if defined
	 * Ignores invalid keys.
	 * @param String $linkName the name of the link for which to remove the attributes. The link must exist, else throws an InvalidArgument Exception.
	 * @param array $keys an array of keys or one single key
	 */
	public function removeLinkAttributes($linkName, $keys);
	/**
	 * Removes all link attributes, except the given array of keys if defined
	 * @param String $linkName the name of the link for which to remove the attributes. The link must exist, else throws an InvalidArgument Exception.
	 * @param array $exceptTheseKeys an array of keys or one single key
	 */
	public function removeAllLinkAttributes($linkName, $exceptTheseKeys=null);		
}