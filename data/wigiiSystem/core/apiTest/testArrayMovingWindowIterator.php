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

/**
 * tests ArrayMovingWindowIterator
 * Created by CWE on 20 juin 09
 */

/**
 * Test base class for configuration
 */
class ArrayMovingWindowIteratorTest extends WigiiApiTest
{
	private $arrayForTest;
	private $blocksForTest;
	private $arrayAsString;
	private $blockSize;

	public function __construct($testId, $testName)
	{
		parent::__construct($testId, $testName);
	}

	protected function getArrayForTest()
	{
		if(!isset($this->arrayForTest))
		{
			$this->arrayForTest = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
		}
		return $this->arrayForTest;
	}

	protected function getBlockSize()
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
	protected function setBlockSize($blockSize)
	{
		$this->blockSize = $blockSize;
	}

	protected function getBlock($index)
	{
		if(!isset($this->blocksForTest))
		{
			$a = $this->getArrayForTest();
			$j = 0; $k = 1; $n = $this->getBlockSize();
			if($n > 0)
			{
				foreach($a as $item)
				{
					$this->blocksForTest[$j] .= $item;
					$k++;
					if($k > $n)
					{
						$k = 1;
						$j++;
					}
				}
			}
			else
			{
				$this->blocksForTest[$j] = '';
			}
		}
		return $this->blocksForTest[$index];
	}

	protected function getWholeArrayAsString()
	{
		if(!isset($this->arrayAsString))
		{
			$this->arrayAsString = $this->blockToString($this->getArrayForTest());
		}
		return $this->arrayAsString;
	}

	protected function blockToString($block)
	{
		$s ='';
		if(!is_null($block))
		{
			foreach($block as $item)
			{
				$s .= $item;
			}
		}
		return $s;
	}
}





class Test_ArrayMovingWindowIterator_getCurrentBlock extends ArrayMovingWindowIteratorTest
{
	public function __construct($blockSize = 6)
	{
		parent::__construct('Test_ArrayMovingWindowIterator_getCurrentBlock_'.$blockSize,'ArrayMovingWindowIterator get current block with block size = '.$blockSize);
		$this->setBlockSize($blockSize);
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_ArrayMovingWindowIterator_getCurrentBlock");
		$i = new ArrayMovingWindowIterator();
		$this->assertNotNull('created ArrayMovingWindowIterator',$i);
		$i->setBlockSize($this->getBlockSize());
		$this->assertEqual('block size is set to '.$this->getBlockSize(), $i->getBlockSize(),$this->getBlockSize());
		$b = $i->getCurrentBlock();
		$this->assertNull('no array set, then getCurrentBlock is null',$b);
		$i->setArray($this->getArrayForTest());
		$j = 0;
		if($this->getBlockSize() > 0) $this->assertEqual('first block is not empty', $i->currentBlockContainsItems(), true);
		$b = $i->getCurrentBlock();
		$this->assertEqual('first block equals '.$this->getBlock($j), $this->blockToString($b), $this->getBlock($j));
		$j++; $guard = count($this->getArrayForTest());
		while($i->moveForward() && $j <= $guard)
		{
			$b = $i->getCurrentBlock();
			$this->assertEqual('next block equals '.$this->getBlock($j), $this->blockToString($b), $this->getBlock($j));
			$j++;
		}
		$this->assertEqual('end of array is reached',$i->moveForward(), false);
		$this->assertEqual('end of array is reached invariance',$i->moveForward(), false);
		$d->write("<br/>----------------------------<br/>");
		$d->write("resets current array");
		$i->setArray($this->getArrayForTest());
		$i->setBlockSize(0);
		$this->assertEqual('block size is set to 0', $i->getBlockSize(),0);
		$this->assertNull('if block is 0 then getCurrentBlock is null', $i->getCurrentBlock());
		try
		{
			$i->setBlockSize(-100);
		}
		catch(MovingWindowIteratorException $mwie)
		{
			switch($mwie->getCode())
			{
				case MovingWindowIteratorException::INVALID_ARGUMENT:
				// catched exception ok.
				break;
				default: $this->fail('MovingWindowIteratorException(INVALID_ARGUMENT) was not thrown with a negative block size');
			}
		}
		$i->setBlockSize();
		$this->assertEqual('set max possible block size', $i->getBlockSize(),count($this->getArrayForTest()));
		$b = $i->getCurrentBlock();
		$this->assertEqual('first block equals '.$this->getWholeArrayAsString(), $this->blockToString($b), $this->getWholeArrayAsString());
		$this->assertEqual('no move forward because max block size is equal to whole array', $i->moveForward(), false);
		$this->assertEqual('end of array is reached invariance',$i->moveForward(), false);
	}
}
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlock($blockSize = 2));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlock($blockSize = 7));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlock($blockSize = 1));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlock($blockSize = 216));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlock($blockSize = 0));









class Test_ArrayMovingWindowIterator_getCurrentBlockItem extends ArrayMovingWindowIteratorTest
{
	private $moveOffset;

	public function __construct($blockSize = 6, $moveOffset = 2)
	{
		parent::__construct('Test_ArrayMovingWindowIterator_getCurrentBlockItem'.$blockSize.'_'.$moveOffset,'ArrayMovingWindowIterator get current block item with block size = '.$blockSize.' and move offset = '.$moveOffset);
		$this->setBlockSize($blockSize);
		$this->moveOffset = $moveOffset;
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_ArrayMovingWindowIterator_getCurrentBlockItem");
		$i = new ArrayMovingWindowIterator();
		$this->assertNotNull('created ArrayMovingWindowIterator',$i);
		$i->setBlockSize($this->getBlockSize());
		$this->assertEqual('block size is set to '.$this->getBlockSize(), $i->getBlockSize(),$this->getBlockSize());
		$i->setArray($this->getArrayForTest());
		if($this->getBlockSize() > 0) $this->assertEqual('first block is not empty', $i->currentBlockContainsItems(), true);
		$a = $this->getArrayForTest();
		$k = 0; $guard = count($a);
		for($j = 0; $j < $i->getCurrentBlockSize(); $j++)
		{
			$item = $a[$this->moveOffset * $k + $j];
			$this->assertEqual('current item is equal to '.$item, $i->getCurrentBlockItem($j), $item);
		}
		$k++;
		$d->write("<br/>----------------------------<br/>");
		while($i->moveForward($this->moveOffset) && $k <= $guard)
		{
			for($j = 0; $j < $i->getCurrentBlockSize(); $j++)
			{
				$item = $a[$this->moveOffset * $k + $j];
				$this->assertEqual('current item is equal to '.$item, $i->getCurrentBlockItem($j), $item);
			}
			$k++;
			$d->write("<br/>----------------------------<br/>");
		}
		$this->assertEqual('end of array is reached',$i->moveForward($this->moveOffset), false);
		$this->assertEqual('end of array is reached invariance',$i->moveForward($this->moveOffset), false);
	}
}
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockItem($blockSize = 2, $moveOffset=4));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockItem($blockSize = 2, $moveOffset=7));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockItem($blockSize = 2, $moveOffset=11));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockItem($blockSize = 7, $moveOffset=4));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockItem($blockSize = 1, $moveOffset=1));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockItem($blockSize = 216, $moveOffset=25));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockItem($blockSize = 216, $moveOffset=26));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockItem($blockSize = 216, $moveOffset=27));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockItem($blockSize = 216, $moveOffset=13));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockItem($blockSize = 0));





class Test_ArrayMovingWindowIterator_getCurrentBlockIterator extends ArrayMovingWindowIteratorTest
{
	private $moveOffset;
	private $subblockSize;

	public function __construct($blockSize = 6, $subblockSize=2)
	{
		parent::__construct('Test_ArrayMovingWindowIterator_getCurrentBlockIterator'.$blockSize.'_'.$subblockSize,'ArrayMovingWindowIterator get current block iterator with block size = '.$blockSize.' and sub block size = '.$subblockSize);
		$this->setBlockSize($blockSize);
		$this->subblockSize = $subblockSize;
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_ArrayMovingWindowIterator_getCurrentBlockIterator");
		$i = new ArrayMovingWindowIterator();
		$this->assertNotNull('created ArrayMovingWindowIterator',$i);
		$i->setBlockSize($this->getBlockSize());
		$this->assertEqual('block size is set to '.$this->getBlockSize(), $i->getBlockSize(),$this->getBlockSize());
		$i->setArray($this->getArrayForTest());
		if($this->getBlockSize() > 0) $this->assertEqual('first block is not empty', $i->currentBlockContainsItems(), true);
		$a = $this->getArrayForTest();
		$k = 0; $guard = count($a);
		// inner iterator
		try
		{
			$innerIt = $i->getCurrentBlockIterator(-10);
		}
		catch(MovingWindowIteratorException $mwie)
		{
			switch($mwie->getCode())
			{
				case MovingWindowIteratorException::INVALID_ARGUMENT:
				// catched exception ok.
				break;
				default: $this->fail('MovingWindowIteratorException(INVALID_ARGUMENT) was not thrown with an internal iterator negative block size');
			}
		}
		try
		{
			$innerIt = $i->getCurrentBlockIterator($this->getBlockSize() + 10);
		}
		catch(MovingWindowIteratorException $mwie)
		{
			switch($mwie->getCode())
			{
				case MovingWindowIteratorException::INVALID_ARGUMENT:
				// catched exception ok.
				break;
				default: $this->fail('MovingWindowIteratorException(INVALID_ARGUMENT) was not thrown with an internal iterator block size bigger than actual block size');
			}
		}
		$innerIt = $i->getCurrentBlockIterator($this->subblockSize);
		$this->assertNotNull('inner iterator is created',$innerIt);
		$l = 0;
		for($j = 0; $j < $innerIt->getCurrentBlockSize(); $j++)
		{
			$item = $a[$i->getBlockSize() * $k + $innerIt->getBlockSize() * $l + $j];
			$this->assertEqual('current item in sub iterator is equal to '.$item, $innerIt->getCurrentBlockItem($j), $item);
		}
		$d->write("<br/>----------------------------<br/>");
		$l++;
		while($innerIt->moveForward() && $l <= $guard)
		{
			for($j = 0; $j < $innerIt->getCurrentBlockSize(); $j++)
			{
				$item = $a[$i->getBlockSize() * $k + $innerIt->getBlockSize() * $l + $j];
				$this->assertEqual('current item in sub iterator is equal to '.$item, $innerIt->getCurrentBlockItem($j), $item);
			}
			$l++;
			$d->write("<br/>----------------------------<br/>");
		}
		$this->assertEqual('end of array is reached',$innerIt->moveForward(), false);
		$this->assertEqual('end of array is reached invariance',$innerIt->moveForward(), false);
		// end inner iterator
		$l = 0;$k++;
		$d->write("<br/>=============================<br/>");
		while($i->moveForward() && $k <= $guard)
		{
			// inner iterator
			$innerIt = $i->getCurrentBlockIterator($this->subblockSize);
			$this->assertNotNull('inner iterator is created',$innerIt);
			$l = 0;
			for($j = 0; $j < $innerIt->getCurrentBlockSize(); $j++)
			{
				$item = $a[$i->getBlockSize() * $k + $innerIt->getBlockSize() * $l + $j];
				$this->assertEqual('current item in sub iterator is equal to '.$item, $innerIt->getCurrentBlockItem($j), $item);
			}
			$d->write("<br/>----------------------------<br/>");
			$l++;
			while($innerIt->moveForward() && $l <= $guard)
			{
				for($j = 0; $j < $innerIt->getCurrentBlockSize(); $j++)
				{
					$item = $a[$i->getBlockSize() * $k + $innerIt->getBlockSize() * $l + $j];
					$this->assertEqual('current item in sub iterator is equal to '.$item, $innerIt->getCurrentBlockItem($j), $item);
				}
				$l++;
				$d->write("<br/>----------------------------<br/>");
			}
			$this->assertEqual('end of array is reached',$innerIt->moveForward(), false);
			$this->assertEqual('end of array is reached invariance',$innerIt->moveForward(), false);
			// end inner iterator
			$l = 0;$k++;
			$d->write("<br/>=============================<br/>");
		}
		$this->assertEqual('end of array is reached',$i->moveForward(), false);
		$this->assertEqual('end of array is reached invariance',$i->moveForward(), false);
	}
}
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockIterator($blockSize = 15, $subblockSize=15));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockIterator($blockSize = 15, $subblockSize=7));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockIterator($blockSize = 25, $subblockSize=23));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockIterator($blockSize = 26, $subblockSize=25));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockIterator($blockSize = 27, $subblockSize=26));
TestRunner::test(new Test_ArrayMovingWindowIterator_getCurrentBlockIterator($blockSize = 270, $subblockSize=28));
?>
