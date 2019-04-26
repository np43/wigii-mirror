@echo off
REM
REM This file is part of Wigii (R) software.
REM Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
REM 
REM Wigii is free software: you can redistribute it and/or modify it 
REM under the terms of the GNU General Public License as published by
REM the Free Software Foundation, either version 3 of the License, 
REM or (at your option) any later version.
REM 
REM Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
REM without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
REM See the GNU General Public License for more details.
REM
REM A copy of the GNU General Public License is available in the Readme folder of the source code.  
REM If not, see <http://www.gnu.org/licenses/>.
REM
REM @copyright  Copyright (c) 2019  Wigii.org
REM @author     <http://www.wigii.org/system>      Wigii.org 
REM @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
REM @license    <http://www.gnu.org/licenses/>     GNU General Public License
REM

REM
REM Creates a new Wigii client on localhost
REM Created by CWE on 15.04.2019
REM 

set USAGE=%0 wigii_client [wigii_db] [wigii_superadmin]
set RETURNVALUE=0
SET PREVIOUS_PATH=%CD%
cd %~dp0
echo Changes code page to UTF-8
chcp 65001

set WIGII_CLIENT=%1
set WIGII_DB=%2
set WIGII_SUPERADMIN=%3
if "%WIGII_CLIENT%"=="" (echo Wigii ERREUR: Wigii client is not defined. Usage: %USAGE% & set RETURNVALUE=1004 & goto end)
if "%WIGII_DB%"=="" (set WIGII_DB=wigii_%WIGII_CLIENT%)
if "%WIGII_DB_USER%"=="" (set WIGII_DB_USER=wgi)
if "%WIGII_DB_PWD%"=="" (set WIGII_DB_PWD=mywgi-pass)
if "%WIGII_SUPERADMIN%"=="" (set WIGII_SUPERADMIN=%WIGII_CLIENT%.admin)
set WIGII_FX=ucfirst('%WIGII_CLIENT%')
for /F "delims=" %%a in ('wigii_cli.bat -shell -c NoClient fx "%WIGII_FX%"') do (set WIGII_CLIENT_LABEL=%%a)
set WIGII_FX=genPassword()
for /F "delims=" %%a in ('wigii_cli.bat -shell -c NoClient fx "%WIGII_FX%"') do (set WIGII_SUPERADMIN_PWD=%%a)

set WIGII_ENV=..\..\..\..
set WIGII_WWW=%WIGII_ENV%\www\
if not exist %WIGII_WWW% (echo Wigii ERROR: www folder has not been found & set RETURNVALUE=404 & goto end)
set WIGII_USERS=%WIGII_ENV%\users\
if not exist %WIGII_USERS% (echo Wigii ERROR: users folder has not been found & set RETURNVALUE=404 & goto end)

if "%WIGII_MYSQL_ENV%"=="" (set WIGII_MYSQL_ENV=C:\wamp\bin\mysql\mysql5.7.24)
set MYSQL=%WIGII_MYSQL_ENV%\bin\mysql.exe
if not exist %MYSQL% (echo Wigii ERROR: %MYSQL% does not exist & set RETURNVALUE=404 & goto end)
if "%WIGII_MYSQL_ROOTPWD%"=="" (echo Wigii MySql root password is not set. Assumes empty string. If not, please set WIGII_MYSQL_ROOTPWD environment variable.)
set WIGII_CREATEDBSQL="%WIGII_ENV%\Readme\Wigii4.6 table structure.sql"
for /F "tokens=* delims=" %%a in (%WIGII_CREATEDBSQL%) do (set WIGII_CREATEDBSQL=%%~fa)
if not exist "%WIGII_CREATEDBSQL%" (echo Wigii ERROR: File "%WIGII_CREATEDBSQL%" does not exist & set RETURNVALUE=1009 & goto end)
SET MYSQL_CMD=%MYSQL% --user=root --password=%WIGII_MYSQL_ROOTPWD% -e 
for /f "tokens=1-3 delims=. " %%a in ('date /T') do (set MYSQL_CMDFILE=%WIGII_DB%_%%c%%b%%a.sql)
for /f "tokens=1-3 delims=. " %%a in ('date /T') do (set MYSQL_OUTFILE=%WIGII_DB%_%%c%%b%%a.txt)
rem asserts MySql variable lower_case_table_names is set to 2 (keep table case, but ignore case on lookup)
%MYSQL_CMD% "charset utf8mb4;set names utf8mb4;show variables like '%%lower_case_table_names%%'" > %MYSQL_OUTFILE%
if %ERRORLEVEL% neq 0 goto mySqlError
for /F "tokens=2 delims=	" %%a in ('findstr /C:"lower_case_table_names" %MYSQL_OUTFILE%') do (
	if %%a LSS 2 (echo Wigii ERROR: MySql lower_case_table_names variable is %%a instead of 2. & echo Please set lower_case_table_names=2 in my.ini file. & set RETURNVALUE=1009 & goto end)
)

:createUsers
rem Creates users folder for new client
if exist %WIGII_USERS%%WIGII_CLIENT% (echo Wigii ERROR: users\%WIGII_CLIENT% folder already exists & set RETURNVALUE=405 & goto end)
echo Creates users\%WIGII_CLIENT% folder
mkdir %WIGII_USERS%%WIGII_CLIENT%
mkdir %WIGII_USERS%%WIGII_CLIENT%\data
mkdir %WIGII_USERS%%WIGII_CLIENT%\data\uploadedFiles

:createWww
rem Creates www folder for new client
if exist %WIGII_WWW%%WIGII_CLIENT% (echo Wigii ERROR: www\%WIGII_CLIENT% folder already exists & set RETURNVALUE=405 & goto end)
echo Creates www\%WIGII_CLIENT% folder
mkdir %WIGII_WWW%%WIGII_CLIENT%
echo Copies www\Example files to www\%WIGII_CLIENT%
xcopy %WIGII_WWW%Example\* %WIGII_WWW%%WIGII_CLIENT% /e /s
ren %WIGII_WWW%%WIGII_CLIENT%\Example.js %WIGII_CLIENT%.js
ren %WIGII_WWW%%WIGII_CLIENT%\Example.css %WIGII_CLIENT%.css

:createConfigs
rem Creates config folder for new client
if exist %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT% (echo Wigii ERROR: configs\%WIGII_CLIENT% folder already exists & set RETURNVALUE=405 & goto end)
echo Creates configs\%WIGII_CLIENT% folder
mkdir %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%
echo Copies configs\Example files to configs\%WIGII_CLIENT%
xcopy %WIGII_ENV%\data\wigiiSystem\configs\Example\* %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT% /e /s
ren %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\dico_Example.txt dico_%WIGII_CLIENT%.txt

:createApi
rem Creates api folder for new client
if exist %WIGII_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT% (echo Wigii ERROR: api\impl\%WIGII_CLIENT% folder already exists & set RETURNVALUE=405 & goto end)
echo Creates api\impl\%WIGII_CLIENT% folder
mkdir %WIGII_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT%
echo Copies api\impl\Example files to api\impl\%WIGII_CLIENT%
rem prepares ServiceProviderExampleImpl.php
(for /f "delims= eol=" %%a in (%WIGII_ENV%\data\wigiiSystem\core\api\impl\Example\ServiceProviderExampleImpl.php) do (	
	set ln=%%a
	Setlocal enableDelayedExpansion
	set emptyLn=!ln:	=!
	if not "!emptyLn!"=="" set emptyLn=!emptyLn: =!
	if not "!emptyLn!"=="" (
		set ln=!ln:Example=%WIGII_CLIENT_LABEL%!
		if not "!ln!"=="" echo !ln!
	)
	endlocal
))>%WIGII_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT%\ServiceProvider%WIGII_CLIENT_LABEL%Impl.php
rem prepares ServiceProviderCliExampleImpl.php
(for /f "delims= eol=" %%a in (%WIGII_ENV%\data\wigiiSystem\core\api\impl\Example\ServiceProviderCliExampleImpl.php) do (	
	set ln=%%a
	Setlocal enableDelayedExpansion
	set emptyLn=!ln:	=!
	if not "!emptyLn!"=="" set emptyLn=!emptyLn: =!
	if not "!emptyLn!"=="" (
		set ln=!ln:Example=%WIGII_CLIENT_LABEL%!
		if not "!ln!"=="" echo !ln!
	)
	endlocal
))>%WIGII_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT%\ServiceProviderCli%WIGII_CLIENT_LABEL%Impl.php
rem prepares ExampleWigiiExecutor.php
(for /f "delims= eol=" %%a in (%WIGII_ENV%\data\wigiiSystem\core\api\impl\Example\ExampleWigiiExecutor.php) do (	
	set ln=%%a
	Setlocal enableDelayedExpansion
	set emptyLn=!ln:	=!
	if not "!emptyLn!"=="" set emptyLn=!emptyLn: =!
	if not "!emptyLn!"=="" (
		set ln=!ln:Example=%WIGII_CLIENT_LABEL%!
		if not "!ln!"=="" echo !ln!
	)
	endlocal
))>%WIGII_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT%\%WIGII_CLIENT_LABEL%WigiiExecutor.php
rem prepares ExampleCliExecutor.php
(for /f "delims= eol=" %%a in (%WIGII_ENV%\data\wigiiSystem\core\api\impl\Example\ExampleCliExecutor.php) do (	
	set ln=%%a
	Setlocal enableDelayedExpansion
	set emptyLn=!ln:	=!
	if not "!emptyLn!"=="" set emptyLn=!emptyLn: =!
	if not "!emptyLn!"=="" (
		set ln=!ln:Example=%WIGII_CLIENT_LABEL%!
		if not "!ln!"=="" echo !ln!
	)
	endlocal
))>%WIGII_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT%\%WIGII_CLIENT_LABEL%CliExecutor.php

:createDb
echo Creates %WIGII_DB% database
SET MYSQL_CMD=%MYSQL% --user=root --password=%WIGII_MYSQL_ROOTPWD% -e 
%MYSQL_CMD% "charset utf8mb4;set names utf8mb4;CREATE DATABASE `%WIGII_DB%` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %ERRORLEVEL% neq 0 goto mySqlError
:createDbTables
rem creates db tables
copy "%WIGII_CREATEDBSQL%" %MYSQL_CMDFILE%
%MYSQL_CMD% "use %WIGII_DB%;set names utf8mb4 collate utf8mb4_unicode_ci;source %MYSQL_CMDFILE%;"
if %ERRORLEVEL% neq 0 goto mySqlError
:initializeDb
echo Initializes %WIGII_DB% database
set WIGII_CLIENT_MODULES=CMS;Contacts;Dimensions;Events;Filemanager;Scripts
copy NUL %MYSQL_CMDFILE%
echo INSERT INTO `Users` SET  >> %MYSQL_CMDFILE%
echo	`username` = 						"%WIGII_SUPERADMIN%",  >> %MYSQL_CMDFILE%
echo 	`wigiiNamespace` = 					NULL,  >> %MYSQL_CMDFILE%
echo 	`password` = 						md5("%WIGII_SUPERADMIN_PWD%"),  >> %MYSQL_CMDFILE%
echo 	`passwordLength` = 					char_length("%WIGII_SUPERADMIN_PWD%"),  >> %MYSQL_CMDFILE%
echo 	`canModifyOwnPassword` = 			TRUE,  >> %MYSQL_CMDFILE%
echo	`authenticationMethod` = 			"usual",  >> %MYSQL_CMDFILE%
echo 	`description` = 					CONCAT("%WIGII_CLIENT_LABEL% superadmin. Created on ",SYSDATE()),  >> %MYSQL_CMDFILE%
echo 	`sys_date` = 						UNIX_TIMESTAMP(),  >> %MYSQL_CMDFILE%
echo 	`sys_user` = 						1,  >> %MYSQL_CMDFILE%
echo	`isRole` = 							FALSE  >> %MYSQL_CMDFILE%
echo ;  >> %MYSQL_CMDFILE%

echo INSERT INTO `Users` SET  >> %MYSQL_CMDFILE%
echo 	`username` = 						"SuperAdmin",  >> %MYSQL_CMDFILE%
echo 	`wigiiNamespace` = 					NULL,  >> %MYSQL_CMDFILE%
echo 	`password` = 						md5(CONCAT("SuperAdmin",UNIX_TIMESTAMP())),  >> %MYSQL_CMDFILE%
echo	`passwordLength` = 					0,  >> %MYSQL_CMDFILE%
echo 	`canModifyOwnPassword` = 			TRUE,  >> %MYSQL_CMDFILE%
echo	`authenticationMethod` = 			"usual",  >> %MYSQL_CMDFILE%
echo 	`description` = 					CONCAT("SuperAdmin role. Created on ",SYSDATE()),  >> %MYSQL_CMDFILE%
echo 	`wigiiNamespaceCreator` = 			TRUE,  >> %MYSQL_CMDFILE%
echo 	`moduleEditor` = 					TRUE,  >> %MYSQL_CMDFILE%
echo 	`readAllUsersInWigiiNamespace` = 	TRUE,  >> %MYSQL_CMDFILE%
echo 	`adminCreator` = 					TRUE,  >> %MYSQL_CMDFILE%
echo 	`userCreator` = 					TRUE,  >> %MYSQL_CMDFILE%
echo 	`moduleAccess` =  					"Admin;%WIGII_CLIENT_MODULES%",  >> %MYSQL_CMDFILE%
echo 	`readAllGroupsInWigiiNamespace` = 	"%WIGII_CLIENT_MODULES%",  >> %MYSQL_CMDFILE%
echo 	`rootGroupCreator` = 				"%WIGII_CLIENT_MODULES%",  >> %MYSQL_CMDFILE%
echo 	`groupCreator` = 					"%WIGII_CLIENT_MODULES%",  >> %MYSQL_CMDFILE%
echo 	`sys_date` = 						UNIX_TIMESTAMP(),  >> %MYSQL_CMDFILE%
echo 	`sys_user` = 						1,  >> %MYSQL_CMDFILE%
echo 	`isRole` = 							TRUE  >> %MYSQL_CMDFILE%
echo ;  >> %MYSQL_CMDFILE%

echo INSERT INTO `Users_Users` (`id_relation_user` ,`id_user_owner` ,`id_user` ,`isOwner` ,`hasRole`)  >> %MYSQL_CMDFILE%
echo VALUES (NULL , '1', '2', NULL , '1'), (NULL , '1', '2', '1' , NULL);  >> %MYSQL_CMDFILE%

%MYSQL_CMD% "use %WIGII_DB%;set names utf8mb4 collate utf8mb4_unicode_ci;source %MYSQL_CMDFILE%;"
if %ERRORLEVEL% neq 0 goto mySqlError
:initializeStartPhp
echo Initializes start.php
ren %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php start-temp.php
(for /f "delims= eol=" %%a in (%WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start-temp.php) do (	
	set ln=%%a
	Setlocal enableDelayedExpansion
	set emptyLn=!ln:	=!
	if not "!emptyLn!"=="" set emptyLn=!emptyLn: =!
	if not "!emptyLn!"=="" (
		rem Changes WigiiExecutor class name
		set ln=!ln:Example=%WIGII_CLIENT_LABEL%!
		rem replaces DB_HOST by localhost
		set tempLn=!ln:DB_HOST=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_HOST", "localhost");")
		rem replaces DB_USER by WIGII_DB_USER
		set tempLn=!ln:DB_USER=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_USER", "%WIGII_DB_USER%");")
		rem replaces DB_PWD by WIGII_DB_PWD
		set tempLn=!ln:DB_PWD=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_PWD", "%WIGII_DB_PWD%");")
		rem replaces DB_NAME by WIGII_DB
		set tempLn=!ln:DB_NAME=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_NAME", "%WIGII_DB%");")
		if not "!ln!"=="" echo !ln!
	)
	endlocal
))>%WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php
del /Q %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start-temp.php

echo Initializes start_cli.php
ren %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli.php start_cli-temp.php
(for /f "delims= eol=" %%a in (%WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli-temp.php) do (	
	set ln=%%a
	Setlocal enableDelayedExpansion
	set emptyLn=!ln:	=!
	if not "!emptyLn!"=="" set emptyLn=!emptyLn: =!
	if not "!emptyLn!"=="" (
		rem Changes WigiiExecutor class name
		set ln=!ln:Example=%WIGII_CLIENT_LABEL%!
		rem replaces DB_HOST by localhost
		set tempLn=!ln:DB_HOST=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_HOST", "localhost");")
		rem replaces DB_USER by WIGII_DB_USER
		set tempLn=!ln:DB_USER=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_USER", "%WIGII_DB_USER%");")
		rem replaces DB_PWD by WIGII_DB_PWD
		set tempLn=!ln:DB_PWD=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_PWD", "%WIGII_DB_PWD%");")
		rem replaces DB_NAME by WIGII_DB
		set tempLn=!ln:DB_NAME=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_NAME", "%WIGII_DB%");")
		if not "!ln!"=="" echo !ln!
	)
	endlocal
))>%WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli.php
del /Q %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli-temp.php

goto end
:mySqlError
set RETURNVALUE=2501
echo Erreur de MySql
goto end
:end
if %RETURNVALUE% GTR 0 (
	echo Error during client creation. Run wigii_deleteClient %WIGII_CLIENT% to cleanup, then correct error and retry.
) else (
	echo ________________________________________________
	echo New client %WIGII_CLIENT% successfully created.
	echo Do not forget to adapt the .htaccess and index.php files to declare %WIGII_CLIENT% url.
	echo Do not forget to add a %WIGII_CLIENT% alias to your Apache server pointing on the Wigii www folder.
	echo Do not forget to adapt the data\wigiiSystem\configs\%WIGII_CLIENT%\start.php with the Wigii DB user and password.
	echo Then connect to Wigii web interface with %WIGII_SUPERADMIN% / %WIGII_SUPERADMIN_PWD%
)
REM clears all variables and exits with return value
set WIGII_CLIENT=
set WIGII_CLIENT_MODULES=
set WIGII_DB=
set WIGII_DB_USER=
set WIGII_DB_PWD=
set WIGII_SUPERADMIN=
set WIGII_SUPERADMIN_PWD=
set WIGII_ENV=
set WIGII_WWW=
set WIGII_USERS=
SET WIGII_FX=
set MYSQL_CMD=
if exist %MYSQL_CMDFILE% del /Q %MYSQL_CMDFILE%
SET MYSQL_CMDFILE=
if exist %MYSQL_OUTFILE% del /Q %MYSQL_OUTFILE%
SET MYSQL_OUTFILE=
cd %PREVIOUS_PATH%
exit /b %RETURNVALUE%