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
 * A list of LinkSelector
 * Created by CWE on 06 février 2014
 */
interface LinkSelectorList extends ObjectList
{
	/**
	 * Adds a linkSelector to the list
	 * @param LinkSelector $linkSelector	 
	 */
	public function addLinkSelector($linkSelector);
	
	/**
	 * Returns the first link selector in the list.
	 * In the case of sub element path from root, this is equal to root element.
	 * @return LinkSelector or null if empty
	 */
	public function getFirstLinkSelector();
	
	/**
	 * Returns the last link selector of the list.
	 * In the case of sub element path from root, this is equal to the sub element parent.
	 * @return LinkSelector or null if empty
	 */
	public function getLastLinkSelector();
}