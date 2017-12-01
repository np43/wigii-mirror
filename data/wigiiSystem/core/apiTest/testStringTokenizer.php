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
 * String Tokenizer tests
 * Created by CWE on 11 déc. 09
 */
class Test_BasicStringTokenizer extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_BasicStringTokenizer','various tests arount string tokenizing');
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_BasicStringTokenizer");

		//$s = '+"*%&/()=?`@#|~\'^!${}[]-.:,;<>\\';
		$s = " (camille.weber@a3.epfl.ch) un texte accentué avec des espaces \"une string\" , de la ponctuation et des  \"une autre\"  parenthèses. (début du prochain mot)   {un nombre: 1234.567; négatif=-14}, 2 < 5 <=> 5 >= x2, mais 4df-s1";
		$matches = array();

		$symbols_pattern = '[\\Q°§+"*%&\/()=?`@#|´~\'^!${}[]-.:,;<>\\\\E]';
		$accentChars_pattern = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýýþÿ';
		$wordChars_pattern = "[\\w$accentChars_pattern]";
		$word_pattern = "(?:$wordChars_pattern+)(?=\\s|$symbols_pattern|".'$)';
		if(preg_match_all("/$word_pattern|$symbols_pattern/s", $s, $matches, PREG_SET_ORDER) > 0)
		{
			foreach($matches as $i => $match)
			{
				$d->write("$i {$match[0]}");
			}
		}
		else
		{
			$d->write("no match");
		}

		$token="fieldName1.subFieldName2.subsubFieldName3";
		$fields = array();
		if(preg_match('/^(.*?)(\\.(.*))?$/s',$token,$fields) > 0)
		{
			$d->write("fieldName=".$fields[1]);
			$subfieldName = $fields[3];
			if(isset($subfieldName)) $d->write("subfieldName=".$subfieldName);
		}
		else $this->fail("syntax error");

		$token="fieldName1";
		$fields = array();
		if(preg_match('/^(.*?)(\\.(.*))?$/s',$token,$fields) > 0)
		{
			$d->write("fieldName=".$fields[1]);
			$subfieldName = $fields[3];
			if(isset($subfieldName)) $d->write("subfieldName=".$subfieldName);
		}
		else $this->fail("syntax error");

	}
}
TestRunner::test(new Test_BasicStringTokenizer());

class Test_StringTokenizer extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_StringTokenizer','test StringTokenizer class');
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_StringTokenizer");
		$s = "http://epfl.ch c:\windows l'école \"l' école\" (camille.weber@a3.epfl.ch) un texte accentué avec des espaces \"une string\", de la ponctuation et   \"une autre\" des    parenthèses. (début du prochain mot)   {un nombre: 1234.567; négatif=-14}, 2 < 5 <=> 5 >= x2, mais 4df-s1";

		// first time
		$strtok = TechnicalServiceProvider::getStringTokenizer();
		$this->assertEqual("has found some tokens", $strtok->tokenize($s) > 0, true);
		foreach($strtok->getTokens() as $i => $token)
		{
			$d->write("$i $token");
		}
		TechnicalServiceProvider::recycleStringTokenizer($strtok);

		// second time
		$strtok = TechnicalServiceProvider::getStringTokenizer();
		$this->assertEqual("has found some tokens", $strtok->tokenize($s) > 0, true);
		foreach($strtok->getTokens() as $i => $token)
		{
			$d->write("$i $token");
		}
		TechnicalServiceProvider::recycleStringTokenizer($strtok);

		// change settings
		$strtok = TechnicalServiceProvider::getStringTokenizer();
		$strtok->setWordExtraSymbols('');
		$strtok->setStringLiteralDelimiter("'");
		$this->assertEqual("has found some tokens", $strtok->tokenize("http://epfl.ch toto \"un deux trois\" 'une chaîne' après la chaîne -12 '-12' {}") > 0, true);
		foreach($strtok->getTokens() as $i => $token)
		{
			$d->write("$i $token");
		}
		// restores settings
		$strtok->setWordExtraSymbols(null);
		$strtok->setStringLiteralDelimiter(null);
		TechnicalServiceProvider::recycleStringTokenizer($strtok);
	}
}
TestRunner::test(new Test_StringTokenizer());

class Test_SearchBarToLogExp extends WigiiApiTest
{
	private $d;

	public function __construct()
	{
		parent::__construct('Test_SearchBarToLogExp','test the creation of a LogExp based on a search bar');
	}
	public function run()
	{
		$this->d = DebugLogger::getInstance("Test_SearchBarToLogExp");
		$s = '(Lionel NOT Weber) OR Champ-Soleil "l\' école" l\'école';
		$fsl = FieldSelectorListArrayImpl::createInstance();
		$fsl->addFieldSelector("first_name");
		$fsl->addFieldSelector("last_name");
		$fsl->addFieldSelector("address", "city");

		$exp = $this->createLogExpOnCriteria($fsl, $s);
		$this->assertIsSet("LogExp is created", $exp);
		$this->d->write("\n".$exp->displayDebug());
	}

	/**
	 * Creates a LogExp based on the search criteria string
	 * search criteria keywords are AND OR NOT ET OU NON && || ! ( ) "
	 * quoted string is considered as one token
	 * parenthesis form groups
	 * token separated by spaces are equivalent to token separated by AND
	 */
	protected function createLogExpOnCriteria($fsl, $criteria)
	{
		$returnValue = null;
		$strtok = TechnicalServiceProvider::getStringTokenizer();
		try
		{
			// tokenizes
			$n = $strtok->tokenize($criteria);
			if($n > 0)
			{
				$returnValue = $this->createOrExpOnCriteria($fsl, $strtok);
			}
			else $returnValue = null;
		}
		catch(Exception $e)
		{
			TechnicalServiceProvider::recycleStringTokenizer($strtok);
			throw $e;
		}
		TechnicalServiceProvider::recycleStringTokenizer($strtok);

		return $returnValue;
	}
	private function createOrExpOnCriteria($fsl, $strtok)
	{
		$returnValue = null;
		$firstAndExp = $this->createAndExpOnCriteria($fsl, $strtok);
		if(isset($firstAndExp))
		{
			while($strtok->hasMoreToken() && $this->consumeTokenIfOr($strtok))
			{
				$andExp = $this->createAndExpOnCriteria($fsl, $strtok);
				if(is_null($andExp)) $strtok->raiseSyntaxError("AND expression");
				if(is_null($returnValue))
				{
					$returnValue = LogExp::createOrExp();
					$returnValue->addOperand($firstAndExp);
				}
				$returnValue->addOperand($andExp);
			}
			if(is_null($returnValue)) $returnValue = $firstAndExp;
		}
		// we should have a right parenthesis or be at the end, else syntax error
		if($strtok->hasMoreToken() && $strtok->getCurrentToken() !== ')')
		{
			$strtok->raiseSyntaxError("no more tokens or right parenthesis");
		}
		return $returnValue;
	}
	private function createAndExpOnCriteria($fsl, $strtok)
	{
		$returnValue = null;
		$firstOperand = $this->createOperandOnCriteria($fsl, $strtok);
		if(isset($firstOperand))
		{
			while($strtok->hasMoreToken() && ($this->consumeTokenIfAnd($strtok) || $this->isTokenAnOperand($strtok)))
			{
				$operand = $this->createOperandOnCriteria($fsl, $strtok);
				if(is_null($operand)) $strtok->raiseSyntaxError("token");
				if(is_null($returnValue))
				{
					$returnValue = LogExp::createAndExp();
					$returnValue->addOperand($firstOperand);
				}
				$returnValue->addOperand($operand);
			}
			if(is_null($returnValue)) $returnValue = $firstOperand;
		}
		// we should have an OR, a right parenthesis or be at the end, else syntax error
		if($strtok->hasMoreToken() && $strtok->getCurrentToken() !== ')' && !$this->isTokenAnOr($strtok))
		{
			$strtok->raiseSyntaxError("no more tokens or right parenthesis");
		}
		return $returnValue;
	}
	private function createOperandOnCriteria($fsl, $strtok)
	{
		$returnValue = null;
		if($strtok->hasMoreToken())
		{
			if($this->isTokenAnOperand($strtok))
			{
				// if we have a left par
				if($this->consumeTokenIfLeftPar($strtok))
				{
					$returnValue = $this->createOrExpOnCriteria($fsl, $strtok);
					// nested expression cannot be null
					if(is_null($returnValue)) $strtok->raiseSyntaxError("nested expression");
					// consumes limit that should exists
					if(!$this->consumeTokenIfRightPar($strtok)) $strtok->raiseSyntaxError(')');
				}
				// if we have a not
				elseif($this->consumeTokenIfNot($strtok))
				{
					$notExp = $this->createOperandOnCriteria($fsl, $strtok);
					if(is_null($notExp)) $strtok->raiseSyntaxError("negative operand");
					$returnValue = LogExp::createNotExp($notExp);
				}
				// else we have a word
				else
				{
					// at this stage we don't support ponctuation.
					// string with ponctuation should be in quotes.
					// don't support dates, floating numbers, etc.
					if($strtok->isCurrentTokenASymbol()) $strtok->raiseSyntaxError("no ponctuation");
					$token = $strtok->consumeCurrentToken();
					// removes surrounding quotes
					if(isset($token))
					{
						$token = trim($token, '"');
					}
					$returnValue = $this->createLogExpOnToken($fsl, $token);
				}
			}
			// we should be at the end, else syntax error
			else
			{
				$strtok->raiseSyntaxError("no more tokens");
			}
		}
		return $returnValue;
	}
	private function createLogExpOnToken($fsl, $token)
	{
		if(is_null($fsl)) throw new ServiceException("FieldSelectorList cannot be null", ServiceException::INVALID_ARGUMENT);
		$returnValue = LogExp::createOrExp();
		if(isset($token) && $token !== '')
		{
			$token = "%$token%";
		}
       	foreach($fsl->getListIterator() as $fs)
       	{
			$returnValue->addOperand(LogExp::createLikeExp($fs, $token));
		}
		return $returnValue;
	}

	private function isTokenAnOperand($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "(":
			case "NOT":
			case "NON":
			case "!":
				return true;
		}
		return $this->isTokenAWord($strtok);
	}
	private function isTokenAWord($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "AND":
			case "ET":
			case "OR":
			case "OU":
			case "NOT":
			case "NON":
			case "!":
			case "(":
			case ")":
				return false;
			case "&":
				if($strtok->peek(1) === "&") return false;
			case "|":
				if($strtok->peek(1) === "|") return false;
		}
		return true;
	}

	/**
	 * Returns true if current token is an AND. If true, then consumes it.
	 */
	private function consumeTokenIfAnd($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "AND":
			case "ET":
				$strtok->move(1);
				return true;
			case "&":
				if($strtok->peek(1) === "&")
				{
					$strtok->move(2);
					return true;
				}
		}
		return false;
	}
	/**
	 * Returns true if current token is an OR. If true, then consumes it.
	 */
	private function consumeTokenIfOr($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "OR":
			case "OU":
				$strtok->move(1);
				return true;
			case "|":
				if($strtok->peek(1) === "|")
				{
					$strtok->move(2);
					return true;
				}
		}
		return false;
	}
	private function isTokenAnOr($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "OR":
			case "OU":
				return true;
			case "|":
				if($strtok->peek(1) === "|") return true;
		}
		return false;
	}
	/**
	 * Returns true if current token is an NOT. If true, then consumes it.
	 */
	private function consumeTokenIfNot($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "NOT":
			case "NON":
			case "!":
				$strtok->move(1);
				return true;
		}
		return false;
	}
	private function isTokenANot($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "NOT":
			case "NON":
			case "!":
				return true;
		}
		return false;
	}
	/**
	 * Returns true if current token is a left parenthesis. If true, then consumes it.
	 */
	private function consumeTokenIfLeftPar($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "(":
				$strtok->move(1);
				return true;
		}
		return false;
	}
	/**
	 * Returns true if current token is a right parenthesis. If true, then consumes it.
	 */
	private function consumeTokenIfRightPar($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case ")":
				$strtok->move(1);
				return true;
		}
		return false;
	}
}
TestRunner::test(new Test_SearchBarToLogExp());