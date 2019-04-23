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
REM Creates a client package from the local dev environment and prepares it for go live
REM Created by CWE on 15.04.2019
REM 

set USAGE=%0 wigii_client [-noData]
set RETURNVALUE=0
SET PREVIOUS_PATH=%CD%
cd %~dp0
echo Changes code page to UTF-8
chcp 65001

set WIGII_CLIENT=%1
if "%2"=="-noData" (set WIGII_OPTION_NODATA=1) else (set WIGII_OPTION_NODATA=0)
if "%WIGII_CLIENT%"=="" (echo Wigii ERROR: Wigii client is not defined. Usage: %USAGE% & set RETURNVALUE=1004 & goto end)
if "%WIGII_HOST%"=="" (echo Wigii ERROR: WIGII_HOST is not defined. & set RETURNVALUE=1009 & goto end)

if "%WIGII_ENV%"=="" (echo Wigii ERROR: WIGII_ENV is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
IF %WIGII_ENV:~-1%==\ SET WIGII_ENV=%WIGII_ENV:~0,-1%

set WIGII_WWW=%WIGII_ENV%\www\
if not exist %WIGII_WWW% (echo Wigii ERROR: www folder has not been found & set RETURNVALUE=404 & goto end)
set WIGII_USERS=%WIGII_ENV%\users\
if not exist %WIGII_USERS% (echo Wigii ERROR: users folder has not been found & set RETURNVALUE=404 & goto end)

rem retrieves Wigii DB name from start.php
for /F "tokens=3 delims=,) " %%a in ('findstr DB_NAME %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php') do (set WIGII_DB=%%~a)
if "%WIGII_DB%"=="" (set WIGII_DB=wigii_%WIGII_CLIENT%)

rem checks installation of mysql tools
if "%WIGII_MYSQL_ENV%"=="" (echo Wigii ERROR: WIGII_MYSQL_ENV is not defined. Call %~nx0 from USER-adminConsole.bat & set RETURNVALUE=1009 & goto end)
set MYSQL=%WIGII_MYSQL_ENV%\bin\mysql.exe
if not exist %MYSQL% (echo Wigii ERROR: %MYSQL% does not exist & set RETURNVALUE=404 & goto end)
set MYSQL_DUMP=%WIGII_MYSQL_ENV%\bin\mysqldump.exe
if not exist %MYSQL_DUMP% (echo Wigii ERROR: %MYSQL_DUMP% does not exist & set RETURNVALUE=404 & goto end)
if "%WIGII_MYSQL_ROOTPWD%"=="" (echo Wigii MySql root password is not set. Assumes empty string. If not, please set WIGII_MYSQL_ROOTPWD environment variable.)

rem checks installation of 7-Zip
if exist "%ProgramFiles%\7-Zip\7z.exe" (set P7ZIP="%ProgramFiles%\7-Zip\7z.exe") else (
if exist "%ProgramFiles(x86)%\7-Zip\7z.exe" (set P7ZIP="%ProgramFiles(x86)%\7-Zip\7z.exe") else (
	rem 7-Zip is not mandatory for now.
	set NO_P7ZIP=1
))

for /f "tokens=1-3 delims=. " %%a in ('date /T') do (set TIMESTAMP=%%c%%b%%a)

echo Setups %WIGII_HOST%-prod folder structure
set WIGII_TARGET=%~dp0%WIGII_HOST%-prod
rmdir %WIGII_TARGET% /s /q
mkdir %WIGII_TARGET%
call %WIGII_HOST%-setup.bat %WIGII_TARGET%
if "%WIGII_TARGET_WEB%"=="" (echo Wigii ERROR: WIGII_TARGET_WEB is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_TARGET_ENV%"=="" (echo Wigii ERROR: WIGII_TARGET_ENV is not defined. & set RETURNVALUE=1009 & goto end)
mkdir %WIGII_TARGET_WEB%
mkdir %WIGII_TARGET_ENV%

echo Copies users\%WIGII_CLIENT% folder
mkdir %WIGII_TARGET_ENV%\users\%WIGII_CLIENT%
xcopy %WIGII_USERS%%WIGII_CLIENT%\* %WIGII_TARGET_ENV%\users\%WIGII_CLIENT% /e /s
if "%WIGII_OPTION_NODATA%"=="1" (rmdir %WIGII_TARGET_ENV%\users\%WIGII_CLIENT% /s /q & mkdir %WIGII_TARGET_ENV%\users\%WIGII_CLIENT%\data\uploadedFiles)
echo Copies www\%WIGII_CLIENT% folder
mkdir %WIGII_TARGET_WEB%\%WIGII_CLIENT%
xcopy %WIGII_WWW%%WIGII_CLIENT%\* %WIGII_TARGET_WEB%\%WIGII_CLIENT% /e /s

echo Copies configs\%WIGII_CLIENT% folder
mkdir %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%
xcopy %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\* %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT% /e /s
ren %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php start-dev.php
ren %WIGII_TARGET_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start_cli.php start_cli-dev.php

echo Copies api\impl\%WIGII_CLIENT% folder
mkdir %WIGII_TARGET_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT%
xcopy %WIGII_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT%\* %WIGII_TARGET_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT% /e /s

if "%WIGII_OPTION_NODATA%"=="1" goto zipPackage
:dumpsDb
echo Dumps %WIGII_DB% database
mkdir %WIGII_TARGET_ENV%\db-dump
%MYSQL_DUMP% --user=root --password=%WIGII_MYSQL_ROOTPWD% --default-character-set=utf8mb4 -r "%WIGII_TARGET_ENV%\db-dump\%WIGII_DB%_%TIMESTAMP%.sql" %WIGII_DB%
if %ERRORLEVEL% neq 0 goto mySqlError

:zipPackage
if "%NO_P7ZIP%"=="1" (echo 7-Zip is not installed, package will not be zipped) else (
	del /Q %WIGII_CLIENT%_%TIMESTAMP%-dev.zip
	%P7ZIP% a -tzip %WIGII_CLIENT%_%TIMESTAMP%-dev.zip %WIGII_TARGET%
)

goto end
:mySqlError
set RETURNVALUE=2501
echo Erreur de MySql
goto end
:end
REM clears all variables and exits with return value
set WIGII_OPTION_NODATA=
set WIGII_CLIENT=
set WIGII_HOST=
set WIGII_TARGET=
set WIGII_TARGET_WEB=
set WIGII_TARGET_ENV=
set WIGII_WWW=
set WIGII_USERS=
cd %PREVIOUS_PATH%
exit /b %RETURNVALUE%