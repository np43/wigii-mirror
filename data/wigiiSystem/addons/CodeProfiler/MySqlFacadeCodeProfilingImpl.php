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
 * MySql database technical facade for performance monitoring
 * Created by CWE on 9.2.2013
 */
class MySqlFacadeCodeProfilingImpl extends MySqlFacade //extends MySqlFacadeWithPConnections
{
	// Dependency injection
		
	private $codeProfiler;
	
	public function setCodeProfiler($codeProfiler)
	{
		$this->codeProfiler = $codeProfiler;
	}
	protected function getCodeProfiler()
	{
		// autowired
		if(!isset($this->codeProfiler))
		{
			$this->codeProfiler = TechnicalServiceProviderCodeProfilingImpl::getCodeProfiler();
		}
		return $this->codeProfiler;
	}	
	
	// Implementation
	
	/**
	 * Sql trace
	 */
	public function logSql($sqlCode, $principal=null)
	{
		$this->getCodeProfiler()->logSql('MySqlFacadeCodeProfilingImpl', $sqlCode, $principal);
	}
	
	/**
	 * Executes an sql select query and returns the first record as an array or null if no records
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * throws MySqlFacadeException in case of error
	 */
	public function selectFirst($principal, $sql, $cnxSettings, $resultType=MYSQL_ASSOC)
	{
		$this->logSql($sql, $principal);
		return parent::selectFirst($principal, $sql, $cnxSettings, $resultType);
	}

	/**
	 * Executes an sql select query and returns the only available record as an array or null if no records
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * throws MySqlFacadeException in case of error
	 * throws MySqlFacadeException(INVALID_ARGUMENT) if number of records selected in the database is greater than one.
	 */
	public function selectOne($principal, $sql, $cnxSettings, $resultType=MYSQL_ASSOC)
	{
		$this->logSql($sql, $principal);
		return parent::selectOne($principal, $sql, $cnxSettings, $resultType);
	}

	/**
	 * Selects first column of one record and returns cell value
	 * Useful for selects which uses aggregators like COUNT, MAX, etc.
	 */
	public function selectOneValue($principal, $sql, $cnxSettings)
	{
		$this->logSql($sql, $principal);
		return parent::selectOneValue($principal, $sql, $cnxSettings);
	}

	/**
	 * Executes an sql select query and fills a list of records
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * rowList: a list of rows to be filled
	 * resultType: MYSQL result type for each record, column name association or column number association. Default to column name association.
	 * returns the number of records selected.
	 * throws MySqlFacadeException in case of error
	 */
	public function selectAll($principal, $sql, $cnxSettings, $rowList, $resultType=MYSQL_ASSOC)
	{
		$this->logSql($sql, $principal);
		return parent::selectAll($principal, $sql, $cnxSettings, $rowList, $resultType);
	}

	/**
	 * Executes a paged sql select query and fills a list of records
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * rowList: a list of rows to be filled
	 * offset: offset of page start, starting with 0, see MYSQL LIMIT reference
	 * pageSize: number maximum of records per page to return, see MYSQL LIMIT reference
	 * resultType: MYSQL result type for each record, column name association or column number association. Default to column name association.
	 *
	 * returns the total number of records that would have been selected if no page was used.
	 * throws MySqlFacadeException in case of error
	 *
	 * postcondition: automatically adds to sql query the SQL_CALC_FOUND_ROWS and LIMIT clauses
	 */
	public function selectPage($principal, $sql, $cnxSettings, $offset, $pageSize, $rowList, $resultType=MYSQL_ASSOC)
	{
		$this->logSql($sql, $principal);
		return parent::selectPage($principal, $sql, $cnxSettings, $offset, $pageSize, $rowList, $resultType);
	}
	
	/**
	 * Inserts one record without execution log.
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * returns the insert id
	 * throws MySqlFacadeException in case of error
	 */
	public function insertOneWithoutLog($principal, $sql, $cnxSettings){		
		try
		{			
			$returnValue = $this->query($sql, $cnxSettings, MySqlFacade::Q_INSERTONE);
		}
		catch(MySqlFacadeException $sqle)
		{			
			throw $sqle;
		}
		catch(Exception $e)
		{			
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}		
		return $returnValue;
	}
	
	/**
	 * Executes an sql select query and fills a list of records without execution log
	 */
	public function selectAllWithoutLog($principal, $sql, $cnxSettings, $rowList, $resultType=MYSQL_ASSOC)
	{		
		$n = 0;
		try
		{
			if(!isset($rowList)) throw new MySqlFacadeException("rowList cannot be null", MySqlFacadeException::INVALID_ARGUMENT);
			$idQuery = $this->query($sql, $cnxSettings, MySqlFacade::Q_SELECTALL);
//			fput($sql);
//			fput($idQuery);
//			fput(mysql_num_rows($idQuery));
			while($row = mysql_fetch_array($idQuery, $resultType)){
				$rowList->addRow($row);
				$n++;
			}
			$this->freeMemoryResult($idQuery);
		}
		catch(MySqlFacadeException $sqle)
		{			
			throw $sqle;
		}
		catch(Exception $e)
		{
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		return $n;
	}
}
