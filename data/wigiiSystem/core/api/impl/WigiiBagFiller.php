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
 * Fills a WigiiBag given db rows
 * Created by CWE on 3 nov. 09
 */
class WigiiBagFiller extends ElementPMapper implements RecordStructureFactory
{
	private $_debugLogger;
	private $wigiiBag;
	private $inReset;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiBagFiller");
		}
		return $this->_debugLogger;
	}

	// Object life cycle

	public static function createInstance($principal, $wigiiBag, $pRights = null)
	{
		$returnValue = new WigiiBagFiller();
		$returnValue->reset($principal, $wigiiBag, $pRights);
		return $returnValue;
	}

	public function reset($principal, $wigiiBag, $pRights=null)
	{
		$this->wigiiBag = $wigiiBag;
		$this->inReset=true;
		parent::reset($principal, $this, $pRights);
		$this->inReset=false;
	}

	public function freeMemory()
	{
		parent::freeMemory();
		if(!$this->inReset) unset($this->wigiiBag);
	}

	// RecordStructureFactory implementation

	public function createFieldList() {return null;}
	public function createWigiiBag() {return $this->wigiiBag;}
}