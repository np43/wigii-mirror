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
 * DataType instance to put specific stuff if needed.
 */
class DataTypeInstance extends DataType
{
	
	/**
	 * Generic factory for a specific datatypes using introspection.
	 */
	public static function createInstance($dataTypeName)
	{
		if(!isset($dataTypeName)) throw new ServiceException("cannot create an instance with a null class name", ServiceException::INVALID_ARGUMENT);
		$returnValue = ServiceProvider::createWigiiObject($dataTypeName);
		$returnValue->setDataTypeName($dataTypeName);
		return $returnValue;
	}

	/**
	 * cette méthode contrôle les données du type de donnée. Ce contrôle ne se fait pas
	 * automatiquement, si le type de donnée évolue, il faut aussi modifier cette méthode
	 */
	public function checkValues($p, $elementId, $wigiiBag, $field)
	{
		/* ok nothing to do. */
	}
}