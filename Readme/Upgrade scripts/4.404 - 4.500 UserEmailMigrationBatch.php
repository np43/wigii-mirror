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
 * This script migrates all emails from the general context to the new email field in Users table.
 * 
 * To Use this script, you must add this file into the folder /data/wigiiSystem/core/_cliImplExecutor
 * Rename the file to remove the numbers of version
 * Then from this folder you can execute the following command : wigii_cli.bat -c CLIENT_NAME -URootPrincipal execBatch UserEmailMigrationBatch
 * CLIENT_NAME must be replaced by the name of your client
 * You can check the result by looking at the file out.log or err.log
 * Created by Medair (LMA) on 21.01.2017
 * 
 * If you don't have access to Wigii CLI, you can use the alternative way of running scripts through the Wigii web interface (Wigii Scripts module)
 * Consult the online documentation https://resource.wigii.org/#Public/Documentation/item/1936.
 * Updated by Wigii.org (CWE) on 31.03.2017
*/
class UserEmailMigrationBatch extends WigiiBatch {
	
	private $_executionSink;
	private function executionSink() {
		if (!isset ($this->_executionSink)) {
			$this->_executionSink = ExecutionSink :: getInstance("UserEmailMigrationBatch");
		}
		return $this->_executionSink;
	}
	
	public function run($argc, $argv, $subArgIndex){
		//Connect to the DB
		try {
			$db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PWD);
		}catch (PDOException $e){
			die('Connexion error on the database - '. $e);
		}
		
		$this->executionSink()->log("Migration Start");
		
		$res = $db->query('SELECT * FROM Users WHERE isRole IS null AND info_lastSessionContext IS NOT null');
		$res->setFetchMode(PDO::FETCH_OBJ);
		$clientName = $this->getClient()->getClientName();
		while ($r = $res->fetch()){
			//Get session Context
			$sessionContext = $r->info_lastSessionContext;
			$userId = $r->id_user;
			
			// if User.email is not defined, migrates email from generalContext
			if(!$r->email) {
				$sessionContextArray = str2array($sessionContext);
				$email = $sessionContextArray['generalContext']['email'];
				
				if(isset($email)){
					//Log
					$this->executionSink()->log("Migration of ". $email);
					
					// Removes email from context
					unset($sessionContextArray['generalContext']['email']);
					$updatedGeneralContext = addslashes(stripslashes(array2str($sessionContextArray)));
					//Create request for migration
					$request = 'UPDATE Users SET email="'. $email. '", emailProof=null, emailProofStatus=0, emailProofKey=MD5(TRIM(LOWER(CONCAT("'.$clienName.'-", NOW(), RAND(), "-","'. $email.'")))), info_lastSessionContext = "'.$updatedGeneralContext.'" WHERE id_user = '. $userId. ' AND isRole IS null';
					$db->exec($request);
				}
			}
			// if User.email, removes email from generalContext
			else {
				$sessionContextArray = str2array($sessionContext);
				$email = $sessionContextArray['generalContext']['email'];
				
				if(isset($email)){
					//Log
					$this->executionSink()->log("Cleanup of ". $email);
						
					// Removes email from context
					unset($sessionContextArray['generalContext']['email']);
					$updatedGeneralContext = addslashes(stripslashes(array2str($sessionContextArray)));
					//Create request for migration
					$request = 'UPDATE Users SET info_lastSessionContext = "'.$updatedGeneralContext.'" WHERE id_user = '. $userId. ' AND isRole IS null';
					$db->exec($request);
				}
			}
		}
		
		$this->executionSink()->log("Migration End");
	}
	
}