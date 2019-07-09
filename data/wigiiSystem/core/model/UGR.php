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

/* A wigii User Group Right
 * Created by CWE on 31 mai 09
 */
class UGR extends DbEntityInstance
{
	private $userId; // user id
	private $groupId; // group id
	private $canModify;
	private $canWriteElement;
	private $canShareElement;

	public static function createInstance($groupId=MANDATORY_ARG, $userId=MANDATORY_ARG, $array=null, $colPrefix='')
	{
		$ugr = new UGR();
		$ugr->setGroupId($groupId);
		$ugr->setUserId($userId);
		$ugr->setCanModify(false);
		$ugr->setCanWriteElement(false);
		$ugr->setCanShareElement(false);
		if(is_array($array))
		{
			if(isset($array[$colPrefix.'canModify'])) $ugr->setCanModify($array[$colPrefix.'canModify']);
			if(isset($array[$colPrefix.'canWriteElement'])) $ugr->setCanWriteElement($array[$colPrefix.'canWriteElement']);
			if(isset($array[$colPrefix.'canShareElement'])) $ugr->setCanShareElement($array[$colPrefix.'canShareElement']);
		}
		return $ugr;
	}

	public function getGroupId()
	{
		return $this->groupId;
	}
	protected function setGroupId($groupId)
	{
		$this->groupId = $groupId;
	}
	public function getUserId()
	{
		return $this->userId;
	}
	protected function setUserId($userId)
	{
		$this->userId = $userId;
	}

	public function canModify()
	{
		return $this->canModify;
	}
	public function setCanModify($canModify)
	{
		$this->canModify = $this->formatBoolean($canModify);
	}
	public function canWriteElement()
	{
		return $this->canWriteElement;
	}
	public function setCanWriteElement($canWriteElement)
	{
		$this->canWriteElement = $this->formatBoolean($canWriteElement);
	}
	public function canShareElement()
	{
		return $this->canShareElement;
	}
	public function setCanShareElement($canShareElement)
	{
		$this->canShareElement = $this->formatBoolean($canShareElement);
	}
	
	public function getLetter(){
		if($this->canModify) return "x";
		if($this->canWriteElement) return "w";
		if($this->canShareElement) return "s";
		return "r";
	}
	
	/**
	 * set rights from a r/s/w/x letter. 
	 * the hierachical logic is implemented. (x means you have all the others)
	 * @return $this to allow easy inline setting
	 */
	public function setRightsFromLetter($letter){
		$letter = strtolower($letter);
		switch($letter){
			case "":
			case null:
			case "none":
				throw new ServiceException("ugr cannot be setted with none, because an existing ugr means read right", ServiceException::INVALID_ARGUMENT);
				break;
			case "r":
				$this->setCanModify(false);
				$this->setCanWriteElement(false);
				$this->setCanShareElement(false);
				break;
			case "s":
				$this->setCanModify(false);
				$this->setCanWriteElement(false);
				$this->setCanShareElement(true);
				break;
			case "w":
				$this->setCanModify(false);
				$this->setCanWriteElement(true);
				$this->setCanShareElement(true);
				break;
			case "x":
				$this->setCanModify(true);
				$this->setCanWriteElement(true);
				$this->setCanShareElement(true);
				break;
			
		}
		return $this;
	}
}


