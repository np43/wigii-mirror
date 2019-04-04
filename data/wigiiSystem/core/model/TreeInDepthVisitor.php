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
 * Tree in depth visitor
 * Created by CWE on 20 mars 10
 */
interface TreeInDepthVisitor
{
	/**
	 * Does something with the current tree node
	 * Indicates him the current depth in the tree, root is 0.
	 * Indicates him the number of direct children.
	 * If visitor returns false then visits stops at this level, else it continues deeper
	 */
	public function actOnTreeNode($object, $depth, $numberOfChildren);

	/**
	 * Does something on the current node after having visited the children nodes
	 * Indicates him the current depth in the tree, root is 0.
	 * Indicates him if all the children have been visited.
	 * If visitor returns false then visits backtracks to parent,
	 * else continues by visiting peers on same level
	 */
	public function actAfterTreeNode($object, $depth, $visitedAllChildren);
}