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

Here are the installation steps:

-------------------------------------------
-- MySql database setup
-------------------------------------------
- run the wigii4 table structure sql script
- insert in the DB a first Example user and the SuperAdmin role with this sql script:

INSERT INTO `Users` SET
	`username` = 						"Example",
	`wigiiNamespace` = 					NULL,
	`password` = 						md5("myPass"),
	`passwordLength` = 					6,
	`canModifyOwnPassword` = 			TRUE,
	`authenticationMethod` = 			"usual",
	`description` = 					CONCAT("First user. Created on ",SYSDATE()),
	`sys_date` = 						UNIX_TIMESTAMP(),
	`sys_user` = 						1,
	`isRole` = 							FALSE
;

INSERT INTO `Users` SET
	`username` = 						"SuperAdmin",
	`wigiiNamespace` = 					NULL,
	`password` = 						md5(CONCAT("SuperAdmin",UNIX_TIMESTAMP())),
	`passwordLength` = 					0,
	`canModifyOwnPassword` = 			TRUE,
	`authenticationMethod` = 			"usual",
	`description` = 					CONCAT("Main SuperAdmin. Created on ",SYSDATE()),
	`wigiiNamespaceCreator` = 			TRUE,
	`moduleEditor` = 					TRUE,
	`readAllUsersInWigiiNamespace` = 	TRUE,
	`adminCreator` = 					TRUE,
	`userCreator` = 					TRUE,
	`moduleAccess` =  					"Admin;Contacts;Events;Filemanager;Projects",
	`readAllGroupsInWigiiNamespace` = 	"Contacts;Events;Filemanager;Projects",
	`rootGroupCreator` = 				"Contacts;Events;Filemanager;Projects",
	`groupCreator` = 					"Contacts;Events;Filemanager;Projects",
	`sys_date` = 						UNIX_TIMESTAMP(),
	`sys_user` = 						1,
	`isRole` = 							TRUE
;

INSERT INTO `Users_Users` (`id_relation_user` ,`id_user_owner` ,`id_user` ,`isOwner` ,`hasRole`)
VALUES (NULL , '1', '2', NULL , '1'), (NULL , '1', '2', '1' , NULL);


-------------------------------------------
-- index.php
-------------------------------------------
	- rename example.index.php in index.php
	- replace the xxx.xx with the hostname from your website
	- if you want to change the ClientName then you need to copy each folders named Example and renaming them with the new clientName.
	- Note: by default the GlobalStatistics are enabled. That means that any action in the system is stored in this table without any automatic cleanup. This is to let you be able to have a track of what happens. You can manually clean it sometimes if you want.

-------------------------------------------
-- .htaccess
-------------------------------------------
	- rename example.htaccess in .htaccess
	- the .htaccess is made to work on localhost with an alias localhost/wigii/ or on a normal website pointing directly on wigii as: http://wigii.example.ch

-------------------------------------------
-- Config files
-------------------------------------------
- change the files in data/wigiiSystem/configs/Example/
	- config.php, 
		- replace the xxx.xx with correct values (the system need a pop3 email account to send emails with.
		- change the default language: l01: english, l02: french
	- config.xml
		- replace the xxx.xx with correct values
		- define your custom banner color and title
	- start.php
		- replace the ...... witht the correct mysql datbase configuration
		- if you are in development uncomment REDIRECT_ALL_EMAILS_TO and all emails of the notification will be redirected to this capture adress with the list of the recipients added in the top of the message
	- you can make changes in any of the other config files to match you personal needs

-------------------------------------------
-- Folder rights
-------------------------------------------	
- add 777 rights on the following folders:
	- data\wigiiSystem\configs\Example
	- users\Example\data\uploadedFiles
	- www\tempUploadedFiles
	- www\temporary_unzipForViewing
	- www\ClientFolder
	- www\ClientFolder\imageForHtmlEditor


Installation completed :) !

log you with: Example / myPass
	


