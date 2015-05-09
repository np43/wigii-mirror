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
 * This service provides the possibility to store and fetch data in the session
 *
 * Created on 24 juil. 09 by LWR
 * Modified by CWE on April 3rd 2014 to add the possibility to store data into 
 * a particular subspace of the session which is cleared each time the user navigates
 * into Wigii (changes module or namespace). This acts as a navigation cache.
 * Modified by CWE on March 04th 2015 to add the possibility to store data into a shared memory implemented as a database table.
 */
interface SessionAdminService {

	/**
	 * Stores the value in the session.
	 * @param Object $obj the caller instance saving data in the session (normally $this) 
	 * @param String $name a is a logical key under which to store the value.
	 * for example in the ConfigService we store the ConfigFolderPath like this:
	 * ->storeData($this, "ConfigFolderPath", $path); and we get it:
	 * ->getData($this, "ConfigFolderPath");
	 * @param Boolean $clearOnNavigate if true, then the stored data is cleared from the session 
	 * when the user navigates into Wigii (changes module or namespace), else the 
	 * data remains into the session untils it is manually cleared or session ends.
	 * @param Boolean $sharedData if true, then data is stored into the database and shared between all users.
	 * if clearOnNavigate, then sharedData is ignored.
	 */
	public function storeData($obj, $name, $value, $clearOnNavigate=false, $sharedData=false);

	/**
	 * Gets the value stored in the session
	 * @param Object $obj the caller instance which saved the data in the session (normally $this) 
	 * @param String $name a is a logical key under which the value is stored.
	 * @param Boolean $fromSharedData if true, then gives a tip to the SessionAdminService saying that it could be some shared data,
	 * else if false (by default) then the SessionAdminService only looks in the session, ignoring shared memory lookup.
	 */
	public function getData($obj, $name, $fromSharedData=false);

	/**
	 * clear the value stored in the session
	 * pass the same obj and name used to store the value.
	 */
	public function clearData($obj, $name);

	/**
	 * Clears all the pairs (key, value) stored for this object.
	 * @param String|Object $obj obj can be the object which was used to store values, or can be a class name, 
	 * in that case all the values of any instances of this class will be cleared from the session.
	 * @param Boolean $clearSharedData if true, then shared data from this class will be also deleted from the database, else only session data is removed. Defaults to false.
	 */
	public function clearObjData($obj, $clearSharedData=false);
}


