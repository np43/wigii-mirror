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

/* A wigii user activity
 * User activities are for instance :
 * - display list of elements
 * - display detail of element
 * - edit detail of element
 * - public add element
 * - feedback on selected element
 * - feedback to group
 * - email on list of elements
 * - export csv on list of elements
 * - export xml on list of elements
 * Created by CWE on 2 juin 09
 */
class Activity extends Model
{
	private $activityName;

	public static function createInstance($activityName = null)
	{
		$a = new Activity();
		if(isset($activityName)){
			$a->setActivityName($activityName);
		}
		return $a;
	}

	public function getActivityName()
	{
		return $this->activityName;
	}
	public function setActivityName($activityName)
	{
		$this->activityName = $activityName;
	}
}



