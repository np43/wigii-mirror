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
* Created on 24 nov. 2009
* by LWR
*/

interface WigiiEmail {
	
    /**
     * Public constructor
     *
     * @param string $charset
     */
    public function __construct($charset = 'iso-8859-1');
    
    /**
     * Return charset string
     *
     * @return string
     */
    public function getCharset();
    
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
		$filename    = null);
	
	/**
     * Adds To-header and recipient
     *
     * @param  string $email
     * @param  string $name
     * @return void
     */
    public function addTo($email, $name='');
    
    /**
     * Adds Cc-header and recipient
     *
     * @param  string    $email
     * @param  string    $name
     * @return void
     */
    public function addCc($email, $name='');
    
    /**
     * Adds Bcc recipient
     *
     * @param  string    $email
     * @return void
     */
    public function addBcc($email);
    
	/**
     * Return list of recipient email addresses
     *
     * @return array (of strings)
     */
    public function getRecipients();
    
    /**
     * Clears list of recipient email addresses
     */
    public function clearRecipients();
    
    /**
     * Return true if there is at least a to cc or bcc email address
     *
     * @return array (of strings)
     */
    public function hasRecipients();
    
    /**
     * Set Reply-To Header
     *
     * @param string $email
     * @param string $name
     * @return void
     */
    public function setReplyTo($email, $name=null);
    
    /**
     * Sets From-header and sender of the message
     *
     * @param  string    $email
     * @param  string    $name
     * @return void
     */
    public function setFrom($email, $name = null);
    
    /**
     * Returns the sender of the mail
     *
     * @return string
     */
    public function getFrom();
    
    /**
     * Clears the sender from the mail
     */
    public function clearFrom();
    
    /**
     * Sets the subject of the message
     *
     * @param   string    $subject
     * @return  void
     */
    public function setSubject($subject);
    
    /**
     * Returns the encoded subject of the message
     *
     * @return string
     */
    public function getSubject();
    
    /**
     * Clears the encoded subject from the message
     */
    public function clearSubject();
    
    
    /**
     * Sets the HTML body for the message
     *
     * @param  string    $html
     * @param  string    $charset
     * @param  string    $encoding
     * @return void
     */
    public function setBodyHtml($html, $charset = null, $encoding = WigiiEmailMime::ENCODING_QUOTEDPRINTABLE);
    
    /**
     * Return body HTML
     *
     * @return false|string
     */
    public function getBodyHtml();
    
    /**
     * Sets the text body for the message.
     *
     * @param  string $txt
     * @param  string $charset
     * @param  string $encoding
     * @return void
    */
    public function setBodyText($txt, $charset = null, $encoding = WigiiEmailMime::ENCODING_QUOTEDPRINTABLE);
    
    /**
     * Return text body
     *
     * @return false|string
     */
    public function getBodyText();
    
    /**
     * Sends this email
     *
     * @param  Object $object
     * @return void
     */
    public function send($object = null);
}
