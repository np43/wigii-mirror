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
* Created on 24 nov. 2009
* by LWR
*/
require_once('Zend/Mail.php');

class WigiiEmailWebImpl extends Zend_Mail implements WigiiEmail {
	
	public function __construct($charset = 'iso-8859-1') {
        $this->_charset = $charset;
        $this->setCreationDate(time());
    }
    
	private $creationDate = null;
	public function setCreationDate($creationDate){ $this->creationDate = $creationDate; }
	public function getCreationDate(){ return $this->creationDate; }
	
	private $lastUpdate = null;
	public function setLastUpdate($lastUpdate){ $this->lastUpdate = $lastUpdate; }
	public function getLastUpdate(){ return $this->lastUpdate; }
	
	private $status = null;
	public function setStatus($status){ $this->status = $status; }
	public function getStatus(){ return $this->status; }
	
	private $nbFailure = 0;
	public function setNbFailure($nbFailure){ $this->nbFailure = $nbFailure; }
	public function getNbFailure(){ return $this->nbFailure; }
	
	private $onlyForDb = true;
	public function setOnlyforDb($bool){ $this->onlyForDb = $bool; }
	public function isOnlyForDb(){ return $this->onlyForDb; }
	 
	private $to;
	public function getToForDb(){
		return $this->to;
	}
	public function setTo($array){ $this->to = $array; }
	public function addTo($email, $name=''){
		$this->to[$email] = $name;
		if(!$this->isOnlyForDb()) parent::addTo($email, $name);
	}
	private $cc;
	public function getCcForDb(){
		return $this->cc;
	}
	public function setCc($array){ $this->cc = $array; }
	public function addCc($email, $name=''){
		$this->cc[$email] = $name;
		if(!$this->isOnlyForDb()) parent::addCc($email, $name);
	}
	private $bcc;
	public function getBccForDb(){
		return $this->bcc;
	}
	public function setBcc($array){ $this->bcc = $array; }
	public function addBcc($email, $name=''){
		$this->bcc[$email] = $name;
		if(!$this->isOnlyForDb()) parent::addBcc($email, $name);
	}
	private $replyTo;
	public function getReplyToForDb(){
		return $this->replyTo;
	}
	public function setReplyTo($email, $name=null){
		$this->replyTo = $email;
		if(!$this->isOnlyForDb()) parent::setReplyTo($email, $name);
	}
	
	public function clearRecipients(){
		$this->to = null;
		$this->cc = null;
		$this->bcc = null;
		if(!$this->isOnlyForDb()) parent::clearRecipients();
	}
	
	public function hasRecipients(){
		return $this->to != null || $this->cc != null || $this->bcc!=null;
	}
	
	private $bodyHtml;
	public function getBodyHtmlForDb(){
		return $this->bodyHtml;
	}
	public function setBodyHtml($html, $charset = null, $encoding = WigiiEmailMime::ENCODING_QUOTEDPRINTABLE){
		$html = str_replace(array("></p>", "<p>", "http://http"), array('>&nbsp;</p>', '<p style="margin:0px;">', "http"), $html);
		$this->bodyHtml = $html;
		if(!$this->isOnlyForDb()) parent::setBodyHtml($html, $charset, $encoding);
	}
	
	private $bodyText;
	public function getBodyTextForDb(){
		return $this->bodyText;
	}
	public function setBodyText($txt, $charset = null, $encoding = WigiiEmailMime::ENCODING_QUOTEDPRINTABLE){
		$this->bodyText = $txt;
		if(!$this->isOnlyForDb()) parent::setBodyText($txt, $charset, $encoding);
	}
	
	private $subject = null;
	public function getSubjectForDb(){
		return $this->subject;
	}
	public function setSubject($subject){
		$this->subject = $subject;
		return parent::setSubject(html_entity_decode(stripslashes($subject), ENT_COMPAT, "UTF-8"));
	}
	public function clearSubject(){
		$this->subject = null;
		return parent::clearSubject();
	}
	
	private $attachements;
	private $attachI;
	public function getAttachementsForDb(){
		return $this->attachements;
	}
	/**
	 * Set a file path for attachement
     * @param  string         $path
     * @param  boolean        $deleteAfterSend=false, if true the file is deleted from the disk after the email is successfully sent
     * @param  string         $mimeType
     * @param  string         $disposition
     * @param  string         $encoding
     * @param  string         $filename OPTIONAL A filename for the attachment
     * @return void
	 */
	public function createAttachment($path, $deleteFileAfterSend=false,
		$mimeType    = WigiiEmailMime::TYPE_OCTETSTREAM,
		$disposition = WigiiEmailMime::DISPOSITION_ATTACHMENT,
		$encoding    = WigiiEmailMime::ENCODING_BASE64,
		$filename    = null){
		if(!isset($this->attachements)){
			$this->attachements = array();
			$this->attachI = 0;
		}
		$this->attachements[$this->attachI] = array();
		$this->attachements[$this->attachI]["path"] = $path;
		$this->attachements[$this->attachI]["deleteFileAfterSend"] = $deleteFileAfterSend;
		$this->attachements[$this->attachI]["mimeType"] = $mimeType;
		$this->attachements[$this->attachI]["disposition"] = $disposition;
		$this->attachements[$this->attachI]["encoding"] = $encoding;
		$this->attachements[$this->attachI]["filename"] = $filename;
		$this->attachI++;
		//file content is no more stored in the email. only the file path is stored.
		if(!$this->isOnlyForDb()) parent::createAttachment(@file_get_contents($path), $mimeType, $disposition, $encoding, $filename);
	}
	
	public function mergeData($data){
		if(!$data) return;
		$subject =  $this->getSubjectForDb();
		$this->clearSubject();
		$keys = array_keys($data);
		$values = array_values($data);
		$subject = str_replace($keys, $values, $subject);
		$this->setSubject(str_replace($keys, $values, $subject));
		$this->setBodyHtml(str_replace($keys, $values, $this->getBodyHtmlForDb()));
		$this->setBodyText(str_replace($keys, $values, $this->getBodyTextForDb()));
	}
	
	
	//copy paste from Model. in php cannot do multiple extend...
	public function displayDebug($nb = 0, $noHTML=false){
		global $private_put_objectDone;
		if($nb == 0) $private_put_objectDone = array();
		
		$idObj = spl_object_hash($this);
		$name = "CLASS: ".get_class($this); //since php 5.2 we cannot find the object id... ." ID:".str_replace("Object id", "", "".$this);
		//$vars = get_object_vars($this);
		
		if($noHTML) $nbspText = " ";
		else $nbspText = " &nbsp; ";
		
		$nbSp = 2;

		$returnValue = "";
		$returnValue .= str_repeat("|".str_repeat($nbspText, $nbSp),$nb).$name;

		//if the object is already displayed, then do nothing to not make recursion
		if($private_put_objectDone[$idObj] != null){
			return $returnValue." (already displayed)\n";
		}
		$returnValue .= "\n";
		$private_put_objectDone[$idObj] = $idObj;
		
		$vars = (array)$this; //like this we really take everything including the heritage
		foreach ($vars as $var_name => $var) {
			//the var_name contains a very strange character just after the class definition
			//so we use that to cut off the name
			$returnValue .= str_repeat("|".str_repeat($nbspText, $nbSp),$nb).preg_replace('/^.*[^a-zA-Z0-9_-]/',"", $var_name);
			$retour_ligne = "";
			if ((is_array($var) || is_object($var)) && $var != null) {
				$returnValue .= " :\n";
				//$retour_ligne = "\n";
			} else {
				$returnValue .= " = ";
				$retour_ligne = "\n";
			}
//			eput($var);
//			eput(is_object($var));
//			eput(method_exists($var, "displayDebug"));
			if(is_object($var) && method_exists($var, "displayDebug")){
				$returnValue .= $var->displayDebug($nb+1, $noHTML).$retour_ligne;
			} else {
				$returnValue .= private_put($var, null, false, $nb+1, $noHTML).$retour_ligne;
			}
		}
		return $returnValue;
	}
}
