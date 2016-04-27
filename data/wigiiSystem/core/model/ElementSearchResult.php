<?php
/**
 *  This file is part of Wigii.
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
 * Element Search result
 * Created by CWE on 3 sept. 09
 */
class ElementSearchResult extends Model
{
	private $absoluteSearchScore;
	private $relativeSearchScore;
	private $elementP;
	private $groupPListList;

	public static function createInstance($absoluteSearchScore, $relativeSearchScore, $elementP, $groupPListList)
	{
		$returnValue = new ElementSearchResult();
		$returnValue->setAbsoluteSearchScore($absoluteSearchScore);
		$returnValue->setRelativeSearchScore($relativeSearchScore);
		$returnValue->setFoundElement($elementP);
		$returnValue->setElementGroups($groupPListList);
		return $returnValue;
	}
	/**
	 * Absolute score value
	 */
	public function getAbsoluteSearchScore()
	{
		return $this->absoluteSearchScore;
	}
	/**
	 * Absolute score value
	 */
	protected function setAbsoluteSearchScore($score)
	{
		$this->absoluteSearchScore = $score;
	}
	/**
	 * Relative score value, a floating number between 0 and 1
	 */
	public function getRelativeSearchScore()
	{
		return $this->relativeSearchScore;
	}
	/**
	 * Relative score value, a floating number between 0 and 1
	 */
	protected function setRelativeSearchScore($score)
	{
		$this->relativeSearchScore = $score;
	}

	/**
	 * Returns found ElementP
	 */
	public function getFoundElement()
	{
		return $this->elementP;
	}
	protected function setFoundElement($elementP)
	{
		$this->elementP = $elementP;
	}
	/**
	 * Returns all groups containing the found element
	 * Each group is associated to its complete path in the group hierarchy, from leaf to root
	 * returns a GrouPListList
	 */
	public function getElementGroups()
	{
		return $this->groupPListList;
	}
	protected function setElementGroups($groupPListList)
	{
		$this->groupPListList = $groupPListList;
	}
}