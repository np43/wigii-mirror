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
REM This script refreshes an existing client development environment from production server. Configuration files and database are refreshed.
REM Created by CWE on 16.04.2019
REM 

set USAGE=%0 wigii_client [-noDb]
set RETURNVALUE=0
SET PREVIOUS_PATH=%CD%
cd %~dp0
echo Changes code page to UTF-8
chcp 65001

set WIGII_CLIENT=%1
if "%2"=="-noDb" (set WIGII_OPTION_NODB=1) else (set WIGII_OPTION_NODB=0)
if "%WIGII_CLIENT%"=="" (echo Wigii ERROR: Wigii client is not defined. Usage: %USAGE% & set RETURNVALUE=1009 & goto end)
if "%WIGII_HOST%"=="" (echo Wigii ERROR: WIGII_HOST is not defined. & set RETURNVALUE=1009 & goto end)

if "%WIGII_ADMIN_HOME%"=="" (echo Wigii ERROR: WIGII_ADMIN_HOME is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
IF %WIGII_ADMIN_HOME:~-1%==\ SET WIGII_ADMIN_HOME=%WIGII_ADMIN_HOME:~0,-1%
if "%WIGII_ENV%"=="" (echo Wigii ERROR: WIGII_ENV is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
IF %WIGII_ENV:~-1%==\ SET WIGII_ENV=%WIGII_ENV:~0,-1%
set WIGII_WWW=%WIGII_ENV%\www
if not exist %WIGII_WWW% (echo Wigii ERROR: www folder has not been found & set RETURNVALUE=404 & goto end)
set WIGII_USERS=%WIGII_ENV%\users
if not exist %WIGII_USERS% (echo Wigii ERROR: users folder has not been found & set RETURNVALUE=404 & goto end)

rem checks installation of mysql tools
if "%WIGII_MYSQL_ENV%"=="" (echo Wigii ERROR: WIGII_MYSQL_ENV is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
set MYSQL=%WIGII_MYSQL_ENV%\bin\mysql.exe
if not exist %MYSQL% (echo Wigii ERROR: %MYSQL% does not exist & set RETURNVALUE=404 & goto end)
if "%WIGII_MYSQL_ROOTPWD%"=="" (echo Wigii MySql root password is not set. Assumes empty string. If not, please set WIGII_MYSQL_ROOTPWD environment variable.)

rem checks installation of WinSCP
if exist "%ProgramFiles%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles%\WinSCP\WinSCP.exe") else (
if exist "%ProgramFiles(x86)%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles(x86)%\WinSCP\WinSCP.exe") else (
	echo Wigii ERROR: WinScp is not installed & set RETURNVALUE=404 & goto end
))
rem prepares Wigii server connexion string
set WIGII_CONNEXION=open ftps://%WIGII_USER%:%WIGII_PWD%@%WIGII_SERVER% -explicit -certificate="%WIGII_CERTIFICATE%"
set WINSCP_CMD=%WINSCP% /log=winscp.log /loglevel=0 /logsize=5*2M /command

:getClientProdConfig
echo Get latest production configuration files for %WIGII_CLIENT%
rem uses start /B /W cmd /c ... instead of call ... to make sure environment variables are kept on completion.
start /B /W "%WIGII_HOST%-versionProdConfig.bat" cmd /c %WIGII_ADMIN_HOME%\configs\%WIGII_HOST%-versionProdConfig.bat %WIGII_CLIENT%
if %ERRORLEVEL% neq 0 (RETURNVALUE=%ERRORLEVEL% & goto end)

if "%WIGII_OPTION_NODB%"=="1" goto prepareProdConfig
:getClientProdDb
echo Get latest production database for %WIGII_CLIENT%
rem backups WIGII_CONNEXION and WINSCP_CMD
set WIGII_CONNEXION_BACKUP=%WIGII_CONNEXION%
set WINSCP_CMD_BACKUP=%WINSCP_CMD%
call %WIGII_ADMIN_HOME%\db-dump\%WIGII_HOST%-getDb.bat %WIGII_CLIENT%
if %ERRORLEVEL% neq 0 (RETURNVALUE=%ERRORLEVEL% & goto end)
set WIGII_CONNEXION=%WIGII_CONNEXION_BACKUP%
set WINSCP_CMD=%WINSCP_CMD_BACKUP%
set WIGII_DB_DUMP=%WIGII_DB%
if "%WIGII_DB_DUMP%"=="" (echo Wigii ERROR: No db dump downloaded from production for %WIGII_CLIENT% & set RETURNVALUE=404 & goto end)

:prepareProdConfig
rem checks that dev environment exists for client
if not exist %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT% (echo Wigii ERROR: no development environment found for %WIGII_CLIENT%. Run wigii_createClient %WIGII_CLIENT% to create one and then retry & set RETURNVALUE=1009 & goto end)
echo Setups %WIGII_HOST%-prod folder structure
set WIGII_TARGET=%~dp0%WIGII_HOST%-prod
rmdir %WIGII_TARGET% /s /q
mkdir %WIGII_TARGET%
call %~dp0%WIGII_HOST%-setup.bat %WIGII_TARGET%
if "%WIGII_TARGET_WEB%"=="" (echo Wigii ERROR: WIGII_TARGET_WEB is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_TARGET_ENV%"=="" (echo Wigii ERROR: WIGII_TARGET_ENV is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_HOST_WEB%"=="" (echo Wigii ERROR: WIGII_HOST_WEB is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_HOST_ENV%"=="" (echo Wigii ERROR: WIGII_HOST_ENV is not defined. & set RETURNVALUE=1009 & goto end)
mkdir %WIGII_TARGET_WEB%
mkdir %WIGII_TARGET_ENV%

echo Prepares configs\%WIGII_CLIENT% folder from production
mkdir %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%
xcopy %WIGII_ADMIN_HOME%\configs\%WIGII_CLIENT%-prod\* %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT% /e /s
ren %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php start-prod.php
ren %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli.php start_cli-prod.php

:getClientProdImplFiles
echo Downloads api\impl\%WIGII_CLIENT% files
mkdir %WIGII_TARGET_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT%
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "lcd %WIGII_TARGET_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT%" ^
 "get -transfer=binary %WIGII_HOST_ENV%/data/wigiiSystem/core/api/impl/%WIGII_CLIENT%/*.?* .\" ^
  "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError
echo Downloads www\%WIGII_CLIENT% files
mkdir %WIGII_TARGET_WEB%\%WIGII_CLIENT%
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "lcd %WIGII_TARGET_WEB%\%WIGII_CLIENT%" ^
 "get -transfer=binary %WIGII_HOST_WEB%/%WIGII_CLIENT%/*.?* .\" ^
  "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError

:getClientProdUserAddonFolders
echo Downloads %WIGII_CLIENT% user addon folders
rem supports: Medidata addon. (uses wildcard selector to not fail if not exist)
mkdir %WIGII_TARGET_ENV%\users\%WIGII_CLIENT%
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "lcd %WIGII_TARGET_ENV%\users\%WIGII_CLIENT%" ^
 "get -transfer=binary -filemask=*/|*.?*;*/data/uploadedFiles/*;*/data/uploadedFiles/*/ %WIGII_HOST_ENV%/users/%WIGII_CLIENT%/Medi* .\" ^
  "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError

if "%WIGII_OPTION_NODB%"=="1" goto refreshDevConfig
:importsDb
rem checks that db dump exists before imports
if "%WIGII_DB_DUMP%"=="" (echo Wigii ERROR: no db dump to import for %WIGII_CLIENT% & set RETURNVALUE=404 & goto end)
if not exist %WIGII_ADMIN_HOME%\db-dump\%WIGII_HOST%-prod\%WIGII_DB_DUMP% (echo Wigii ERROR: no db dump to import for %WIGII_CLIENT% & set RETURNVALUE=404 & goto end)
rem retrieves Wigii DB name from start.php
for /F "tokens=3 delims=,) " %%a in ('findstr DB_NAME %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php') do (set WIGII_DB=%%~a)
if "%WIGII_DB%"=="" (set WIGII_DB=wigii_%WIGII_CLIENT%)

echo Imports %WIGII_DB% database from %WIGII_DB_DUMP%
for /f "tokens=1-3 delims=. " %%a in ('date /T') do (set MYSQL_CMDFILE=%WIGII_DB%_%%c%%b%%a.sql)
SET MYSQL_CMD=%MYSQL% --user=root --password=%WIGII_MYSQL_ROOTPWD% -e 
copy "%WIGII_ADMIN_HOME%\db-dump\%WIGII_HOST%-prod\%WIGII_DB_DUMP%" %MYSQL_CMDFILE%
%MYSQL_CMD% "use %WIGII_DB%;set names utf8mb4 collate utf8mb4_unicode_ci;source %MYSQL_CMDFILE%;"
if %ERRORLEVEL% neq 0 goto mySqlError

:refreshDevConfig
echo Refreshes dev configs\%WIGII_CLIENT%
rem backups temporarily dev start.php and start_cli.php
copy /Y %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%
copy /Y %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli.php %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%
rem empties dev config folder
del /Q %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\*
rem copies prod config to dev folder
xcopy %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\* %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT% /e /s /y
del /Q %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start-prod.php
del /Q %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli-prod.php
ren %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php start-dev.php
ren %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli.php start_cli-dev.php

:refreshDevImpl
echo Refreshes dev api\impl\%WIGII_CLIENT%
copy /Y %WIGII_TARGET_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT%\*.* %WIGII_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT%
echo Refreshes dev www\%WIGII_CLIENT%
copy /Y %WIGII_TARGET_WEB%\%WIGII_CLIENT%\*.* %WIGII_WWW%\%WIGII_CLIENT%

:refreshDevUser
echo Refreshes dev user
xcopy %WIGII_TARGET_ENV%\users\%WIGII_CLIENT%\* %WIGII_USERS%\%WIGII_CLIENT% /e /s

if "%WIGII_OPTION_NODB%"=="1" echo Done. Dev env %WIGII_CLIENT% refreshed from production (except database).
if not "%WIGII_OPTION_NODB%"=="1" echo Done. Dev env %WIGII_CLIENT% refreshed from production.
goto end
:mySqlError
set RETURNVALUE=2501
echo Erreur de MySql
goto end
:winScpError
set RETURNVALUE=10303
echo Erreur de communication WinScp
goto end
:end
REM clears all variables and exits with return value
set WIGII_OPTION_NODB=
set MYSQL_CMD=
if not "%MYSQL_CMDFILE%"=="" del /Q %MYSQL_CMDFILE%
SET MYSQL_CMDFILE=
set WIGII_SERVER=
set WIGII_CERTIFICATE=
set WIGII_USER=
set WIGII_PWD=
set WINSCP=
set WINSCP_CMD=
set WINSCP_CMD_BACKUP=
set WIGII_CONNEXION=
set WIGII_CONNEXION_BACKUP=
set WIGII_HOST=
set WIGII_HOST_WEB=
set WIGII_HOST_ENV=
set WIGII_WWW=
set WIGII_USERS=
set WIGII_TARGET=
set WIGII_TARGET_WEB=
set WIGII_TARGET_ENV=
cd %PREVIOUS_PATH%
exit /b %RETURNVALUE%