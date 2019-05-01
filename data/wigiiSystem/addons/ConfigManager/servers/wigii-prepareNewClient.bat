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
REM This script prepares a new client for go live on a Wigii server
REM Created by CWE on 18.04.2019
REM 

set USAGE=%0 wigii_client
set RETURNVALUE=0
SET PREVIOUS_PATH=%CD%
cd %~dp0
echo Changes code page to UTF-8
chcp 65001

set WIGII_CLIENT=%1
if "%WIGII_CLIENT%"=="" (echo Wigii ERROR: Wigii client is not defined. Usage: %USAGE% & set RETURNVALUE=1004 & goto end)
if "%WIGII_HOST%"=="" (echo Wigii ERROR: WIGII_HOST is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_SERVER%"=="" (echo Wigii ERROR: WIGII_SERVER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_CERTIFICATE%"=="" (echo Wigii ERROR: WIGII_CERTIFICATE is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_USER%"=="" (echo Wigii ERROR: WIGII_USER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_PWD%"=="" (echo Wigii ERROR: WIGII_PWD is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_HOST_INDEX%"=="" (echo Wigii ERROR: WIGII_HOST_INDEX is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_HOST_HTACCESS%"=="" (echo Wigii ERROR: WIGII_HOST_HTACCESS is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_ADMIN_HOME%"=="" (echo Wigii ERROR: WIGII_ADMIN_HOME is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
IF %WIGII_ADMIN_HOME:~-1%==\ SET WIGII_ADMIN_HOME=%WIGII_ADMIN_HOME:~0,-1%
set LOCAL_HTACCESS=%~dp0
IF %LOCAL_HTACCESS:~-1%==\ SET LOCAL_HTACCESS=%LOCAL_HTACCESS:~0,-1%
set LOCAL_HTACCESS=%LOCAL_HTACCESS%\htaccess


rem checks installation of WinSCP
if exist "%ProgramFiles%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles%\WinSCP\WinSCP.exe") else (
if exist "%ProgramFiles(x86)%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles(x86)%\WinSCP\WinSCP.exe") else (
	echo Wigii ERROR: WinScp is not installed & set RETURNVALUE=404 & goto end
))

rem asserts working folder does not commit to GitHub Wigii
for /f "tokens=1 delims=" %%a in ('svn info %LOCAL_HTACCESS% ^| find /C "https://github.com/wigii/wigii"') do (
	if %%a GTR 0 (echo Wigii ERROR: Cannot commit %WIGII_HOST% host information to GitHub/Wigii & set RETURNVALUE=405 & goto end)
)

rem checks that WIGII_HOST_INDEX points to index. and that WIGII_HOST_HTACCESS points to .htaccess
if %WIGII_HOST_INDEX:index.php=% == %WIGII_HOST_INDEX% (echo Wigii ERROR: WIGII_HOST_INDEX does not point to index.php. & set RETURNVALUE=1009 & goto end)
if %WIGII_HOST_HTACCESS:.htaccess=% == %WIGII_HOST_HTACCESS% (echo Wigii ERROR: WIGII_HOST_HTACCESS does not point to htaccess. & set RETURNVALUE=1009 & goto end)

rem prepares Wigii server connexion string
set WIGII_CONNEXION=open ftps://%WIGII_USER%:%WIGII_PWD%@%WIGII_SERVER% -explicit -certificate="%WIGII_CERTIFICATE%"
set WINSCP_CMD=%WINSCP% /log=.\winscp.log /loglevel=0 /logsize=5*2M /command

:checkProdPackage
echo Checks %WIGII_HOST%-prod folder structure
set WIGII_TARGET=%~dp0%WIGII_HOST%-prod
if not exist %WIGII_TARGET% (echo Wigii ERROR: %WIGII_HOST%-prod is not correctly setup. & set RETURNVALUE=1009 & goto end)
call %~dp0%WIGII_HOST%-setup.bat %WIGII_TARGET%
if "%WIGII_TARGET_WEB%"=="" (echo Wigii ERROR: WIGII_TARGET_WEB is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_TARGET_ENV%"=="" (echo Wigii ERROR: WIGII_TARGET_ENV is not defined. & set RETURNVALUE=1009 & goto end)

:versionHtaccess
echo Versions htaccess and index.php files from %WIGII_HOST%
rem creates Wigii host htaccess prod folder
if not exist %LOCAL_HTACCESS%\%WIGII_HOST%-prod (mkdir %LOCAL_HTACCESS%\%WIGII_HOST%-prod & svn add %LOCAL_HTACCESS%\%WIGII_HOST%-prod & svn commit -m "Create htaccess %WIGII_HOST%-prod folder" %LOCAL_HTACCESS%\%WIGII_HOST%-prod)
rem svn updates Wigii host htaccess folder
echo SVN updates %WIGII_HOST%-prod htaccess folder
svn update %LOCAL_HTACCESS%\%WIGII_HOST%-prod
rem downloads Wigii host htaccess and index.php files
echo Download %WIGII_HOST% htaccess and index.php files
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "lcd %LOCAL_HTACCESS%\%WIGII_HOST%-prod" ^
 "get -transfer=binary %WIGII_HOST_INDEX% %WIGII_HOST_HTACCESS% .\" ^
 "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError
rem find all files not yet into svn and add them 
for /f "tokens=2" %%a in ('svn status %LOCAL_HTACCESS%\%WIGII_HOST%-prod ^| findstr ?') do (svn add %%a)
rem commit changes
svn commit -m "Commited production htaccess and index.php from %WIGII_HOST%" %LOCAL_HTACCESS%\%WIGII_HOST%-prod

:prepareHtaccess
echo Copies htaccess and index.php production files to %WIGII_HOST%-prod package web folder
copy /y %LOCAL_HTACCESS%\%WIGII_HOST%-prod\index.php %WIGII_TARGET_WEB%\index.php

if not "%WIGII_TARGET_HTACCESS%"=="" (set "WIGII_TARGET_HTACCESS=%WIGII_TARGET%\%WIGII_TARGET_HTACCESS:\.htaccess=%")
if "%WIGII_TARGET_HTACCESS%"=="" (set WIGII_TARGET_HTACCESS=%WIGII_TARGET_WEB%)
copy /y "%LOCAL_HTACCESS%\%WIGII_HOST%-prod\.htaccess" "%WIGII_TARGET_HTACCESS%\.htaccess"

:prepareDbInfo
echo Prepares production database informations for client %WIGII_CLIENT% on %WIGII_HOST%
if not exist %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start-dev.php (echo Wigii ERROR: start-dev.php has not been found for client %WIGII_CLIENT%. Please rebuild a client package. & set RETURNVALUE=404 & goto end)
if "%WIGII_DB_HOST%"=="" (set /P WIGII_DB_HOST="Database host: ")
if "%WIGII_DB_HOST%"=="" (set /P WIGII_DB_HOST="Database host: ")
if "%WIGII_DB_HOST%"=="" (echo Wigii ERROR: WIGII_DB_HOST is not defined. & set RETURNVALUE=1004 & goto end)
set /P WIGII_DB="Database name: "
if "%WIGII_DB%"=="" (set /P WIGII_DB="Database name: ")
if "%WIGII_DB%"=="" (echo Wigii ERROR: WIGII_DB is not defined. & set RETURNVALUE=1004 & goto end)
set /P WIGII_DB_USER="Database user: "
if "%WIGII_DB_USER%"=="" (set /P WIGII_DB_USER="Database user: ")
if "%WIGII_DB_USER%"=="" (echo Wigii ERROR: WIGII_DB_USER is not defined. & set RETURNVALUE=1004 & goto end)
set /P WIGII_DB_PWD="Database password: "
if "%WIGII_DB_PWD%"=="" (set /P WIGII_DB_PWD="Database password: ")
if "%WIGII_DB_PWD%"=="" (echo Wigii ERROR: WIGII_DB_PWD is not defined. & set RETURNVALUE=1004 & goto end)

:preparesStartPHP
echo Prepares start.php
if not exist %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start-dev.php (echo Wigii ERROR: start-dev.php has not been found for client %WIGII_CLIENT%. & set RETURNVALUE=404 & goto end)
(for /f "delims= eol=" %%a in (%WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start-dev.php) do (	
	set ln=%%a
	Setlocal enableDelayedExpansion
	set emptyLn=!ln:	=!
	if not "!emptyLn!"=="" set emptyLn=!emptyLn: =!
	if not "!emptyLn!"=="" (
		rem replaces DB_HOST by WIGII_DB_HOST
		set tempLn=!ln:DB_HOST=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_HOST", "%WIGII_DB_HOST%");")
		rem replaces DB_USER by WIGII_DB_USER
		set tempLn=!ln:DB_USER=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_USER", "%WIGII_DB_USER%");")
		rem replaces DB_PWD by WIGII_DB_PWD
		set tempLn=!ln:DB_PWD=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_PWD", "%WIGII_DB_PWD%");")
		rem replaces DB_NAME by WIGII_DB
		set tempLn=!ln:DB_NAME=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_NAME", "%WIGII_DB%");")
		rem deactivates email redirection
		set tempLn=!ln:REDIRECT_ALL_EMAILS_TO=!
		if not "!tempLn!"=="!ln!" (set "ln=//!ln!")
		rem enables again box integration
		set tempLn=!ln:DISABLE_BOX_INTEGRATION=!
		if not "!tempLn!"=="!ln!" (set "ln=//!ln!")
		if not "!ln!"=="" echo !ln!
	)
	endlocal
))>%WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php
del /Q %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start-dev.php

if not exist %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli-dev.php goto endPreparesStartPHP
echo Prepares start_cli.php
(for /f "delims= eol=" %%a in (%WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli-dev.php) do (	
	set ln=%%a
	Setlocal enableDelayedExpansion
	set emptyLn=!ln:	=!
	if not "!emptyLn!"=="" set emptyLn=!emptyLn: =!
	if not "!emptyLn!"=="" (
		rem replaces DB_HOST by WIGII_DB_HOST
		set tempLn=!ln:DB_HOST=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_HOST", "%WIGII_DB_HOST%");")
		rem replaces DB_USER by WIGII_DB_USER
		set tempLn=!ln:DB_USER=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_USER", "%WIGII_DB_USER%");")
		rem replaces DB_PWD by WIGII_DB_PWD
		set tempLn=!ln:DB_PWD=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_PWD", "%WIGII_DB_PWD%");")
		rem replaces DB_NAME by WIGII_DB
		set tempLn=!ln:DB_NAME=!
		if not "!tempLn!"=="!ln!" (set "ln=define ("DB_NAME", "%WIGII_DB%");")
		rem deactivates email redirection
		set tempLn=!ln:REDIRECT_ALL_EMAILS_TO=!
		if not "!tempLn!"=="!ln!" (set "ln=//!ln!")
		if not "!ln!"=="" echo !ln!
	)
	endlocal
))>%WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli.php
del /Q %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli-dev.php
:endPreparesStartPHP

:updateMySqlDumpScript
if not exist %WIGII_ADMIN_HOME%\db-dump\%WIGII_HOST%-prod\%WIGII_HOST%-mysqldump.sh goto endUpdateMySqlDumpScript
echo Updates mysqldump script with new client database
for /f "tokens=3 delims=:" %%a in ('find /C "%WIGII_DB%" %WIGII_ADMIN_HOME%\db-dump\%WIGII_HOST%-prod\%WIGII_HOST%-mysqldump.sh') do (if %%a GTR 0 goto endUpdateMySqlDumpScript)
echo mysqldump --user=%WIGII_DB_USER% --password="%WIGII_DB_PWD%" --host=%WIGII_DB_HOST% --default-character-set=utf8mb4 -r "wigii_%WIGII_CLIENT%_$TIMESTAMP.sql" %WIGII_DB%>>%WIGII_ADMIN_HOME%\db-dump\%WIGII_HOST%-prod\%WIGII_HOST%-mysqldump.sh
rem commit changes
svn commit -m "Added database %WIGII_DB% for client %WIGII_CLIENT% to db-dump script" %WIGII_ADMIN_HOME%\db-dump\%WIGII_HOST%-prod\%WIGII_HOST%-mysqldump.sh
:endUpdateMySqlDumpScript

echo _________________________________________________
echo Done. New client %WIGII_CLIENT% is ready for deployement.
echo Do not forget to adapt the .htaccess and index.php files with the %WIGII_CLIENT% url before pushing package live.
echo To import the initial database dump use the script %WIGII_HOST%-initialDbImport.bat
goto end
:winScpError
set RETURNVALUE=10303
echo Erreur de communication WinScp
goto end
:end
REM clears all variables and exits with return value
set LOCAL_HTACCESS=
set WIGII_HOST=
set WIGII_HOST_INDEX=
set WIGII_HOST_HTACCESS=
set WIGII_TARGET_HTACCESS=
set WIGII_SERVER=
set WIGII_CERTIFICATE=
set WIGII_USER=
set WIGII_PWD=
set WIGII_DB=
set WIGII_DB_HOST=
set WIGII_DB_USER=
set WIGII_DB_PWD=
set WINSCP=
set WINSCP_CMD=
cd %PREVIOUS_PATH%
exit /b %RETURNVALUE%