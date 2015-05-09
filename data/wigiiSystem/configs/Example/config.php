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

//******************
/* CONFIG FIL */
//******************

//*******************
//* SMTP parameters */
//*******************
define ("SMTP_host", "mail.xxx.xx");
define ("SMTP_userame", "mailer@xxx.xx");
define ("SMTP_password", "xxx");
define ("SMTP_ssl", "tls"); 						//SMTP_ssl:  "" : tls : ssl
define ("SMTP_port", "587"); 						//SMTP_port: tls=25/587 : ssl=465
define ("SMTP_auth", "plain"); 						//SMTP_auth: plain : logic : crammd5
define ("EmailService_maxRecipients", 15);			//Max number of recipients per email. If more, the email is splitted and duplicated

//*********************
//* Emails parameters */
//*********************
define ("EMAIL_postfix", "@xxx.xx");  
define ("EMAIL_defaultPop3Server", "mail.xxx.xx");  

/**********************
 * LANGUAGE parameters 
 * 10 language are available
 * from l01 to l10
 * you need to set the visible languages 
 * and their respective label.
 **********************/
define("DEFAULT_LANGUAGE", "l01");
ServiceProvider::getTranslationService()->setVisibleLanguage(array("l01"=>"English", "l02"=>"Français"));

//*********************
/* DEFAULT parameters */
//*********************
define ("USERNAME_maxLength", 160);
define ("USERNAME_minLength", 2);
define ("GROUPNAME_maxLength", USERNAME_maxLength);
define ("GROUPNAME_minLength", USERNAME_minLength);
define ("PASSWORD_maxLength", 32);
define ("PASSWORD_minLength", 3);


