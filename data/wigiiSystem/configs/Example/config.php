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
// Config version G99

//******************
/* CONFIG FIL */
//******************

//*******************
//* SMTP parameters */
//*******************
define ("SMTP_host", "mail.xxx.xx");
define ("SMTP_username", "mailer@xxx.xx");
define ("SMTP_password", "xxx");
define ("SMTP_ssl", "tls"); 						//SMTP_ssl:  "" : tls : ssl
define ("SMTP_port", "587"); 						//SMTP_port: tls=25/587 : ssl=465
define ("SMTP_auth", "plain"); 						//SMTP_auth: plain : login: crammd5
define ("EmailService_maxRecipients", 15);			//Max number of recipients per email. If more, the email is splitted and duplicated
// Medair 27.03.2017: EmailService_sendOnBehalfOfUser: true or false or undefined 
// if true, SMTP account will always send emails from address defined in emailingFrom (default as reply@xxx.xx) with real sender's email set into reply-to header.
// else if false or not defined, SMTP account will send emails using sender's email directly set into the from header (this option can be blocked by server or anti-spam filters)
//define ("EmailService_sendOnBehalfOfUser", true); 
//define ("EmailService_sendOnBehalfServiceName", "Wigii system"); // If EmailService_sendOnBehalfOfUser=true, then defines the name of the service through which the email is posted on behalf of the user. By default, takes the siteTitle.

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

/*****************************************
 * Additional programmatic configuration
 *****************************************/
if(!TEST_ON) {
	//ServiceProvider::getConfigService()->setDumpConfig(true);
	ServiceProvider::getClientAdminService()->configureMonitoring(ObjectConfigurator::createInstance(array('monitorFatalError' => true)));
	
	// Medair 16.08.2016: if Files uploads to Box then show a warning message displaying in which box folder file is uploaded. (useful for testing and dev environments)
	//ServiceProvider::configureClass('BoxServiceFormExecutor', ObjectConfigurator::createInstance(array('setShowWarningIfBoxUpload' => true)));
	
	// Configures FuncExpVM modules
	//ServiceProvider::configureClass('FuncExpVM', ObjectConfigurator::createInstance(array('setBootstrapModules' => array('GeometryCliFL', 'Geometry2DFL'))));
	
	// Enables Approval system for non admin users
	//ServiceProvider::configureClass('ElementPolicyEvaluatorBaseImpl', ObjectConfigurator::createInstance(array('enableApprovalForNonAdminUser' => true)));
}