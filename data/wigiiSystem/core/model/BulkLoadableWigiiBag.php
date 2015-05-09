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
 * A wigii bag which can accept injection of values in bulk
 * Created by CWE on 27 mai 2014
 */
interface BulkLoadableWigiiBag extends WigiiBag
{
	/**
	 * Injects a wigii fixed bag into this wigii bag.
	 * @param WigiiFixedBag $wigiiFixedBag the fixed bag containing the data
	 * @param Int|Array $elementIds the element id or the array of element ids (array(elementId=>elementId)) contained in the fixed bag.
	 * @throws RecordException can throw a RecordException::INVALID_STATE if the wigii bag currently 
	 * doesn't accept the injection of a wigii fixed bag, in that case, the caller should use instead
	 * the standard one by one method WigiiBag::setValue.
	 * To prevent the throw of the exception, you can call the acceptsFixedBag method to check if the wigii bag
	 * is currently accepting the injection of a fixed bag.
	 */
	public function setFixedBag($wigiiFixedBag, $elementIds);
	
	/**
	 * Returns true if this wigii bag currently accepts the injection of a wigii fixed bag, else returns false.
	 */
	public function acceptsFixedBag();
}