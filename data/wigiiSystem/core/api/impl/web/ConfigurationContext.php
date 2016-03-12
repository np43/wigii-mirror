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
 * The ConfigurationContext have all base method of the ConfigurationService. Each method without the group option.
 * The ConfigurationContext will be able to choose between a group method or a standard one.
 * Created on 22 Dec 2009 by LWR
 */
interface ConfigurationContext extends ConfigService {

	/**
	 * set the actual selected groups
	 */
	public function setGroupPList($principal, $module, $groupPListWithoutDetail, $includeChildrenGroups = true);

	/**
	 * return the current group id used to get the configuration.
	 * If current count(groupPList)>1 or if !allowGroupDynamicConfig then return 0.
	 */
	public function getCrtConfigGroupId($principal, $exec);

}


