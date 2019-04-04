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
 * Tree in width visitor
 * Created by CWE on 20 mars 10
 */
interface TreeInWidthVisitor
{
	/**
	 * Called to indicate to the visitor that it changed level in the tree
	 * Indicates him also the number of nodes present on this level
	 * If visitor returns true to this event then visit continues on this level, else it stops.
	 * First level is 0.
	 */
	public function changeToLevel($level, $numberOfNodes);

	/**
	 * Does something with the current tree node
	 * If visitor returns false then visits stops, else it continues
	 */
	public function actOnTreeNode($object);
}