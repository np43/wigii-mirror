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
 * Created on 23 nov. 09 by LWR
 * Modified by Medair in 2016-2017 for maintenance purposes (see SVN log for details)
 */

class EmailServiceWebImpl implements EmailService {

	private $_debugLogger;
	private $_executionSink;

	private $maxRecipientsPerEmail = EmailService_maxRecipients;
	public function setMaxRecipientsPerEmail($max){ $this->maxRecipientsPerEmail = $max; }
	protected function getMaxRecipientsPerEmail(){ return $this->maxRecipientsPerEmail; }
	private $maxEmailsToSendPerExecution = 1;
	public function setMaxEmailsToSendPerExecution($max){ $this->maxEmailsToSendPerExecution = $max; }
	protected function getMaxEmailsToSendPerExecution(){ return $this->maxEmailsToSendPerExecution; }

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("EmailServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("EmailServiceWebImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	/*
	 * dependy injection
	 */
	private $executionS;
	public function setExecutionService($executionService)
	{
		$this->executionS = $executionService;
	}
	protected function getExecutionService()
	{
		// autowired
		if(!isset($this->executionS))
		{
			$this->executionS = ServiceProvider::getExecutionService();
		}
		return $this->executionS;
	}

	private $dbAS;
	public function setDbAdminService($dbAdminService)
	{
		$this->dbAS = $dbAdminService;
	}
	protected function getDbAdminService()
	{
		// autowired
		if(!isset($this->dbAS))
		{
			$this->dbAS = ServiceProvider::getDbAdminService();
		}
		return $this->dbAS;
	}

	private $configS;
	public function setConfigService($configService)
	{
		$this->configS = $configService;
	}
	protected function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = ServiceProvider::getConfigService();
		}
		return $this->configS;
	}

	private $mysqlF;
	public function setMySqlFacade($mysqlFacade)
	{
		$this->mysqlF = $mysqlFacade;
	}
	protected function getMySqlFacade()
	{
		// autowired
		if(!isset($this->mysqlF))
		{
			$this->mysqlF = TechnicalServiceProvider::getMySqlFacade();
		}
		return $this->mysqlF;
	}

	private $authoS;
	public function setAuthorizationService($authorizationService)
	{
		$this->authoS = $authorizationService;
	}
	protected function getAuthorizationService()
	{
		// autowired
		if(!isset($this->authoS))
		{
			$this->authoS = ServiceProvider::getAuthorizationService();
		}
		return $this->authoS;
	}

	/*
	 * service implementation
	 */
	public function getEmailInstance(){

		$mail = new WigiiEmailWebImpl('UTF-8');
		return $mail;
	}

	public function cloneEmailInstance($mail){
		$clone = clone $mail;
		return $clone;
	}

	protected function getSqlForSelectEmails($principal, $status){
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$microTime = $principal->getMicroTime();
		$statusCd = $sqlB->formatBinExp('E.status', '=', $status, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		//warning, the select page replace the first 6 chars with adding the SQL_CALC_FOUND_ROWS --> don't add a new line in front the SELECT
		return "SELECT ".$this->getSqlColumnsForEmail('E')." FROM ".$this->getDbTablename()." as E
WHERE $statusCd AND (E.sys_lockId IS NULL OR (E.sys_lockMicroTime + ".$this->getDbAdminService()->getLockExpirationMicrosec()." < $microTime))
";
	}
	protected function getSqlColumnsForEmail($alias){
		if($alias != null) $alias .= ".";
		$col = array();
		$col[] = "$alias`id_email`";
		$col[] = "$alias`status`";
		$col[] = "$alias`nbFailure`";
		$col[] = "$alias`creationDate`";
		$col[] = "$alias`lastUpdate`";
		$col[] = "$alias`wigiiNamespace`";
		$col[] = "$alias`userId`";
		$col[] = "$alias`username`";
		$col[] = "$alias`realUserId`";
		$col[] = "$alias`realUsername`";
		$col[] = "$alias`attachement`";
		$col[] = "$alias`to`";
		$col[] = "$alias`cc`";
		$col[] = "$alias`bcc`";
		$col[] = "$alias`replyTo`";
		$col[] = "$alias`from`";
		$col[] = "$alias`subject`";
		$col[] = "$alias`bodyHtml`";
		$col[] = "$alias`bodyText`";
		return implode(", ", $col);
	}

	protected function getDbTablename(){
		return "EmailService";
	}
	protected function getDbEmailAttachementsTablename(){
		return "EmailServiceAttachementsToDelete";
	}

	/**
	 * @param $nb : int, nb of time the email is chunck
	 */
	protected function persistEmailsAttachementToDelete($principal, $email, $nb){
		//store mail in DB
		if($email==null) return null;
		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
		$mysqlF = $this->getMySqlFacade();
		$sql = $this->getSqlForPersistEmailsAttachementToDelete($principal, $email, $nb);
		if($sql==null) return null;
		$returnValue = $mysqlF->insertMultiple($principal,
				$sql,
				$dbCS);
		return $returnValue;
	}
	protected function getSqlForPersistEmailsAttachementToDelete($principal, $email, $nb){
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert($this->getDbEmailAttachementsTablename());

		$time = time();
		$first = true;
		$attachements = $email->getAttachementsForDb();
		if(is_array($attachements)){
			foreach($attachements as $attachement){
				if(!$attachement["deleteFileAfterSend"]) continue;
				if($first){
					$sqlB->insertValue("path", $attachement["path"], MySqlQueryBuilder::SQLTYPE_VARCHAR);
					$sqlB->insertValue("nb", $nb, MySqlQueryBuilder::SQLTYPE_INT);
					$sqlB->insertValue("timestamp", $time, MySqlQueryBuilder::SQLTYPE_INT);
					$first = false;
				} else {
					$sqlB->insertMoreRecord();
					$sqlB->insertMoreValue($attachement["path"], MySqlQueryBuilder::SQLTYPE_VARCHAR);
					$sqlB->insertMoreValue($nb, MySqlQueryBuilder::SQLTYPE_INT);
					$sqlB->insertMoreValue($time, MySqlQueryBuilder::SQLTYPE_INT);
				}
			}
		}
		if($first) return null; //no attachement found for delete
		return $sqlB->getSql();
	}
	protected function persistEmail($principal, $email, $status){
		//store mail in DB
		if($email==null) return null;
		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
		$mysqlF = $this->getMySqlFacade();
		$returnValue = $mysqlF->insertOne($principal,
				$this->getSqlForPersistEmail($principal, $email, $status),
				$dbCS);
		return $returnValue;
	}
	protected function getSqlForPersistEmail($principal, $email, $status){
		return $this->getSqlForPersistMultipleEmails($principal, array($email), $status);
	}
	protected function persistEmails($principal, $emails, $status){
		//store mail in DB
		if($emails==null) return null;
		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
		$mysqlF = $this->getMySqlFacade();
		$returnValue = $mysqlF->insertMultiple($principal,
				$this->getSqlForPersistMultipleEmails($principal, $emails, $status),
				$dbCS);
		return $returnValue;
	}
	protected function getSqlForPersistMultipleEmails($principal, $emails, $status=null){
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForInsert($this->getDbTablename());

		$time = time();

		$first = true;
		foreach($emails as $email){
			if($first){
				$sqlB->insertValue("status", ($status!==null ? $status : $email->getStatus()), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue("nbFailure", $email->getNbFailure(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue("creationDate", $email->getCreationDate(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue("lastUpdate", $time, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue("wigiiNamespace", $principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue("userId", $principal->getUserId(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue("username", addslashes($principal->getUsername()), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue("realUserId", $principal->getRealUserId(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertValue("realUsername", addslashes(($principal->getRealUser() ? $principal->getRealUser()->getUsername() : null)), MySqlQueryBuilder::SQLTYPE_VARCHAR);

				$sqlB->insertValue("charset", addslashes($email->getCharset()), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue("attachement", addslashes(array2str($email->getAttachementsForDb())), MySqlQueryBuilder::SQLTYPE_TEXT);
				$sqlB->insertValue("replyTo", addslashes($email->getReplyToForDb()), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue("from", addslashes($email->getFrom()), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertValue("subject", addslashes($email->getSubjectForDb()), MySqlQueryBuilder::SQLTYPE_TEXT);
				$sqlB->insertValue("to", addslashes(array2str($email->getToForDb())), MySqlQueryBuilder::SQLTYPE_TEXT);
				$sqlB->insertValue("cc", addslashes(array2str($email->getCcForDb())), MySqlQueryBuilder::SQLTYPE_TEXT);
				$sqlB->insertValue("bcc", addslashes(array2str($email->getBccForDb())), MySqlQueryBuilder::SQLTYPE_TEXT);
				$sqlB->insertValue("bodyHtml", $email->getBodyHtmlForDb(), MySqlQueryBuilder::SQLTYPE_TEXT);
				$sqlB->insertValue("bodyText", $email->getBodyTextForDb(), MySqlQueryBuilder::SQLTYPE_TEXT);

				$first = false;
			} else {
				$sqlB->insertMoreRecord();
				$sqlB->insertMoreValue(($status!==null ? $status : $email->getStatus()), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($email->getNbFailure(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($email->getCreationDate(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($time, MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($principal->getWigiiNamespace()->getWigiiNamespaceName(), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($principal->getUserId(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue(addslashes($principal->getUsername()), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($principal->getRealUserId(), MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue(addslashes(($principal->getRealUser() ? $principal->getRealUser()->getUsername() : null)), MySqlQueryBuilder::SQLTYPE_VARCHAR);

				$sqlB->insertMoreValue(addslashes($email->getCharset()), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue(addslashes(array2str($email->getAttachementsForDb())), MySqlQueryBuilder::SQLTYPE_TEXT);
				$sqlB->insertMoreValue(addslashes($email->getReplyToForDb()), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue(addslashes($email->getFrom()), MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue(addslashes($email->getSubjectForDb()), MySqlQueryBuilder::SQLTYPE_TEXT);
				$sqlB->insertMoreValue(addslashes(array2str($email->getToForDb())), MySqlQueryBuilder::SQLTYPE_TEXT);
				$sqlB->insertMoreValue(addslashes(array2str($email->getCcForDb())), MySqlQueryBuilder::SQLTYPE_TEXT);
				$sqlB->insertMoreValue(addslashes(array2str($email->getBccForDb())), MySqlQueryBuilder::SQLTYPE_TEXT);
				$sqlB->insertMoreValue($email->getBodyHtmlForDb(), MySqlQueryBuilder::SQLTYPE_TEXT);
				$sqlB->insertMoreValue($email->getBodyTextForDb(), MySqlQueryBuilder::SQLTYPE_TEXT);
			}
		}

		return $sqlB->getSql();
	}
	protected function updateEmailsStatus($principal, $emails){
		//store mail in DB
		if($emails==null) return null;
		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
		$mysqlF = $this->getMySqlFacade();
		$returnValue = $mysqlF->update($principal,
				$this->getSqlForUpdateEmailsStatus($principal, $emails),
				$dbCS);
		return $returnValue;
	}
	protected function getSqlForUpdateEmailsStatus($principal, $emails){
		$sqlB = $this->getMySqlFacade()->getSqlBuilder();
		$sqlB->setTableForUpdate($this->getDbTablename(), true);

		$first = true;
		foreach($emails as $emailId=>$email){
			if($first){
				$sqlB->updateValue("id_email", $email["id_email"], MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->updateValue("status", $email["status"], MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->updateValue("nbFailure", $email["nbFailure"], MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->updateValue("lastUpdate", $email["lastUpdate"], MySqlQueryBuilder::SQLTYPE_INT);
				$first = false;
			} else {
				$sqlB->insertMoreRecord();
				$sqlB->insertMoreValue($email["id_email"], MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($email["status"], MySqlQueryBuilder::SQLTYPE_VARCHAR);
				$sqlB->insertMoreValue($email["nbFailure"], MySqlQueryBuilder::SQLTYPE_INT);
				$sqlB->insertMoreValue($email["lastUpdate"], MySqlQueryBuilder::SQLTYPE_INT);
			}
		}

		return $sqlB->getSql();
	}
	protected function updateEmailsAttachements($principal, $attachementToDelete){
		//store mail in DB
		if($attachementToDelete==null) return null;
		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
		$mysqlF = $this->getMySqlFacade();
		$returnValue = $mysqlF->update($principal,
				$this->getSqlForUpdateEmailsAttachements($principal, $attachementToDelete),
				$dbCS);
		return $returnValue;
	}
	protected function getSqlForUpdateEmailsAttachements($principal, $attachementToDelete){
		$sql = "UPDATE ".$this->getDbEmailAttachementsTablename()." ";
		$sql .= "SET `nb` = `nb`-1 ";
		$sql .= "WHERE `path` IN ('".implode("','",$attachementToDelete)."') ";
		return $sql;
	}
	protected function deleteObseleteEmailsAttachements($principal){
		//delete any attachement older than 1 year. There is no sense that an email would not be sent yet
		$deleteOlderThan = (time()-3600*24*365);

		$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
		$mysqlF = $this->getMySqlFacade();
		$rowList = RowListForDeleteEmailAttachements::createInstance();
		$returnValue = $mysqlF->selectAll($principal,
				$this->getSqlForSelectObseleteEmailsAttachements($principal, $deleteOlderThan),
				$dbCS, $rowList);
		if($rowList->count()){
			$returnValue = $mysqlF->delete($principal,
					$this->getSqlForDeleteObseleteEmailsAttachements($principal, $deleteOlderThan),
					$dbCS);
		}

		return $returnValue;
	}
	protected function getSqlForSelectObseleteEmailsAttachements($principal, $deleteOlderThan){
		$sql = "SELECT `path` FROM ".$this->getDbEmailAttachementsTablename()." WHERE `nb` = 0 OR `timestamp` < ".$deleteOlderThan;
		return $sql;
	}
	protected function getSqlForDeleteObseleteEmailsAttachements($principal, $deleteOlderThan){
		$sql = "DELETE FROM ".$this->getDbEmailAttachementsTablename()." WHERE `nb` = 0 OR `timestamp` < ".$deleteOlderThan;
		return $sql;
	}

	/**
	 * send the WigiiEmail
	 * @param $p
	 * @param $email : WigiiEmail
	 * @param $mergeData = null, array(key:email->value:array(key:merge field->value:merge data))
	 * @return nb of asynch emails to send
	 */
	public function send($principal, $email, $mergeData=null){
		$this->executionSink()->publishStartOperation("send", $principal);
		try
		{
			$this->assertPrincipalForSend($principal);

			//setup the plain text with the html data
			if($email->getBodyText()==null){
				$processedBody = $email->getBodyHtmlForDb();
				$html2text = new Html2text();
				$html2text->setHtml($processedBody);
				$processedBody = $html2text->getText();
// 				$html2text->clear();
				$email->setBodyText($processedBody);
			}

			//split the email per Y recipients max
			$cc = $email->getCcForDb();
			$bcc = $email->getBccForDb();
			$to = $email->getToForDb();
			$email->clearRecipients();
			$email->setNbFailure(0);

			//if mergeData is defined, then one email is sent to each independently
			$emailList = array();
			$add = array();
			if($mergeData != null){
				if($to) $add = array_merge($add, $to);
				if($cc) $add = array_merge($add, $cc);
				if($bcc) $add = array_merge($add, $bcc);
				foreach($add as $emailAdd=>$name){
					$newEmail = $this->cloneEmailInstance($email);
					$newEmail->addTo($emailAdd, $name);
					$newEmail->mergeData($mergeData[$emailAdd]);
					$emailList[] = $newEmail;
				}
			} else {
				//2 case:
				//1) the to + cc + bcc is less than MaxRecipeintsPerEmail --> create one email
				//2) the to + cc + bcc is biger than MaxRecipientsPerEmail
				//	 add all in bcc + split them

				//1)
				if((count($to)+count($cc)+count($bcc))<=$this->getMaxRecipientsPerEmail()){
					$newEmail = $this->cloneEmailInstance($email);
					if(!$to){
						$to = (string)$this->getConfigService()->getParameter($principal, null, "emailNotificationFrom");
						$newEmail->addTo($to);
					} else {
						$newEmail->setTo($to);
					}
					$newEmail->setCc($cc);
					$newEmail->setBcc($bcc);
					$emailList[] = $newEmail;
				} else {
				//2)
					if($to) $add = array_merge($add, $to);
					if($cc) $add = array_merge($add, $cc);
					if($bcc) $add = array_merge($add, $bcc);
					$add = array_chunk($add, $this->getMaxRecipientsPerEmail(), true);
					$to = (string)$this->getConfigService()->getParameter($principal, null, "emailNotificationFrom");
					foreach($add as $subBcc){
						$newEmail = $this->cloneEmailInstance($email);
						$newEmail->addTo($to);
						$newEmail->setBcc($subBcc);
						$emailList[] = $newEmail;
					}
				}
			}

			$nbJobAdded = 0;
			if($emailList){
				//store the attachements path the number of created emails if should be deleted
				$this->persistEmailsAttachementToDelete($principal, $email, count($emailList));
				$this->persistEmails($principal, $emailList, "toSend");
				$nbJobAdded = count($emailList);
			} else {
				$nbJobAdded = null;
			}

			//increment the number of remaining jobs
			//and launch the cronJobsWorkingFunction to execute them
			$this->getExecutionService()->addJsCode("
$('#cronJobsNb').text(parseInt($('#cronJobsNb').text())+$nbJobAdded);
if(!globalCronJobsStopper) { cronJobsWorkingFunction(); }
");

			return $nbJobAdded;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("send", $e, $principal);
			throw new EmailServiceException("Fail to send email (failed to store split and store email in DB for sending)",EmailServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("send", $principal);
	}

	protected function assertPrincipalForSend($principal){
		if(is_null($principal)) throw new ServiceException('principal can not be null', ServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "EmailService", "send");
	}

	/**
	 * args = array[
	 * 	"jobLimit"=>X, maximum of job to do this time
	 * ]
	 */
	public function wakeup($principal, $args=null){
		$this->executionSink()->publishStartOperation("wakeup", $principal);
		$transS = ServiceProvider::getTranslationService();
		try
		{
			$this->assertPrincipalForWakeup($principal);

			$maxNbJobs = $this->getMaxEmailsToSendPerExecution();
			if($args && $args["jobLimit"]){
				$maxNbJobs = $args["jobLimit"];
			}
			//get the emails
			$emailList = $this->getEmailList();
			$dbCS = $this->getDbAdminService()->getDbConnectionSettings($principal);
			$mysqlF = $this->getMySqlFacade();
			$status = "toSend";
			$totalJobs = $mysqlF->selectPage($principal,
					$this->getSqlForSelectEmails($principal, $status),
					$dbCS, 0, $maxNbJobs, $emailList);

			if(!$emailList->isEmpty()){

				// acquires lock
				$this->lock($principal, $emailList);

				// send the emails
				$config = array();
				$config["ssl"] = SMTP_ssl;
				$config["port"] = SMTP_port;
				$config["auth"] = SMTP_auth;

				if(defined("SMTP_username")) $config["username"] = SMTP_username; // fixed typo to username
				else $config["username"] = SMTP_userame; // old misspelled constant userame, kept for backward compatibility
				$config["password"] = SMTP_password;
				require_once('Zend/Mail/Transport/Smtp.php');
				require_once('Zend/Mail/Protocol/Smtp/Auth/Plain.php');
				require_once('Zend/Mail/Protocol/Smtp/Auth/Login.php');
				require_once('Zend/Mail/Protocol/Smtp/Auth/Crammd5.php');
				$tr = new Zend_Mail_Transport_Smtp(SMTP_host, $config);

				$succeed = array();
				$failure = array();
				$emails = $emailList->getListIterator();

				foreach($emails as $emailId=>$row){
					try{
						$mail = $this->getEmailInstanceFromRow($principal, $row);
						//change only in dev or preprod to not really send the email
						if(defined("REDIRECT_ALL_EMAILS_TO")){
							if(strpos($mail->getBodyHtmlForDb(), "</body></html>")){
								$mail->setBodyHtml(substr($mail->getBodyHtmlForDb(), 0, strpos($mail->getBodyHtmlForDb(), "</body></html>"))."<br /><br />".implode(", ", $mail->getRecipients())."</body></html>");
							} else {
								$mail->setBodyHtml($mail->getBodyHtmlForDb()."<br /><br />".implode(", ", $mail->getRecipients()));
							}
							$mail->clearRecipients();
							$mail->addTo(REDIRECT_ALL_EMAILS_TO);
						}
						$mail->send($tr);
						$succeed[$emailId] = time();
					} catch (Exception $e){
						$failure[$emailId] = time();
						// signals fatal error to monitoring system
						ServiceProvider::getClientAdminService()->signalFatalError($e);
					}
				}

				//on success remove attachements if needed
				//update the status, the timestamp
				$attachementToDelete = array();
				if($succeed){
					foreach($succeed as $emailId=>$time){
						$emails[$emailId]["status"] = "sent";
						$emails[$emailId]["lastUpdate"] = $time;
						$attachements = str2array($emails[$emailId]["attachement"]);
						if($attachements){
							foreach($attachements as $attachement){
								if($attachement["deleteFileAfterSend"]){
									$attachementToDelete[] = $attachement["path"];
								}
							}
						}
					}
				}
				//on fail inc the nb of try, update the timestamp, don't change the status, in next execution the email will be send again
				if($failure){
					foreach($failure as $emailId=>$time){
						$nbFailure = $emails[$emailId]["nbFailure"];
						if($nbFailure==="NULL" || $nbFailure == null){
							$nbFailure = 0;
						}
						$nbFailure++;
						$emails[$emailId]["nbFailure"] = $nbFailure;
						if($nbFailure>10){
							$emails[$emailId]["status"] = "failToSend";
						} else {
							$emails[$emailId]["status"] = "toSend";
						}
						$emails[$emailId]["lastUpdate"] = $time;
					}
				}


				$this->updateEmailsStatus($principal, $emails);
				//currently the emailAttachements are not deleted from the file system. Only the table emailServiceAttachementsToDelete is updated. If the column nb is to 0 then the file could be deleted from the file server
				$this->updateEmailsAttachements($principal, $attachementToDelete);

				$this->deleteObseleteEmailsAttachements($principal);

				$this->unlock($principal, $emailList);

				//if no success to send, try later, not directly
				if($succeed){
					return $totalJobs-count($succeed);
				} else {
					return 0;
				}
			}
			return 0;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("wakeup", $e, $principal);
			if($e->getCode() == AuthorizationServiceException::OBJECT_IS_LOCKED){
				//if lock, that means we are just in a concurent status. Which is not important to treat.
				//next time treatement will be well done
				echo "DB Entity was locked";
			} else {
				throw new EmailServiceException("Fail to wakeup email service",EmailServiceException::WRAPPING, $e);
			}
		}
		$this->executionSink()->publishEndOperation("wakeup", $principal);
	}

	protected function assertPrincipalForWakeup($principal){
		if(is_null($principal)) throw new ServiceException('principal can not be null', ServiceException::INVALID_ARGUMENT);

		$autoS = $this->getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($principal, "EmailService", "wakeup");
	}

	public function getEmailInstanceFromRow($principal, $dbRow){
		if($dbRow){
			//$this->debugLogger()->logBeginOperation("getEmailInstanceFromRow");
			$mail = $this->getEmailInstance($dbRow["charset"]);
			$mail->setOnlyforDb(false);
			$mail->setStatus($dbRow["status"]);
			$mail->setNbFailure(($dbRow["nbFailure"]==="NULL" ? 0 : $dbRow["nbFailure"]));
			$mail->setCreationDate($dbRow["creationDate"]);
			$mail->setLastUpdate($dbRow["lastUpdate"]);
			//put all recipients in to
			if($dbRow["to"]!=null){
				$recipients = str2array($dbRow["to"]);
				if($recipients){
					foreach($recipients as $email=>$label){
						$mail->addTo($email, $label);
					}
				}
			}
			if($dbRow["cc"]!=null){
				$recipients = str2array($dbRow["cc"]);
				if($recipients){
					foreach($recipients as $email=>$label){
						$mail->addCc($email, $label);
					}
				}
			}
			if($dbRow["bcc"]!=null){
				$recipients = str2array($dbRow["bcc"]);
				if($recipients){
					foreach($recipients as $email=>$label){
						$mail->addBcc($email, $label);
					}
				}
			}
			
			
			// Medair (CWE) 27.03.2017 if EmailService_sendOnBehalfOfUser is defined then doesn't use the user's email address as sender's email,
			// but uses the SMTP account as sender. User's email is put into the reply-to header.
			if(defined("EmailService_sendOnBehalfOfUser") && EmailService_sendOnBehalfOfUser) {
				// except if notification then always uses direct sending
				if($dbRow["from"] == (string)$this->getConfigService()->getParameter($principal, null, 'emailNotificationFrom')) {
					//$this->debugLogger()->write("Sending notification from ".$dbRow["from"]);
					$mail->setFrom($dbRow["from"]);
				}
				// except if user's email is defined as an Authorized direct sender, then puts the user's email directly in the from
				elseif(defined("EmailService_authorizedDirectSenders") && strpos(EmailService_authorizedDirectSenders, $dbRow["from"])!==false) {
					//$this->debugLogger()->write("Authorized direct sender: ".$dbRow["from"]);
					if($dbRow["replyTo"]!=null){
						$mail->setReplyTo($dbRow["replyTo"]);
					}
					$mail->setFrom($dbRow["from"]);
				}				 
				// else wraps it into the reply-to header.
				else {
					// defines from label as "sender's email via Service Name" or "sender's email via siteTitle"
					if(defined("EmailService_sendOnBehalfServiceName") && EmailService_sendOnBehalfServiceName) $fromLabel = EmailService_sendOnBehalfServiceName;
					else $fromLabel = (string)$this->getConfigService()->getParameter($principal, null, 'siteTitle');					
					$fromLabel = $dbRow["from"]." via ".$fromLabel;
					//$this->debugLogger()->write("Wrapping user's email: ".$fromLabel);
						
					// defines from address to emailingFrom config parameter or SMTP account if not defined
					$from = (string)$this->getConfigService()->getParameter($principal, null, 'emailingFrom');
					if(empty($from)) {
						if(defined("SMTP_username")) $from = SMTP_username; // fixed typo to username
						else $from = SMTP_userame; // old misspelled constant userame, kept for backward compatibility
					}
					$mail->setFrom($from, $fromLabel);
					
					
					// puts user's email in reply-to header, except if reply-to is already specified.
					if($dbRow["replyTo"]!=null){
						$mail->setReplyTo($dbRow["replyTo"]);
					}
					else $mail->setReplyTo($dbRow["from"]);
						
					// Medair (CWE) 27.03.2017: return-path is not set in order to avoid Spam filter blocking.
					// therefore bouncing email cannot be catched back by sender if not a direct sender.
					//$mail->setReturnPath($dbRow["from"]);
				}
			}
			// else sends the email with a from address equal to user's email.
			else {
				//$this->debugLogger()->write("Direct sending with user's email: ".$dbRow["from"]);
				if($dbRow["replyTo"]!=null){
					$mail->setReplyTo($dbRow["replyTo"]);
				}
				$mail->setFrom($dbRow["from"]);
			}
			
			$mail->setSubject($dbRow["subject"]);
			$mail->setBodyHtml($dbRow["bodyHtml"]);
			$mail->setBodyText($dbRow["bodyText"]);

			//eput($dbRow);
			if($dbRow["attachement"]!=null){
				$attachements = str2array($dbRow["attachement"]);
				if($attachements){
					foreach($attachements as $attach){
						$mail->createAttachment(
							$attach["path"],
							$attach["deleteFileAfterSend"],
							$attach["mimeType"],
							$attach["disposition"],
							$attach["encoding"],
							$attach["filename"]);
					}
				}
			}
			//$this->debugLogger()->write($mail->displayDebug());
			//$this->debugLogger()->logEndOperation("getEmailInstanceFromRow");
			return $mail;
		}
		return null;
	}

	/**
	 * Lock element or list of element
	 * @param object: Element or ElementPList or ElementList
	 */
	public function lock($principal, $object){
		return $this->getDbAdminService()->lock($principal, $this->getDbTablename(), $object);
	}

	/**
	 * UnLock element or list of element
	 * @param object: Element or ElementPList or ElementList
	 */
	public function unLock($principal, $object){
		$this->getDbAdminService()->unLock($principal, $this->getDbTablename(), $object);
	}


	protected function getEmailList(){
		return $this->createEmailListInstance();
	}
	protected function createEmailListInstance(){
		return new EmailList();
	}

}

class EmailList extends Model implements DbEntityList, RowList {

	protected $objArray;
	protected $ids;

	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	public function reset()
	{
		$this->freeMemory();
		$this->objArray = array();
	}
	public function freeMemory()
	{
		unset($this->objArray);
	}

	public function getListIterator()
	{
		return $this->objArray;
	}

	public function isEmpty()
	{
		return ($this->objArray == null);
	}

	public function count()
	{
		return (count($this->objArray));
	}

	public function addRow($row){
		$id = $row["id_email"];
		$this->ids[$id] = $id;
		$this->objArray[$id] = $row;
	}

	public function getIds(){
		return $this->ids;
	}
}


