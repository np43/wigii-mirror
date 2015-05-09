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
 * A list of ElementP
 * Created by CWE on 17 août 09
 */
interface ElementPList extends ObjectList, RecordStructureFactory
{
	/**
	 * Adds an element to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same element in the list
	 */
	public function addElementP($elementP);
	
	/**	 
	 * If implemented, then ElementService will call this method,
	 * when executing getSelectedElementsInGroups or getSelectedElements
	 * to inform of the calculated group list.
	 * @param GroupList $groupList the calculated group list
	 * 
	 * public function notifyCalculatedGroupList($groupList);
	 */
}