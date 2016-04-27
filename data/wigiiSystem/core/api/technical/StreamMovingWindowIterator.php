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
 * Moving Window Iterator based on a stream of items
 * Created by CWE on 20 juin 09
 */
abstract class StreamMovingWindowIterator implements MovingWindowIterator
{
	private $_debugLogger;
	/**
	 * block buffer, always indexed from 0
	 */
	private $buffer;
	private $currentStreamIndex;
	private $currentBlockIndex;
	private $blockSize;
	private $reachedEndOfStream;

	const ALL_ITEMS = -4;


	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("StreamMovingWindowIterator");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		$this->currentStreamIndex = 0;
		$this->currentBlockIndex = 0;
		$this->reachedEndOfStream = false;
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
		else
		{
			return $this->blockSize;
		}
	}
	private function blockIsWholeStream($blockSize)
	{
		return ($blockSize == MovingWindowIterator::BIGGEST_POSSIBLE_BLOCK_SIZE);
	}

	public function getCurrentBlock()
	{
		$blockSize = $this->getBlockSize();
		if($blockSize == 0) return null;
		if($this->blockIsWholeStream($blockSize))
		{
			$this->loadWholeStream();
		}
		else
		{
			$this->loadFromStreamUntilIndex($this->currentBlockIndex + $blockSize - 1);
		}
		// returns buffer
		return $this->getBuffer();
	}
	public function getCurrentBlockItem($offset)
	{
		if($offset < 0)
		{
			throw new MovingWindowIteratorException('offset cannot be negative',MovingWindowIteratorException::BLOCK_INDEX_OUT_OF_RANGE);
		}
		$blockSize = $this->getBlockSize();
		if($blockSize == 0) return null;
		if($this->blockIsWholeStream($blockSize))
		{
			$this->loadWholeStream();
			if($offset > $this->getBufferSize() - 1)
			{
				throw new MovingWindowIteratorException('offset must be between 0 and current block size '.$this->getBufferSize(),MovingWindowIteratorException::BLOCK_INDEX_OUT_OF_RANGE);
			}
		}
		else
		{
			$validOffset = min($offset, $blockSize - 1);
			if(!$this->loadFromStreamUntilIndex($this->currentBlockIndex + $validOffset) ||
			   $validOffset < $offset)
			{
				throw new MovingWindowIteratorException('offset must be between 0 and current block size '.$this->getBufferSize(),MovingWindowIteratorException::BLOCK_INDEX_OUT_OF_RANGE);
			}
		}
		// returns buffer[offset]
		return $this->getItemInBuffer($offset);
	}
	public function getCurrentBlockSize()
	{
		$blockSize = $this->getBlockSize();
		if($blockSize == 0) return 0;
		if($this->blockIsWholeStream($blockSize))
		{
			$this->loadWholeStream();
		}
		else
		{
			$this->loadFromStreamUntilIndex($this->currentBlockIndex + $blockSize - 1);
		}
		// returns buffer size
		return $this->getBufferSize();
	}
	public function currentBlockContainsItems()
	{
		if($this->getBlockSize() == 0) return false;
		$this->loadFromStreamUntilIndex($this->currentBlockIndex);
		// returns (buffer size > 0)
		return ($this->getBufferSize() > 0);
	}
	public function getCurrentBlockIterator($subBlockSize = 1)
	{
		if(0 <= $subBlockSize && $subBlockSize <= $this->getBlockSize())
		{
			$returnValue = $this->createArrayMovingWindowIteratorForCurrentBlock();
			$returnValue->setArray($this->getCurrentBlock());
			$returnValue->setBlockSize($subBlockSize);
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
		if($nbOfUnits != MovingWindowIterator::MOVE_ONE_BLOCK &&
		   $nbOfUnits < 0)
		{
			throw new MovingWindowIteratorException('nbOfUnits is invalid, it can only be MOVE_ONE_BLOCK or positive',MovingWindowIteratorException::INVALID_ARGUMENT);
		}
		if($nbOfUnits == 0) return false;
		$blockSize = $this->getBlockSize();
		if($blockSize == 0) return false;
		if($this->blockIsWholeStream($blockSize))
		{
			// whole stream in one block, so move one block empties the buffer, do not move
			if($nbOfUnits == MovingWindowIterator::MOVE_ONE_BLOCK)
			{
				return false;
			}
			else
			{
				$this->loadWholeStream();
				// if shifts whole stream then do not move
				if($nbOfUnits >= $this->getBufferSize())
				{
					return false;
				}
				// discards out of range items
				else
				{
					$this->discardBufferItems($nbOfUnits);
				}
			}
		}
		else
		{
			// if shift is bigger than a block, then current block is discarded and recreated
			if($nbOfUnits >= $blockSize ||
			   $nbOfUnits == MovingWindowIterator::MOVE_ONE_BLOCK)
			{
				if($nbOfUnits == MovingWindowIterator::MOVE_ONE_BLOCK) $nbOfUnits = $blockSize;
				if(!$this->loadFromStreamUntilIndex($this->currentBlockIndex + $nbOfUnits))
				{
					return false;
				}
			}
			// else if shift is inside a block
			else
			{
				// if not already loaded, first loads missing elements in block
				if($this->currentBlockIndex + $nbOfUnits >= $this->currentStreamIndex)
				{
					if(!$this->loadFromStreamUntilIndex($this->currentBlockIndex + $nbOfUnits))
					{
						return false;
					}
				}
				// discards nbOfUnits out of range elements
				$this->discardBufferItems($nbOfUnits);
			}
		}
		return true;
	}


	// stream management

	/**
	 * reads and returns next item in stream
	 * this method can block until item is available in stream
	 * must return false if no more items are available.
	 */
	protected abstract function readNextItemFromStream();
	private function doReadNextItemFromStream()
	{
		if(!$this->reachedEndOfStream)
		{
			$result = $this->readNextItemFromStream();
			if(!$result)
			{
				$this->reachedEndOfStream = true;
				return false;
			}
			else return $result;
		}
		else return false;
	}
	/**
	 * Skips next number of items in stream
	 * this method can block until item is available in stream
	 * must return false if end of stream is reached before skipping all items, else returns true
	 * this implementation loops on readNextItemFromStream, but if stream implementation
	 * supports block skipping then override this method with the appropriate calls for optimization.
	 */
	protected function skipNextItemsFromStream($nbOfItems)
	{
		for($i = 0; $i < $nbOfItems; $i++)
		{
			if($this->readNextItemFromStream() == false) return false;
		}
		return true;
	}
	private function doSkipNextItemsFromStream($nbOfItems)
	{
		if(!$this->reachedEndOfStream)
		{
			$result = $this->skipNextItemsFromStream($nbOfItems);
			if(!$result)
			{
				$this->reachedEndOfStream = true;
				return false;
			}
			else return $result;
		}
		else return false;
	}
	/**
	 * Returns true if underlying stream supports reading block of items instead of reading items one by one.
	 * This implementation always returns false, but if your underlying stream supports this feature,
	 * just override this method and the iterator will automatically use it.
	 * If you do this, do not forget to override the readNextItemsFromStream method with the appropriate calls to you stream API
	 */
	protected function streamSupportsBlockReading()
	{
		return false;
	}
	/**
	 * Reads next number of items in stream in one shot and returns an array of items
	 * this method can block until item is available in stream
	 * must return false if no more items are available.
	 * the size of the array is smaller or equal to the number of items to read
	 * defaults to ALL_ITEMS which means that the stream returns an array with all items.
	 * this implementation does not support this feature, but if your underlying stream supports this,
	 * then just override this method with the appropriate calls to your stream API and the iterator will use it.
	 * If you do this, do not forget to override streamSupportsBlockReading to return true.
	 */
	protected function readNextItemsFromStream($nbOfItems=self::ALL_ITEMS)
	{
		throw new MovingWindowIteratorException('this implementation does not support reading blocks of item, implement a subclass',MovingWindowIteratorException::UNSUPPORTED_OPERATION);
	}
	private function doReadNextItemsFromStream($nbOfItems=self::ALL_ITEMS)
	{
		if(!$this->reachedEndOfStream)
		{
			$result = $this->readNextItemsFromStream($nbOfItems);
			if(!$result)
			{
				$this->reachedEndOfStream = true;
				return false;
			}
			else return $result;
		}
		else return false;
	}


	// stream loading

	private function loadWholeStream()
	{
		// if streams supports bulk read then uses the feature
		if($this->streamSupportsBlockReading())
		{
			$a = $this->doReadNextItemsFromStream();
			if($a != false)
			{
				if(!is_null($a))
				{
					$nItemsRead = count($a);
					foreach($a as $item)
					{
						// stores item
						$this->storeItemInBuffer($item);
					}
				}
				else
				{
					$nItemsRead = 0;
				}
				$this->currentStreamIndex = $this->currentStreamIndex + $nItemsRead;
			}
			// end of stream reached when trying to read element
			else
			{
				return false;
			}
		}
		// else reads items one by one
		else
		{
			$item = $this->doReadNextItemFromStream();
			// end of stream reached when trying to read element
			if($item == false)
			{
				return false;
			}
			// stores item
			$this->storeItemInBuffer($item);
			$this->currentStreamIndex++;
			while($item != false)
			{
				$item = $this->doReadNextItemFromStream();
				if($item != false)
				{
					// stores item
					$this->storeItemInBuffer($item);
					$this->currentStreamIndex++;
				}
			}
		}
		return true;
	}

	/**
	 * returns false if end of stream is reached before loading everything needed
	 */
	private function loadFromStreamUntilIndex($indexToReach)
	{
		if($this->currentStreamIndex <= $indexToReach)
		{
			$blockSize = $this->getBlockSize();
			// if we need to skip elements
			if($indexToReach >= $this->currentBlockIndex + $blockSize)
			{
				$nBlockToSkip = ($blockSize > 0 ? ($indexToReach - $this->currentBlockIndex) / $blockSize : 0);
				//$this->debugLogger()->write('number of blocks to skip '.$nBlockToSkip);
				$nItemsToSkip = $nBlockToSkip * $blockSize - ($this->currentStreamIndex - $this->currentBlockIndex);
				$this->debugLogger()->write('number of items to skip '.$nItemsToSkip);
				if($this->doSkipNextItemsFromStream($nItemsToSkip) != false)
				{
					$this->currentStreamIndex = $this->currentStreamIndex + $nItemsToSkip;
					$this->currentBlockIndex  = $this->currentBlockIndex + $nBlockToSkip * $blockSize;
					// if number of blocks to skip >= 1 then discards buffer
					if($nBlockToSkip >= 1)
					{
						$this->discardBuffer();
					}
				}
				// end of stream reached before end of skipping, returns false
				else
				{
					$this->currentStreamIndex = $this->currentStreamIndex + $nItemsToSkip;
					//$this->debugLogger()->write('current stream index '.$this->currentStreamIndex);
					return false;
				}
			}
			//then reads missing elements
			if($this->currentStreamIndex <= $indexToReach)
			{
				// if streams supports bulk read then uses the feature
				if($this->streamSupportsBlockReading())
				{
					$nItemsToRead = $indexToReach - $this->currentStreamIndex + 1;
					$this->debugLogger()->write('number of items to read '.$nItemsToRead);
					$a = $this->doReadNextItemsFromStream($nItemsToRead);
					if($a != false)
					{
						if(!is_null($a))
						{
							$nItemsRead = count($a);
							foreach($a as $item)
							{
								// stores item
								$this->storeItemInBuffer($item);
							}
						}
						else
						{
							$nItemsRead = 0;
						}
						$this->currentStreamIndex = $this->currentStreamIndex + $nItemsRead;
					}
					// end of stream reached when trying to read element
					else
					{
						return false;
					}
				}
				// else reads items one by one
				else
				{
					while($this->currentStreamIndex <= $indexToReach)
					{
						$item = $this->doReadNextItemFromStream();
						if($item != false)
						{
							// stores item
							$this->storeItemInBuffer($item);
							$this->currentStreamIndex++;
						}
						// end of stream reached when trying to read element
						else
						{
							return false;
						}
					}
				}
			}
		}
		return true;
	}

	// internal buffer management

	private function getBufferSize()
	{
		if(isset($this->buffer))
		{
			return count($this->buffer);
		}
		else
		{
			return 0;
		}
	}
	private function getBuffer()
	{
		return $this->buffer;
	}
	private function getItemInBuffer($offset)
	{
		return $this->buffer[$offset];
	}
	private function discardBuffer()
	{
		unset($this->buffer);
	}
	/**
	 * removes first n elements from buffer, buffer is reindexed from 0
	 * updates current block index
	 */
	private function discardBufferItems($nbOfItems)
	{
		if(isset($this->buffer))
		{
			if($nbOfItems > 0 && $nbOfItems < count($this->buffer))
			{
				$this->currentBlockIndex = $this->currentBlockIndex + $nbOfItems;
				$this->buffer = array_slice($this->buffer, $nbOfItems);
			}
		}
	}
	private function storeItemInBuffer($item)
	{
		$this->buffer[] = $item;
	}
}
?>
