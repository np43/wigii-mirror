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
 * String tokenizer
 * Created by CWE on 12 déc. 09
 */
class StringTokenizer
{
	private $_debugLogger;
	protected $tokens;
	protected $tokenizer_pattern;
	protected $symbols_pattern;
	protected $word_extra_symbols;
	protected $string_literal_delimiter;
	protected $string_literal_pattern;
	private $numberOfTokens;
	protected $currentPosition;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("StringTokenizer");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	// Object life cycle management

	public static function createInstance()
	{
		$returnValue = new StringTokenizer();
		$returnValue->reset();
		return $returnValue;
	}
	public function reset()
	{
		$this->freeMemory();
		$this->tokens = array();
		$this->numberOfTokens = 0;
		$this->currentPosition = 0;
	}
	public function freeMemory()
	{
		unset($this->tokens);
	}

	// Parametrization

	/**
	 * Defines a set of symbols that should be considered as belonging to a word
	 * If null, then no extra symbols are permittet in a word, only normal word chars
	 */
	public function setWordExtraSymbols($wordExtraSymbols)
	{
		$this->word_extra_symbols = $wordExtraSymbols;
		$this->initializeTokenizerPattern();
	}

	/**
	 * Defines symbol to be used a string literal delimiter.
	 * If null, then uses default quote symbol.
	 */
	public function setStringLiteralDelimiter($stringLiteralDelimiter)
	{
		$this->string_literal_delimiter = $stringLiteralDelimiter;
		$this->initializeTokenizerPattern();
	}

	// Tokenizer Implementation

	/**
	 * Tokenizes the string and set the current position on the first token
	 * returns number of tokens found
	 */
	public function tokenize($s)
	{
		if(is_null($s)) return;
		if(is_null($this->tokenizer_pattern)) $this->initializeTokenizerPattern();
		$n = preg_match_all($this->tokenizer_pattern, $s, $this->tokens, PREG_SET_ORDER);
		$this->currentPosition = 0;
		if($n > 0) $this->numberOfTokens = $n;
		else $this->numberOfTokens = 0;
		return $this->numberOfTokens;
	}
	private function initializeTokenizerPattern()
	{
		$this->symbols_pattern = '[\\Q°§+"*%&\/()=?`@#|´~\'^!${}[]-.:,;<>\\\\E]';
		$symbols_pattern = $this->symbols_pattern;
		$accentChars_pattern = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýýþÿ';

		if(isset($this->word_extra_symbols))
		{
			$wordChars_extraSymbols = $this->word_extra_symbols;
		}
		else $wordChars_extraSymbols = "@%'.:\/-";
		$wordChars_pattern = "[\\w$accentChars_pattern$wordChars_extraSymbols]";
		$word_pattern = "(?:$wordChars_pattern+)(?=\\s|$symbols_pattern|".'$)';

		if(isset($this->string_literal_delimiter)) $stringLiteral_delimiter = $this->string_literal_delimiter;
		else $stringLiteral_delimiter = '"';
		$stringLiteral_pattern="$stringLiteral_delimiter.*?$stringLiteral_delimiter";
		$this->string_literal_pattern = $stringLiteral_pattern;

		$this->tokenizer_pattern = "/$stringLiteral_pattern|$word_pattern|$symbols_pattern/s";
		$this->debugLogger()->write("creating tokenizer pattern ".$this->tokenizer_pattern);
	}

	/**
	 * Returns an array of tokens indexed from 0
	 * a token is or a word or a symbol.
	 * a word is a combination of letters and digits, or only digits.
	 */
	public function getTokens()
	{
		$returnValue = array();
		$n = $this->getNumberOfTokens();
		// flatten tokens
		for($i = 0; $i < $n; $i++)
		{
			$returnValue[$i] = $this->tokens[$i][0];
		}
		return $returnValue;
	}
	/**
	 * Returns the number of tokens parsed
	 */
	public function getNumberOfTokens()
	{
		return $this->numberOfTokens;
	}
	/**
	 * Returns the token at index
	 * throws ListException::OUT_OF_BOUND if index is out of bounds.
	 */
	public function getToken($index)
	{
		$returnValue = $this->tokens[$index];
		if(is_null($returnValue) && !array_key_exists($index, $this->tokens))
			throw new ListException("index $index is out of bound of token list", ListException::OUT_OF_BOUND);
		if(isset($returnValue)) $returnValue = $returnValue[0];
		return $returnValue;
	}

	/**
	 * Returns the token at the current position
	 */
	public function getCurrentToken()
	{
		return $this->getToken($this->currentPosition);
	}
	/**
	 * Returns current token and moves one forward
	 */
	public function consumeCurrentToken()
	{
		$returnValue = $this->getCurrentToken();
		$this->currentPosition++;
		return $returnValue;
	}

	/**
	 * Peeks token given an offset relative to current position
	 * offset can be positive of negative
	 * return null if index is out of range
	 */
	public function peek($offset)
	{
		$index = $this->currentPosition+$offset;
		if($index < 0 || $index >= $this->numberOfTokens) return null;
		return $this->getToken($index);
	}

	/**
	 * Consumes tokens until regular expression matches.
	 * Returns the concatenation of all consumed tokens, without consuming the reached limit
	 */
	public function consumeUntilRegLimit($tokenLimitRegPattern)
	{
		$returnValue = '';
		while($this->hasMoreToken())
		{
			$t = $this->getCurrentToken();
			if(preg_match($tokenLimitRegPattern, $t) > 0)
			{
				return $returnValue;
			}
			else
			{
				$returnValue .= $t;
				$this->currentPosition++;
			}
		}
	}
	/**
	 * Consumes tokens until exact match.
	 * Returns the concatenation of all consumed tokens, without consuming the reached limit
	 */
	public function consumeUntilLimit($tokenLimit)
	{
		$returnValue = '';
		while($this->hasMoreToken())
		{
			$t = $this->getCurrentToken();
			if($tokenLimit === $t)
			{
				return $returnValue;
			}
			else
			{
				$returnValue .= $t;
				$this->currentPosition++;
			}
		}
	}

	/**
	 * Returns true if token is a symbol
	 */
	public function isTokenASymbol($token)
	{
		return (preg_match('/^'.$this->symbols_pattern.'$/s', $token) > 0);
	}

	/**
	 * Returns true if current token is a symbol
	 */
	public function isCurrentTokenASymbol()
	{
		return $this->isTokenASymbol($this->getCurrentToken());
	}

	/**
	 * Returns true if token is a string literal
	 */
	public function isTokenAStringLiteral($token)
	{
		return (preg_match('/^'.$this->string_literal_pattern.'$/s', $token) > 0);
	}

	/**
	 * Returns true if current token is a string literal
	 */
	public function isCurrentTokenAStringLiteral()
	{
		return $this->isTokenAStringLiteral($this->getCurrentToken());
	}

	/**
	 * Returns true if has more unread tokens in the list
	 */
	public function hasMoreToken()
	{
		return ($this->currentPosition < $this->numberOfTokens);
	}
	/**
	 * Rewinds internal pointer at the beginning of the list
	 */
	public function rewind()
	{
		$this->currentPosition = 0;
	}

	/**
	 * Moves internal pointer of offset (can be negative)
	 */
	public function move($offset)
	{
		$this->currentPosition += $offset;
	}

	/**
	 * Returns current internal pointer position
	 */
	public function getCurrentPosition()
	{
		return $this->currentPosition;
	}

	/**
	 * Raises a syntax error when found an unexpected token
	 */
	public function raiseSyntaxError($expectedToken)
	{
		if($this->hasMoreToken())
		{
			throw new StringTokenizerException("Syntax error. Found token '"
				.$this->getCurrentToken()
				."' at position ".($this->getCurrentPosition()+1)
				." instead of expected token '$expectedToken'",
				StringTokenizerException::SYNTAX_ERROR);
		}
		else
		{
			throw new StringTokenizerException("Syntax error. No token found at position ".($this->getCurrentPosition()+1)
				." instead of expected token '$expectedToken'",
				StringTokenizerException::SYNTAX_ERROR);
		}
	}
}