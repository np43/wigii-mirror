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
 * MovingWindowIterator on an array
 * Created by CWE on 20 juin 09
 */
class ArrayMovingWindowIterator implements MovingWindowIterator
{
	private $blockSize;
	private $internalArray;
	private $currentBlockIndex;
	private $upperBound;

	const NO_UPPER_BOUND = -3;

	/**
	 * Array should be a numeric indexed array starting with 0
	 */
	public function setArray($array)
	{
		if(isset($this->internalArray))
		{
			unset($this->internalArray);
		}
		$this->internalArray = $array;
		$this->setCurrentBlockIndex(0);
	}
	protected function getArray()
	{
		return $this->internalArray;
	}
	protected function getArraySize()
	{
		if(!isset($this->internalArray))
		{
			return 0;
		}
		else
		{
			return count($this->internalArray);
		}
	}
	/**
	 * Sets an artificial upper bound index in the array which should not be passed over
	 */
	public function setUpperBound($upperBoundIndex = self::NO_UPPER_BOUND)
	{
		if($upperBoundIndex == self::NO_UPPER_BOUND || $upperBoundIndex >= 0)
		{
			$this->upperBound = $upperBoundIndex;
		}
		else
		{
			throw new MovingWindowIteratorException('upperBoundIndex must be bigger or equal to 0 or equal to NO_UPPER_BOUND'.$this->getArraySize(),MovingWindowIteratorException::INVALID_ARGUMENT);
		}
	}
	protected function getUpperBound()
	{
		if(!isset($this->upperBound))
		{
			return self::NO_UPPER_BOUND;
		}
		else
		{
			return $this->upperBound;
		}
	}
	protected function getCurrentBlockIndex()
	{
		if(!isset($this->currentBlockIndex))
		{
			return 0;
		}
		else
		{
			return $this->currentBlockIndex;
		}
	}
	protected function setCurrentBlockIndex($currentIndex)
	{
		$this->currentBlockIndex = $currentIndex;
	}

	// iterator implementation

	public function setBlockSize($nbOfUnits=MovingWindowIterator::BIGGEST_POSSIBLE_BLOCK_SIZE)
	{
		if($nbOfUnits == MovingWindowIterator::BIGGEST_POSSIBLE_BLOCK_SIZE ||
		   $nbOfUnits >= 0)
		{
			$this->blockSize = $nbOfUnits;
		}
		else
		{
			throw new MovingWindowIteratorException('',MovingWindowIteratorException::INVALID_ARGUMENT);
		}
	}
	public function getBlockSize()
	{
		if(!isset($this->blockSize))
		{
			return 0;
		}
		elseif($this->blockSize == MovingWindowIterator::BIGGEST_POSSIBLE_BLOCK_SIZE)
		{
			return $this->getArraySize();
		}
		else
		{
			return $this->blockSize;
		}
	}
	public function getCurrentBlock()
	{
		$returnValue = null;
		$a = $this->getArray();
		if(! is_null($a))
		{
			$n = $this->getCurrentBlockSize();
			// if current block size equals array size then returns directly the array
			if($n == $this->getArraySize())
			{
				//DebugLogger::getInstance("ArrayMovingWindowIterator")->write('block size equals array size so we optimize');
				$returnValue = $a;
			}
			// else does a slice
			elseif($n > 0)
			{
				$returnValue = array_slice($a, $this->getCurrentBlockIndex(), $n);
			}
		}
		return $returnValue;
	}
	public function getCurrentBlockItem($offset)
	{
		if(0 <= $offset && $offset < $this->getCurrentBlockSize())
		{
			$returnValue = null;
			$a = $this->getArray();
			if(! is_null($a))
			{
				$returnValue = $a[$this->getArrayIndex($offset)];
			}
			else
			{
				$returnValue = null;
			}
			return $returnValue;
		}
		else
		{
			throw new MovingWindowIteratorException('offset must be between 0 and current block size '.$this->getCurrentBlockSize(),MovingWindowIteratorException::BLOCK_INDEX_OUT_OF_RANGE);
		}
	}
	public function getCurrentBlockSize()
	{
		return min($this->getBlockSize(), $this->countRemainingElements());
	}
	public function currentBlockContainsItems()
	{
		return ($this->getCurrentBlockSize() > 0);
	}
	public function getCurrentBlockIterator($subBlockSize = 1)
	{
		if(0 <= $subBlockSize && $subBlockSize <= $this->getBlockSize())
		{
			$returnValue = $this->createArrayMovingWindowIteratorForCurrentBlock();
			$returnValue->setArray($this->getArray());
			$returnValue->setUpperBound($this->getCurrentBlockLastIndex());
			$returnValue->setBlockSize($subBlockSize);
			$returnValue->moveForward($this->getCurrentBlockIndex());
			return $returnValue;
		}
		else
		{
			throw new MovingWindowIteratorException('subBlockSize must be between 0 and block size '.$this->getBlockSize(),MovingWindowIteratorException::INVALID_ARGUMENT);
		}
	}
	protected function createArrayMovingWindowIteratorForCurrentBlock()
	{
		return new ArrayMovingWindowIterator();
	}
	public function moveForward($nbOfUnits = MovingWindowIterator::MOVE_ONE_BLOCK)
	{
		if($nbOfUnits == MovingWindowIterator::MOVE_ONE_BLOCK ||
		   $nbOfUnits >= 0)
		{
			// if block size is null then no move possible.
			if($this->getBlockSize() == 0) return false;
			if($nbOfUnits == MovingWindowIterator::MOVE_ONE_BLOCK)
			{
				$nbOfUnits = $this->getBlockSize();
			}
			// if end is reached then returns false
			if($this->getCurrentBlockIndex() + $nbOfUnits > $this->getLastReachableIndex())
			{
				return false;
			}
			// else moves forward
			else
			{
				$this->setCurrentBlockIndex($this->getCurrentBlockIndex() + $nbOfUnits);
				return true;
			}
		}
		else
		{
			throw new MovingWindowIteratorException('nbOfUnits is invalid, it can only be MOVE_ONE_BLOCK or positive',MovingWindowIteratorException::INVALID_ARGUMENT);
		}
	}

	private function getArrayIndex($offset)
	{
		return min($this->getCurrentBlockIndex() + $offset, $this->getLastReachableIndex());
	}
	private function getLastReachableIndex()
	{
		$ub = $this->getUpperBound();
		// if we have an upper bound then return min between array size - 1 and upper bound
		if($ub != self::NO_UPPER_BOUND)
		{
			return 	max(0, min($this->getArraySize()-1, $ub));
		}
		// else returns array size - 1
		else
		{
			return max(0, $this->getArraySize()-1);
		}
	}
	private function getCurrentBlockLastIndex()
	{
		return max($this->getCurrentBlockIndex(), $this->getArrayIndex($this->getBlockSize()-1));
	}
	/**
	 * this counts current element
	 */
	private function countRemainingElements()
	{
		return $this->getLastReachableIndex() - $this->getCurrentBlockIndex()+1;
	}
}
?>
