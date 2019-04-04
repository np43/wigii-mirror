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
 * wigii database administration service
 * Created by CWE on 23 juin 09
 */
interface DbAdminService
{

	/**
	 * Returns the connection settings needed by the user to connect to its database
	 * returns a DbConnectionSettings object
	 * principal: authenticated user performing the operation
	 * throws DbAdminServiceException in case of error
	 */
	public function getDbConnectionSettings($principal);

	/**
	 * Deletes rows in a table and cascades deletion in whole schema following relations.
	 * principal: authenticated user performing the operation
	 * tableName: table where to delete rows
	 * whereClause: sql string representing a logical expression selecting the rows in the table to be deleted
	 * cnxSettings: the DbConnectionSettings object to use to access the database
	 * precondition: column names in whereClause should be prefixed with tableName
	 */
	public function deleteRows($principal, $tableName, $whereClause, $cnxSettings);

	/**
	 * Returns the database table name linked to a Wigii DataType
	 */
	public function getDataTypeTableName($dataType);

	/**
	 * Get the list of locked ids.
	 * @param DbEntity|ObjectList $dbEntity can be either DbEntity or ObjectList of DbEntity
	 * If dbEntity is a list, then lock is checked on all objects.
	 * returns array of ids of locked items
	 */
	public function getLocked($principal, $tableName, $dbEntity);
	
	/**
	 * Locks the dbEntity/s
	 * @param Principal authenticated user performing the operation 
	 * @param DbEntity|ObjectList $dbEntity can be either DbEntity or ObjectList of DbEntity
	 * @param boolean $transfer if true, then an existing lock owned by another principal is transfered to the current principal.
	 * This works only for RootPrincipal. If false, then an existing lock is not tried to be acquired. Defaults to false. 
	 * @throws AuthorizationServiceException AuthorizationServiceException::OBJECT_IS_LOCKED if lock cannot be acquired by principal
	 * because it is already locked by someone else.
	 * If dbEntity is a list, then lock should be acquired on all objects, not only partially.
	 * @return true if lock has been set for the first time,
	 * else returns false if lock has just been renewed by the same principal
	 */
	public function lock($principal, $tableName, $dbEntity, $transfer=false);

	/**
	 * unLocks the dbEntity/s
	 * @param DbEntity|ObjectList $dbEntity can be either DbEntity or ObjectList of DbEntity
	 */
	public function unLock($principal, $tableName, $dbEntity);


	
	// Temp table space
	
	/**
	 * Creates a persistant db table that is used as a temporary table inside wigii.
	 * The table is created with a lease time of 2h=120min by default.
	 * If after 2h the table has never been touched again, it is automatically dropped.
	 * @param Principal $principal authenticated user performing the operation
	 * @param String $sqlCreationStatement part of the SQL table creation statement, 
	 * defining all the creation details such as columns, constraints, indexes and 
	 * optional select statement used to populate the table.
	 * Do not provide any 'CREATE TABLE tbl_name' string as the table name will be generated
	 * by the DbAdminService and returned as a result of this function. 
	 * @param DbConnectionSettings $cnxSettings the connection settings used to connect to the db
	 * @param int $leaseTime timeout in minutes after which the table is dropped if never touched.
	 * @return String the generated temp table name.
	 * @throws DbAdminServiceException in case of error
	 */
	public function createTempTable($principal, $sqlCreationStatement, $cnxSettings, $leaseTime=120);

	/**
	 * Drops the temp table given its name.
	 * @param Principal $principal authenticated user performing the operation
	 * @param String $tableName the temp table name as generated by the createTempTable method.
	 * @param DbConnectionSettings $cnxSettings the connection settings used to connect to the db 
	 * where is located the temp table.
	 * @return boolean returns true if table is dropped, false if table does not exist.
	 * @throws DbAdminServiceException in case of error. 
	 */
	public function dropTempTable($principal, $tableName, $cnxSettings);
	
	/**
	 * Indicates to the DbAdminService that the table is still beeing used. 
	 * The lease time is renewed. Optionally a new lease time can be defined if needed.
	 * @param Principal $principal authenticated user performing the operation
	 * @param String $tableName the temp table name as generated by the createTempTable method.
	 * @param DbConnectionSettings $cnxSettings the connection settings used to connect to the db 
	 * where is located the temp table.
	 * @param int $newLeaseTime an optional new lease time in minutes. By default, reuses the precedent lease time.
	 * @throws DbAdminServiceException if table does not exist 
	 * or if lease time is already expired and table is locked for deletion by the cleanup process.
	 */
	public function touchTempTable($principal, $tableName, $cnxSettings, $newLeaseTime=null);

	
	////////////////////////
	// OPERATION DELEGATION
	////////////////////////



	/**
	 * Returns the connection settings needed to connect to the specified client database
	 * returns a DbConnectionSettings object
	 * principal: authenticated user performing the operation
	 * throws DbAdminServiceException in case of error
	 */
	public function getDbConnectionSettingsForClient($principal, $client);
}



