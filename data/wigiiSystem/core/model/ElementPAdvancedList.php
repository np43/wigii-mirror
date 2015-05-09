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
 * An advanced list of ElementP
 * Created by LWR on 23 march 10
 */
interface ElementPAdvancedList extends ElementPList, DbEntityList
{
	/**
	 * return true if since the last reset every elementP added has
	 * at least read right
	 */
	public function allHaveReadRights();
	/**
	 * return true if since the last reset every elementP added has
	 * at least write right
	 */
	public function allHaveWriteRights();

	/**
	 * return true if since the last reset every elementP added has
	 * the attribute specified in the reset at true
	 */
	public function allHaveSpecificAttribut();
	/**
	 * Returns true if since the last reset at least one elementP added has
	 * the attribute specified in the reset at true
	 */
	public function atLeastOneHasSpecificAttribut();
	/**
	 * return the FieldSelector of the specific attribute we wanted to check
	 * can be null
	 */
	public function getSpecificAttributFieldSelector();

	/**
	 * return an array with the ids of the element list
	 */
	public function getElementIds();

	/**
	 * return an array with the ids of the elements that this user has last modified
	 */
	public function getSysUserElementPList($userId);
	public function getNoneSysUserElementPList($userId);

	/**
	 * return an array with the ids of the elements that this user has created
	 */
	public function getSysCreationUserElementPList($userId);
	public function getNoneSysCreationUserElementPList($userId);
	
	/**
	 * return the fieldSelectorList associated to the ElementPList
	 * can be null
	 */
	public function getFieldSelectorList();

	/**
	 * return the specific ElementP of the list
	 */
	public function getElementP($elementId);

}