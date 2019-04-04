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
 * A Wigii Graph Node Listener
 * Created by CWE on 24 sept 2013
 */
interface WigiiGraphNodeListener {

	// Listener configuration
	
	/**
	 * Returns true if the listener is sensitive to changes of the node value for the given wigiiGraphNode
	 */
	public function listenToValueChange($wigiiGraphNode);
	/**
	 * Returns true if the listener is sensitive to changes on attributes for the given wigiiGraphNode
	 */
	public function listenToAttributeChange($wigiiGraphNode);
	/**
	 * Returns true if the listener is sensitive to changes on links for the given wigiiGraphNode
	 */
	public function listenToLinkChange($wigiiGraphNode);
	/**
	 * Returns true if the listener is sensitive to changes on link attributes for the given wigiiGraphNode
	 */
	public function listenToLinkAttributeChange($wigiiGraphNode);
	
	// Listener events
	
	/**
	 * The listener is notified that the WigiiGraphNode value has changed. 
	 * This event is triggered only if the method listenToValueChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance which triggered the event
	 * @param mixed  $oldValue the existing value which now changed
	 * @param mixed $newValue the new set value
	 */
	public function wgn_setValue($wigiiGraphNode, $oldValue, $newValue);
	/**
	 * The listener is notified that an attribute on the WigiiGraphNode has changed. 
	 * This event is triggered only if the method listenToAttributeChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance which triggered the event
	 * @param String $key the key of the attribute which has been set
	 * @param mixed $oldValue the existing value which now changed
	 * @param mixed $newValue the new set value
	 */
	public function wgn_setAttribute($wigiiGraphNode, $key, $oldValue, $newValue);
	/**
	 * The listener is notified that some attributes on the WigiiGraphNode have changed. 
	 * This event is triggered only if the method listenToAttributeChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance which triggered the event
	 * @param array $oldAttributes an array with the existing attributes values (key=>value), which now changed
	 * @param array $newAttributes an array with the new attributes values (key=>value)
	 */
	public function wgn_setAttributes($wigiiGraphNode, $oldAttributes, $newAttributes);
	/**
	 * The listener is notified that some attributes on the WigiiGraphNode have been removed. 
	 * This event is triggered only if the method listenToAttributeChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance which triggered the event
	 * @param array $attributes an array with the existing attributes values (key=>value), which have been removed
	 */
	public function wgn_removeAttributes($wigiiGraphNode, $attributes);
	/**
	 * The listener is notified that all the attributes on the WigiiGraphNode have been removed. 
	 * This event is triggered only if the method listenToAttributeChange returns true.
	 * @param WigiiGraphNode$wigiiGraphNode the WigiiGraphNode instance which triggered the event
	 * @param array $attributes an array with the existing attributes values (key=>value), which have been removed
	 */
	public function wgn_removeAllAttributes($wigiiGraphNode, $attributes);
	/**
	 * The listener is notified that a link on the WigiiGraphNode has changed. 
	 * This event is triggered only if the method listenToLinkChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance which triggered the event
	 * @param String $linkName the name of the link which has been set
	 * @param WigiiGraphNode $oldNode the existing linked WigiiGraphNode instance which now changed
	 * @param WigiiGraphNode $newNode the new linked WigiiGraphNode
	 * @param Boolean $keptLinkAttributes if true, then means that the node changed, but the link attributes where preserved.
	 */
	public function wgn_setLink($wigiiGraphNode, $linkName, $oldNode, $newNode, $keptLinkAttributes=false);
	/**
	 * The listener is notified that some attributes on the WigiiGraphNode have been removed. 
	 * This event is triggered only if the method listenToLinkChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance which triggered the event
	 * @param array $links an array with the existing links (linkName=>WigiiGraphNode), which have been removed
	 * @param array $linkAttributes an array of array containing the link attributes for each removed link (linkName=>[key=>value])
	 */
	public function wgn_removeLinks($wigiiGraphNode, $links, $linkAttributes);
	/**
	 * The listener is notified that all the links on the WigiiGraphNode have been removed. 
	 * This event is triggered only if the method listenToLinkChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance which triggered the event
	 * @param array $links an array with the existing links (linkName=>WigiiGraphNode), which have been removed
	 * @param array $linkAttributes an array of array containing the link attributes for each removed link (linkName=>[key=>value])
	 */
	public function wgn_removeAllLinks($wigiiGraphNode, $links, $linkAttributes);
	/**
	 * The listener is notified that a link attribute on the WigiiGraphNode has changed. 
	 * This event is triggered only if the method listenToLinkAttributeChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance which triggered the event
	 * @param String $linkName the name of the link which has an attribute which has changed.
	 * @param String $key the key of the attribute which has been set
	 * @param mixed $oldValue the existing value which now changed
	 * @param mixed $newValue the new set value
	 */
	public function wgn_setLinkAttribute($wigiiGraphNode, $linkName, $key, $oldValue, $newValue);
	/**
	 * The listener is notified that some link attributes on the WigiiGraphNode have changed. 
	 * This event is triggered only if the method listenToLinkAttributeChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance which triggered the event
	 * @param String $linkName the name of the link which has some attributes which have changed.
	 * @param array $oldAttributes an array with the existing attributes values (key=>value), which now changed
	 * @param array $newAttributes an array with the new attributes values (key=>value)
	 */
	public function wgn_setLinkAttributes($wigiiGraphNode, $linkName, $oldAttributes, $newAttributes);
	/**
	 * The listener is notified that some link attributes on the WigiiGraphNode have been removed. 
	 * This event is triggered only if the method listenToLinkAttributeChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance which triggered the event
	 * @param String $linkName the name of the link which has some attributes which have been removed.
	 * @param array $attributes an array with the existing attributes values (key=>value), which have been removed
	 */
	public function wgn_removeLinkAttributes($wigiiGraphNode, $linkName, $attributes);
	/**
	 * The listener is notified that all the link attributes on the WigiiGraphNode have been removed. 
	 * This event is triggered only if the method listenToLinkAttributeChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance which triggered the event
	 * @param String $linkName the name of the link which has all the attributes which have been removed.
	 * @param array $attributes an array with the existing attributes values (key=>value), which have been removed
	 */
	public function wgn_removeAllLinkAttributes($wigiiGraphNode, $linkName, $attributes);
	/**
	 * The listener is notified that the WigiiGraphNode has been linked from another node
	 * This event is triggered only if the method listenToLinkChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance which has been linked
	 * @param String $linkName the name of the link
	 * @param WigiiGraphNode $originNode the WigiiGraphNode instance which created the link
	 */
	public function wgn_linkedFrom($wigiiGraphNode, $linkName, $originNode);
	/**
	 * The listener is notified that the WigiiGraphNode has been unlinked from another node
	 * This event is triggered only if the method listenToLinkChange returns true.
	 * @param WigiiGraphNode $wigiiGraphNode the WigiiGraphNode instance for which a link has been removed
	 * @param String $linkName the name of the link
	 * @param WigiiGraphNode $originNode the WigiiGraphNode instance which removed the link
	 */
	public function wgn_unlinkedFrom($wigiiGraphNode, $linkName, $originNode);		
}