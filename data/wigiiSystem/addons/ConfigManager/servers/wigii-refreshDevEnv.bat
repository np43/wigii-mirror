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

set WIGII_CLIENT=%1
set USAGE=%0 wigii_client
set RETURNVALUE=0
echo Changes code page to UTF-8
chcp 65001

if "%WIGII_CLIENT%"=="" (echo Wigii ERREUR: Wigii client is not defined. Usage: %USAGE% & set RETURNVALUE=1009 & goto end)
if "%WIGII_HOST%"=="" (echo Wigii ERREUR: WIGII_HOST is not defined. & set RETURNVALUE=1009 & goto end)

if "%WIGII_ADMIN_HOME%"=="" (echo Wigii ERREUR: WIGII_ADMIN_HOME is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
IF %WIGII_ADMIN_HOME:~-1%==\ SET WIGII_ADMIN_HOME=%WIGII_ADMIN_HOME:~0,-1%
if "%WIGII_ENV%"=="" (echo Wigii ERREUR: WIGII_ENV is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
IF %WIGII_ENV:~-1%==\ SET WIGII_ENV=%WIGII_ENV:~0,-1%

rem checks installation of mysql tools
if "%WIGII_MYSQL_ENV%"=="" (echo Wigii ERREUR: WIGII_MYSQL_ENV is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
set MYSQL=%WIGII_MYSQL_ENV%\bin\mysql.exe
if not exist %MYSQL% (echo Wigii ERREUR: %MYSQL% does not exist & set RETURNVALUE=404 & goto end)
if "%WIGII_MYSQL_ROOTPWD%"=="" (echo Wigii MySql root password is not set. Assumes empty string. If not, please set WIGII_MYSQL_ROOTPWD environment variable.)

:getClientProdConfig
echo Get latest production configuration files for %WIGII_CLIENT%
call %WIGII_ADMIN_HOME%\configs\%WIGII_HOST%-versionProdConfig.bat %WIGII_CLIENT%
if %ERRORLEVEL% neq 0 (RETURNVALUE=%ERRORLEVEL% & goto end)

:getClientProdDb
echo Get latest production database for %WIGII_CLIENT%
call %WIGII_ADMIN_HOME%\db-dump\%WIGII_HOST%-getDb.bat %WIGII_CLIENT%
if %ERRORLEVEL% neq 0 (RETURNVALUE=%ERRORLEVEL% & goto end)
set WIGII_DB_DUMP=%WIGII_DB%
if "%WIGII_DB_DUMP%"=="" (echo Wigii ERREUR: No db dump downloaded from production for %WIGII_CLIENT% & set RETURNVALUE=404 & goto end)

:prepareProdConfig
rem checks that dev environment exists for client
if not exist %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT% (echo Wigii ERREUR: no development environment found for %WIGII_CLIENT%. Run wigii_createClient %WIGII_CLIENT% to create one and then retry & set RETURNVALUE=1009 & goto end)
echo Setups %WIGII_HOST%-prod folder structure
set WIGII_TARGET=%~dp0%WIGII_HOST%-prod
rmdir %WIGII_TARGET% /s /q
mkdir %WIGII_TARGET%
call %~dp0%WIGII_HOST%-setup.bat %WIGII_TARGET%
if "%WIGII_TARGET_WEB%"=="" (echo Wigii ERREUR: WIGII_TARGET_WEB is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_TARGET_ENV%"=="" (echo Wigii ERREUR: WIGII_TARGET_ENV is not defined. & set RETURNVALUE=1009 & goto end)
mkdir %WIGII_TARGET_WEB%
mkdir %WIGII_TARGET_ENV%

echo Prepares configs\%WIGII_CLIENT% folder from production
mkdir %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%
xcopy %WIGII_ADMIN_HOME%\configs\%WIGII_CLIENT%-prod\* %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT% /e /s
ren %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php start-prod.php
ren %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli.php start_cli-prod.php

:importsDb
rem checks that db dump exists before imports
if "%WIGII_DB_DUMP%"=="" (echo Wigii ERREUR: no db dump to import for %WIGII_CLIENT% & set RETURNVALUE=404 & goto end)
if not exist %WIGII_ADMIN_HOME%\db-dump\%WIGII_HOST%-prod\%WIGII_DB_DUMP% (echo Wigii ERREUR: no db dump to import for %WIGII_CLIENT% & set RETURNVALUE=404 & goto end)
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

echo Done. Dev env %WIGII_CLIENT% refreshed from production.
goto end
:mySqlError
set RETURNVALUE=2501
echo Erreur de MySql
goto end
:end
REM clears all variables and exits with return value
set MYSQL_CMD=
if not "%MYSQL_CMDFILE%"=="" del /Q %MYSQL_CMDFILE%
SET MYSQL_CMDFILE=
exit /b %RETURNVALUE%