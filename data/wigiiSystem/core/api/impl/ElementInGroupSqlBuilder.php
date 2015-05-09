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
 * Element in Group LogExp sql builder
 * Created by CWE on 15 fév. 2010
 *
 * Creates an sql query to select element ids using LogExp containing ING, INGR, NOTING and NOTINGR expressions.
 *
 * ING(groupLogExp) = returns element ids contained in the groups selected by the log exp
 * NOTING(groupLogExp) = returns element ids not contained in the groups selected by the log exp
 * INGR(groupLogExp) = returns element ids contained in the groups selected by the log exp or in any of its children groups
 * NOTINGR(groupLogExp) = returns element ids not contained in the groups selected by the log exp and not in any of its children groups
 *
 *
 * Sql building rules :
 *
 * ING($select_Groups):= select E.id_element from Elements_Groups as E inner join Groups as G on E.id_group = G.id_group and ($select_Groups)
 * NOTING($select_Groups):= select E.id_element from Elements as E left join ING($select_Groups) as E1 on E.id_element = E1.id_element where E1.id_element is null
 * GR($select_Groups):= select GG.id_group from Groups_Groups as GG inner join Groups as G on GG.id_group_owner = G.id_group and ($select_Groups) group by GG.id_group
 * INGR($select_Groups):= ING($select_Groups) union distinct ING(G.id_group in (GR($select_Groups)))
 * NOTINGR($select_Groups):= select E1.id_element from (NOTING($select_Groups)) as E1 inner join (NOTING(G.id_group in (GR($select_Groups)))) as E2 on E1.id_element = E2.id_element
 * ING1 AND ING2:= select E1.id_element from (ING1) as E1 inner join (ING2) as E2 on E1.id_element = E2.id_element
 * ING1 OR ING2:= ING1 union distinct ING2
 */
class ElementInGroupSqlBuilder extends MySqlQueryBuilder implements LogExpVisitor
{
	private $_debugLogger;
	private $groupASImpl;
	protected $sqlElementInGroup;
	protected $sqlBuilt;
	protected $sqlNumberOfJoins;
	protected $sqlTableAliasIndex;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementInGroupSqlBuilder");
		}
		return $this->_debugLogger;
	}

	// Object lifecycle

	public static function createInstance()
	{
		$returnValue = new ElementInGroupSqlBuilder();
		$returnValue->reset();
		return $returnValue;
	}
	public function reset()
	{
		parent::reset();
		unset($this->sqlElementInGroup);
		$this->sqlBuilt = false;
		$this->sqlNumberOfJoins = 0;
		$this->sqlTableAliasIndex = 1;
	}


	// Dependency injection

	/**
	 * depends on GroupAdminServiceImpl or on a subclass
	 */
	public function setGroupAdminServiceImpl($groupAdminServiceImpl)
	{
		if(isset($groupAdminServiceImpl) && !($groupAdminServiceImpl instanceof GroupAdminServiceImpl))
		{
			throw new ElementServiceException("ElementInGroupSqlBuilder depends on GroupAdminServiceImpl or a subclass", ElementServiceException::INVALID_ARGUMENT);
		}
		$this->groupASImpl = $groupAdminServiceImpl;
	}
	protected function getGroupAdminServiceImpl()
	{
		// autowired
		if(!isset($this->groupASImpl))
		{
			$this->setGroupAdminServiceImpl(ServiceProvider::getGroupAdminService());
		}
		return $this->groupASImpl;
	}


	// Service implementation

	/**
	 * Builds sql for Element in Group selection
	 */
	public function buildSqlForElementInGroup($elementInGroupLogExp)
	{
		if(!$this->sqlBuilt)
		{
			$elementInGroupLogExp->acceptLogExpVisitor($this);
			$this->sqlBuilt = true;
		}
		return $this->sqlElementInGroup;
	}

	public function getSqlForElementInGroup()
	{
		if(!$this->sqlBuilt) throw new ElementServiceException("sql has not been built yet, please call first buildSqlForElementInGroup method", ElementServiceException::INVALID_STATE);
		return $this->sqlElementInGroup;
	}

	/**
	 * Returns number of joins present in built sql
	 */
	public function getNumberOfJoins()
	{
		return $this->sqlNumberOfJoins;
	}

	// LogExp visitor implementation

	public function actOnAndExp($andLogExp)
	{
		$ops = $andLogExp->getOperands();
		if(isset($ops))
		{
			$firstOp = true;
			$E1 = "E".$this->sqlTableAliasIndex; $this->sqlTableAliasIndex++;
			foreach($ops as $logExp)
			{
				if($firstOp)
				{
					$firstOp = false;
					$this->sqlElementInGroup .= "select $E1.id_element from (";
					$logExp->acceptLogExpVisitor($this);
					$this->sqlElementInGroup .= ") as $E1";
				}
				else
				{
					$E2 = "E".$this->sqlTableAliasIndex; $this->sqlTableAliasIndex++;
					$this->sqlElementInGroup .= " inner join (";
					$logExp->acceptLogExpVisitor($this);
					$this->sqlElementInGroup .= ") as $E2 on $E1.id_element = $E2.id_element";
					$this->sqlNumberOfJoins++;
				}
			}
		}
	}
	public function actOnOrExp($orLogExp)
	{
		$ops = $orLogExp->getOperands();
		if(isset($ops))
		{
			$firstOp = true;
			foreach($ops as $logExp)
			{
				if($firstOp) $firstOp = false;
				else
				{
					$this->sqlElementInGroup .= " union distinct ";
					$this->sqlNumberOfJoins++;
				}
				$logExp->acceptLogExpVisitor($this);
			}
		}
	}
	/**
	 * NOT(in group log exp) cannot be interpreted,
	 * therefore negation is automatically reduced.
	 */
	public function actOnNotExp($notLogExp)
	{
		$notLogExp->reduceNegation(true)->acceptLogExpVisitor($this);
	}
	public function actOnInGroup($inGroupLogExp)
	{
		$groupLogExp= $inGroupLogExp->getGroupSelectionLogExp()->reduceNegation(true);
		if($inGroupLogExp->includeChildrenGroups())
		{
			$this->sqlElementInGroup .= $this->getSqlForINGR($groupLogExp);
		}
		else
		{
			$this->sqlElementInGroup .= $this->getSqlForING($groupLogExp);
		}

	}
	public function actOnNotInGroup($notInGroupLogExp)
	{
		$groupLogExp= $notInGroupLogExp->getGroupSelectionLogExp()->reduceNegation(true);
		if($notInGroupLogExp->includeChildrenGroups())
		{
			$this->sqlElementInGroup .= $this->getSqlForNOTINGR($groupLogExp);
		}
		else
		{
			$this->sqlElementInGroup .= $this->getSqlForNOTING($groupLogExp);
		}
	}

	/**
	 * Gets sql for Group selection log exp
	 * G: table alias for Groups
	 * returns sql
	 */
	protected function getSqlForSelectGroups($groupLogExp, $G)
	{
		return $this->getGroupAdminServiceImpl()->getSqlWhereClauseForSelectGroups($groupLogExp, $G);
	}

	protected function getSqlForING($groupLogExp)
	{
		$E = "E".$this->sqlTableAliasIndex; $G = "G".$this->sqlTableAliasIndex; $this->sqlTableAliasIndex++;
		$select_Groups = $this->getSqlForSelectGroups($groupLogExp, $G);
		$this->sqlNumberOfJoins++;
		return "select $E.id_element from Elements_Groups as $E inner join Groups as $G on $E.id_group = $G.id_group and ($select_Groups) group by $E.id_element";
	}
	protected function getSqlForNOTING($groupLogExp)
	{
		$E = "E".$this->sqlTableAliasIndex; $this->sqlTableAliasIndex++; $E1 = "E".$this->sqlTableAliasIndex; $this->sqlTableAliasIndex++;
		$this->sqlNumberOfJoins++;
		return "select $E.id_element from Elements as $E left join (".$this->getSqlForING($groupLogExp).") as $E1 on $E.id_element = $E1.id_element where $E1.id_element is null";
	}
	protected function getSqlForGR($groupLogExp)
	{
		$G = "G".$this->sqlTableAliasIndex; $GG = "GG".$this->sqlTableAliasIndex; $this->sqlTableAliasIndex++;
		$select_Groups = $this->getSqlForSelectGroups($groupLogExp, $G);
		$this->sqlNumberOfJoins++;
		return "select $GG.id_group from Groups_Groups as $GG inner join Groups as $G on $GG.id_group_owner = $G.id_group and ($select_Groups) group by $GG.id_group";
	}
	protected function getSqlForINGR($groupLogExp)
	{
		$this->sqlNumberOfJoins++;
		return $this->getSqlForING($groupLogExp)." union distinct ".str_replace("Group.innerSelect", $this->getSqlForGR($groupLogExp), $this->getSqlForING(LogExp::createInExp(FieldSelector::createInstance("id"), array("Group.innerSelect"))));
	}
	protected function getSqlForNOTINGR($groupLogExp)
	{
		$E1 = "E".$this->sqlTableAliasIndex; $this->sqlTableAliasIndex++; $E2 = "E".$this->sqlTableAliasIndex; $this->sqlTableAliasIndex++;
		$this->sqlNumberOfJoins++;
		return "select $E1.id_element from (".$this->getSqlForNOTING($groupLogExp).") as $E1 inner join (".str_replace("$E2.innerSelect", $this->getSqlForGR($groupLogExp), $this->getSqlForNOTING(LogExp::createInExp(FieldSelector::createInstance("id"), array("$E2.innerSelect")))).") as $E2 on $E1.id_element = $E2.id_element group by $E1.id_element";
	}

	public function actOnSmaller($obj, $val)
	{
		throw new ElementServiceException("SMALLER binary operation is not supported in Element In Group LogExp", ElementServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnSmallerEq($obj, $val)
	{
		throw new ElementServiceException("SMALLER OR EQUAL binary operation is not supported in Element In Group LogExp", ElementServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnGreater($obj, $val)
	{
		throw new ElementServiceException("GREATER binary operation is not supported in Element In Group LogExp", ElementServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnGreaterEq($obj, $val)
	{
		throw new ElementServiceException("GREATER OR EQUAL binary operation is not supported in Element In Group LogExp", ElementServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnEqual($obj, $val)
	{
		throw new ElementServiceException("EQUAL binary operation is not supported in Element In Group LogExp", ElementServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnNotEqual($obj, $val)
	{
		throw new ElementServiceException("NOT EQUAL binary operation is not supported in Element In Group LogExp", ElementServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnIn($obj, $vals)
	{
		throw new ElementServiceException("IN binary operation is not supported in Element In Group LogExp", ElementServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnNotIn($obj, $vals)
	{
		throw new ElementServiceException("NOT IN binary operation is not supported in Element In Group LogExp", ElementServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnLike($obj, $val)
	{
		throw new ElementServiceException("LIKE binary operation is not supported in Element In Group LogExp", ElementServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnMatchAgainst($obj, $val)
	{
		throw new ElementServiceException("MatchAgainst binary operation is not supported in Element In Group LogExp", ElementServiceException::UNSUPPORTED_OPERATION);
	}
	public function actOnNotLike($obj, $val)
	{
		throw new ElementServiceException("NOT LIKE binary operation is not supported in Element In Group LogExp", ElementServiceException::UNSUPPORTED_OPERATION);
	}
}