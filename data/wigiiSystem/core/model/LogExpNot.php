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
 * NOT logical expression
 * Created by CWE on 1 sept. 09
 */
class LogExpNot extends LogExp
{
	private $logExp;

	public function setLogExp($logExp)
	{
		$this->logExp = $logExp;
	}
	public function getLogExp()
	{
		return $this->logExp;
	}
	public function acceptLogExpVisitor($logExpVisitor)
	{
		return $logExpVisitor->actOnNotExp($this);
	}

	public function negate($clone=false)
	{
		return $this->logExp->reduceNegation($clone);
	}

	public function reduceNegation($clone=false)
	{
		return $this->logExp->negate($clone);
	}
}