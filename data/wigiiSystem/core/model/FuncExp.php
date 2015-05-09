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
 * Function Expression
 * Created by CWE on 13 avr. 10
 */
class FuncExp extends Model
{
	private $arguments;
	private $name;

	public static function createInstance($name, $arguments=null)
	{
		$returnValue = new self();
		$returnValue->setName($name);
		$returnValue->setArguments($arguments);
		return $returnValue;
	}

	public function setName($name)
	{
		if(is_null($name)) throw new ServiceException("name cannot be null", ServiceException::INVALID_ARGUMENT);
		$this->name = $name;
	}
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Adds an argument to the function
	 * arg can be a FuncExp, an object, a FieldSelector or a literal (string, array, number, etc)
	 */
	public function addArgument($arg)
	{
		$this->arguments[] = $arg;
	}
	public function getArguments()
	{
		return $this->arguments;
	}
	public function setArguments($arguments)
	{
		if(isset($arguments))
		{
			if(!is_array($arguments)) throw new ServiceException("arguments should be an array", ServiceException::INVALID_ARGUMENT);
			$this->arguments = $arguments;
		}
		else $this->arguments = array();
	}

	/**
	 * Fills a fieldSelectorList with all funcExp dependencies
	 * i.e. the used fieldSelectors inside the funcExp
	 */
	public function getDependencies($fieldSelectorList)
	{
		if(is_null($fieldSelectorList)) throw new ServiceException("fieldSelectorList cannot be null", ServiceException::INVALID_ARGUMENT);
		foreach($this->getArguments() as $arg)
		{
			if($arg instanceof FieldSelector)
			{
				try
				{
					//add the fieldSelector only if the general one is not defined
					if(!$fieldSelectorList->containsFieldSelector($arg->getFieldName(), null)){
						$fieldSelectorList->addFieldSelectorInstance($arg);
					}
				}
				catch(ListException $le) {if($le->getCode() != ListException::ALREADY_EXISTS) throw $le;}
			}
			elseif($arg instanceof FuncExp)
			{
				$arg->getDependencies($fieldSelectorList);
			}
		}
	}

	// Evaluator

	public function evaluate($evaluator)
	{
		if(is_null($evaluator)) throw new ServiceException("evaluator cannot be null", ServiceException::INVALID_ARGUMENT);
		$fName = $this->getName();
		if($evaluator instanceof FuncExpEvaluator) return $evaluator->evaluateFuncExp($this);
		elseif(method_exists($evaluator, $fName)) return $evaluator->$fName($this->getArguments());
		else throw new ServiceException("evaluator can only be an instance of FuncExpEvaluator or implement a method called $fName", ServiceException::INVALID_ARGUMENT);
	}
}