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
 * FieldSelectorFuncExpParser tests
 * Created by CWE on 13 avr. 10
 */
class Test_FieldSelectorFuncExpParser extends WigiiApiTest
{
	private $d;
	private $variables;

	public function __construct()
	{
		parent::__construct('Test_FieldSelectorFuncExpParser','FieldSelectorFuncExpParser->createFuncExpFromString');
		$this->d = DebugLogger::getInstance("Test_FieldSelectorFuncExpParser");
	}
	public function run()
	{
		$s = 'CAT(SUM(price.value1, price.value2), " ", currency, " in total")';
		$this->variables = array("price" => array("value1" => 10, "value2" => 12), "currency" => "CHF");
		$fsFuncExpParser = TechnicalServiceProvider::getFieldSelectorFuncExpParser();
		$this->assertIsSet("FieldSelectorFuncExp parser is created", $fsFuncExpParser);

		$this->d->write("will create a FuncExp from '$s'");
		$funcExp = $fsFuncExpParser->createFuncExpFromString($s);
		$this->assertNotNull("FuncExp is created and not null", $funcExp);

		$this->d->write($funcExp->displayDebug());
		$this->d->write("Created FuncExp is: ");
		$this->d->write($fsFuncExpParser->funcExpToString($funcExp));

		$this->d->write("Func exp evaluates to ".$funcExp->evaluate($this));

		$dependencies = FieldSelectorListArrayImpl::createInstance();
		$funcExp->getDependencies($dependencies);
		$this->d->write("FuncExp depends on: ");
		foreach($dependencies->getListIterator() as $fs)
		{
			$this->d->write($fs->toString());
		}
	}

	// Evaluator

	public function CAT($args)
	{
		$returnValue = '';
		foreach($args as $arg)
		{
			if($arg instanceof FieldSelector)
			{
				$returnValue .= $this->dereferenceVariable($arg);
			}
			elseif($arg instanceof FuncExp)
			{
				$returnValue .= $arg->evaluate($this);
			}
			else $returnValue .= $arg;
		}
		return $returnValue;
	}
	public function SUM($args)
	{
		$returnValue = 0;
		foreach($args as $arg)
		{
			if($arg instanceof FieldSelector)
			{
				$returnValue += $this->dereferenceVariable($arg);
			}
			elseif($arg instanceof FuncExp)
			{
				$returnValue += $arg->evaluate($this);
			}
			else $returnValue += $arg;
		}
		return $returnValue;
	}
	protected function dereferenceVariable($fieldSelector)
	{
		$returnValue = $this->variables[$fieldSelector->getFieldName()];
		$subfield = $fieldSelector->getSubFieldName();
		if(isset($subfield))
		{
			$returnValue = $returnValue[$subfield];
		}
		return $returnValue;
	}
}
TestRunner::test(new Test_FieldSelectorFuncExpParser());

class Test_FuncExp2String extends WigiiApiTest
{
	private $d;
	
	public function __construct()
	{
		parent::__construct('Test_FuncExp2String','FieldSelectorFuncExpParser->funcExpToString');
		$this->d = DebugLogger::getInstance("Test_FuncExp2String");
	}
	public function run()
	{	
		$fsFuncExpParser = TechnicalServiceProvider::getFieldSelectorFuncExpParser();
		$o = (object)array('prop1'=>'property1:"a text"', 'prop2."34"'=>56, 'p3.true' => true, 'p4.false' => false);
		$f = array(1=>array('A'=>'AA', 'B'=>'BB'), 2=>$o);
		$s = $fsFuncExpParser->funcExpToString($f);
		$this->d->write($s);
		$f2 = $fsFuncExpParser->createFuncExpFromString($s);
		$f2 = evalfx($this->getRootPrincipal(), $f2);
		$s2 = $fsFuncExpParser->funcExpToString($f2);
		$this->assertEqual('parsing and toString are equal', $s, $s2);
	}
}
TestRunner::test(new Test_FuncExp2String());