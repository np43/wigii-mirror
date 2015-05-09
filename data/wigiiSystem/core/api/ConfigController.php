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
 * A configuration controller.
 * Instances of this type are intended to interact with Wigii configuration files and modify them on the fly.
 * The life cycle of theses objects are managed by the ConfigServiceImpl.
 * Created by CWE on 27 february 2014
 */
interface ConfigController extends WigiiExclusiveAccessObject
{	
	/**
	 * Processes the given xml configuration node and updates it if needed.
	 * @param Principal $principal the principal executing the action
	 * @param SimpleXMLElement $xmlConfig a SimpleXMLElement instance containing the xml configuration node, 
	 * to be used in <b>read only</b> mode, for analysis purpose. 
	 * To get a writable node, invoke the 'getWritableNode' method given in the arguments.
	 * @param CallableObject $getWritableNode a Wigii callable object which can be invoked to get a writable copy of the xml configuration node.
	 * The getWritableNode callable object returns an instance of a SimpleXMLElement which is a clone of the readable xmlConfig node.
	 * Usage is : $writableXml = $getWritableNode->invoke(); then do some stuff on the $writableXml instance.
	 * The xmlConfig instance remains untouched.
	 * @param Array $lp the lookup path of the xml config as an array of strings with the keys [moduleName, clientName, wigiiNamespaceName, groupName, username, activityName].
	 * Warning: $lp can be null, if not lookup path information is available for the given xml node. For example when processing inner fields.
	 * @return boolean returns true if node has been updated, else false.
	 */
	public function processConfigurationNode($principal, $xmlConfig, $getWritableNode, $lp);
}