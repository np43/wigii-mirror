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
 * MySql Where Clause builder based on a LogExp based on FieldSelectors
 * This class should be subclassed to work properly.
 * Created by CWE on 22 déc. 09
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
class FieldSelectorLogExpSqlBuilder extends MySqlQueryBuilder implements LogExpVisitor
{
	protected $whereClause;
	protected $updateOrDelete;

	// Object lifecycle

	public function reset()
	{
		parent::reset();
		unset($this->whereClause);
		$this->updateOrDelete = false;
	}

	// Service implementation

	/**
	 * Sets where clause based on FieldSelector LogExp
	 */
	public function setWhereClauseFieldSelectorLogExp($fsLogExp)
	{
		if(isset($fsLogExp))
		{
			unset($this->whereClause);
			$fsLogExp->acceptLogExpVisitor($this);
			$this->setWhereClause($this->whereClause);
			unset($this->whereClause);
		}
	}

	/**
	 * Builds where clause based on FieldSelector LogExp and returns it.
	 */
	public function buildWhereClause($logExp)
	{
		$this->setWhereClauseFieldSelectorLogExp($logExp);
		return $this->getWhereClause();
	}

	/**
	 * Converts FieldSortingKey list to order by clause
	 */
	public function convertFieldSortingKeyListToOrderByClause($fieldSortingKeyList)
	{
		if(isset($fieldSortingKeyList))
		{
			foreach($fieldSortingKeyList->getListIterator() as $fsk)
			{
				$sqlColName = $this->getSqlColumnNameForFieldSelector($fsk);
				$sqlType = $this->getSqlDataTypeForFieldSelector($fsk);
				$tableAlias = $this->getTableAliasForFieldSelector($fsk);
				if(isset($tableAlias) && $tableAlias != '') $tableAlias .= ".";
				$this->orderByCol($tableAlias.$sqlColName, $fsk->isAscending());
			}
		}
	}

	/**
	 * Returns table alias for this FieldSelector.
	 * Default to empty string
	 */
	public function getTableAliasForFieldSelector($fieldSelector)
	{
		return '';
	}

	/**
	 * Returns sql column name for this field selector
	 * Default to fieldName if subFieldName is null else XFieldNameXSubFieldNameZZ
	 */
	public function getSqlColumnNameForFieldSelector($fieldSelector)
	{
		if(is_null($fieldSelector)) throw new MySqlQueryBuilderException("fieldSelector cannot be null", MySqlQueryBuilderException::INVALID_ARGUMENT);
		$subFieldName = $fieldSelector->getSubFieldName();
		if(isset($subFieldName))
		{
			return 'X'.$fieldSelector->getFieldName().'X'.$subFieldName.'ZZ';
		}
		else
		{
			return $fieldSelector->getFieldName();
		}
	}

	/**
	 * Returns MySqlQueryBuilder::SQLTYPE_? for the given fieldSelector
	 * default throws MySqlQueryBuilderException::UNSUPPORTED_OPERATION,
	 * subclass should provide implementation
	 */
	public function getSqlDataTypeForFieldSelector($fieldSelector)
	{
		throw new MySqlQueryBuilderException("subclass should implement this method", MySqlQueryBuilderException::UNSUPPORTED_OPERATION);
	}
	
	/**
	 * @return Boolean returns true if this query builder supports sys info fields. Returns null if no information is available, returns false if known as unsupported. Defaults to null.
	 */
	public function supportsSysInformation() {
		return null;
	}

	public function setTableForDelete($tableName)
	{
		parent::setTableForDelete($tableName);
		$this->updateOrDelete = true;
	}

	public function setTableForUpdate($tableName, $insertIfNotExist=false)
	{
		parent::setTableForUpdate($tableName, $insertIfNotExist);
		$this->updateOrDelete = true;
	}

	public function updateSysUser($principal, $timestamp=null) {
		if($this->supportsSysInformation()!==false) parent::updateSysUser($principal, $timestamp);
	}
	
	// LogExp visitor implementation

	public function actOnAndExp($andLogExp)
	{
		$ops = $andLogExp->getOperands();
		if(isset($ops))
		{
			$firstOp = true;
			foreach($ops as $logExp)
			{
				if($firstOp) $firstOp = false;
				else
				{
					$this->whereClause .= " AND ";
				}
				$logExp->acceptLogExpVisitor($this);
			}
		}
	}
	public function actOnOrExp($orLogExp)
	{
		$ops = $orLogExp->getOperands();
		if(isset($ops))
		{
			$firstOp = true;
			$this->whereClause .= " (";
			foreach($ops as $logExp)
			{
				if($firstOp) $firstOp = false;
				else
				{
					$this->whereClause .= " OR ";
				}
				$logExp->acceptLogExpVisitor($this);
			}
			$this->whereClause .= ") ";
		}
	}
	public function actOnNotExp($notLogExp)
	{
		$logExp = $notLogExp->getLogExp();
		if(isset($logExp))
		{
			$this->whereClause .= " NOT(";
			$logExp->acceptLogExpVisitor($this);
			$this->whereClause .= ") ";
		}
	}
	public function actOnSmaller($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, '<', $val);
	}
	public function actOnSmallerEq($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, '<=', $val);
	}
	public function actOnGreater($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, '>', $val);
	}
	public function actOnGreaterEq($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, '>=', $val);
	}
	public function actOnEqual($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, '=', $val);
	}
	public function actOnNotEqual($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, '!=', $val);
	}
	public function actOnIn($obj, $vals)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, 'IN', $vals);
	}
	public function actOnNotIn($obj, $vals)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, 'NOTIN', $vals);
	}
	public function actOnLike($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, 'LIKE', $val);
	}
	public function actOnMatchAgainst($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, 'MATCHAGAINST', $val);
	}
	public function actOnNotLike($obj, $val)
	{
		$this->convertFieldSelectorBinExpToWhereClauseChunk($obj, 'NOTLIKE', $val);
	}
	protected function convertFieldSelectorBinExpToWhereClauseChunk($fs, $op, $val)
	{
		if(is_null($fs)) throw new MySqlQueryBuilderException("fieldSelector cannot be null", MySqlQueryBuilderException::INVALID_ARGUMENT);

		$sqlColName = $this->getSqlColumnNameForFieldSelector($fs);
		$sqlType = $this->getSqlDataTypeForFieldSelector($fs);
		if(!$this->updateOrDelete) $tableAlias = $this->getTableAliasForFieldSelector($fs);
		if(isset($tableAlias) && $tableAlias != '') $tableAlias .= ".";
		$this->whereClause .= $this->formatBinExp($tableAlias.$sqlColName, $op, $val, $sqlType);
	}
	public function actOnInGroup($inGroupLogExp)
	{
		throw new MySqlQueryBuilderException("actOnInGroup is not supported", MySqlQueryBuilderException::UNSUPPORTED_OPERATION);
	}
	public function actOnNotInGroup($notInGroupLogExp)
	{
		throw new MySqlQueryBuilderException("actOnNotInGroup is not supported", MySqlQueryBuilderException::UNSUPPORTED_OPERATION);
	}
}