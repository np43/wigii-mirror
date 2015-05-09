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
 * A Wigii Graph
 * Created by CWE on 24 sept 2013
 */
interface WigiiGraph {
	/**
	 * Returns a reference on the WigiiGraphNode of the parent graph containing this graph
	 * @return WigiiGraphNode a WigiiGraphNode instance or null if this graph has no parent graph
	 */
	public function getParentGraphNode();
	/**
	 * Returns an iterator on the list of WigiiGraphNodes contained in this graph
	 * The iterator is compatible with the foreach control structure : foreach(WigiiGraph->getWigiiGraphNodeIterator() as $wigiiGraphNode){...}
	 */
	public function getGraphNodeIterator();
	/**
	 * Returns true if this graph is empty (has no WigiiGraphNode)
	 */
	public function isEmpty();
	/**
	 * Returns the number of WigiiGraphNodes contained in this graph
	 */
	public function countGraphNodes();
	/**
	 * Creates a new unlinked WigiiGraphNode instance living in this graph
	 * @return WigiiGraphNode an instance of a WigiiGraphNode
	 */
	public function createGraphNode();		
}