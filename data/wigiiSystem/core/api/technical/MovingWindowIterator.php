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
 * Collection iterator based on the moving window concept
 * Created by CWE on 20 juin 09
 */
interface MovingWindowIterator
{
	const BIGGEST_POSSIBLE_BLOCK_SIZE = -1;
	const MOVE_ONE_BLOCK = -2;

	/**
	 * Sets block size in number of items.
	 * This size corresponds to the size of the moving window
	 * Block size defaults to BIGGEST_POSSIBLE_BLOCK_SIZE which means that the implementation decides
	 * of the maximum of items contained in one block, normally should put all available items in one single block.
	 * throws MovingWindowIteratorException in case of error
	 */
	public function setBlockSize($nbOfUnits=MovingWindowIterator::BIGGEST_POSSIBLE_BLOCK_SIZE);

	/**
	 * Returns the block size in number of items
	 * This size corresponds to the size of the moving window
	 * throws MovingWindowIteratorException in case of error
	 */
	public function getBlockSize();

	/**
	 * returns current block as an array of items
	 * throws MovingWindowIteratorException in case of error
	 */
	public function getCurrentBlock();

	/**
	 * returns an item in the current block given its offset
	 * offset starts from 0 to current block size - 1
	 * throws MovingWindowIteratorException in case of error
	 */
	public function getCurrentBlockItem($offset);

	/**
	 * Returns current block size which is smaller or equal to block size
	 * The call to this method can trigger the loading of the entire block to count elements,
	 * to only check if block is not empty, call the currentBlockContainsItems method
	 * throws MovingWindowIteratorException in case of error
	 */
	public function getCurrentBlockSize();

	/**
	 * Returns true if current block is not empty, else false
	 * This method is fast, does trigger the load of maximum one item.
	 */
	public function currentBlockContainsItems();

	/**
	 * returns a MovingWindowIterator on the current block
	 * default sub-block size equals 1
	 * throws MovingWindowIteratorException in case of error
	 */
	public function getCurrentBlockIterator($subBlockSize = 1);

	 /**
	  * Moves current block forward of a given number of units
	  * Number of units defaults to one block size, so that block pagination is straighforward
	  * throws MovingWindowIteratorException in case of error
	  * return false if did not move because end was reached or nbOfUnits is 0.
	  */
	 public function moveForward($nbOfUnits = MovingWindowIterator::MOVE_ONE_BLOCK);
}
?>
