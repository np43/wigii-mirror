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
REM Deletes an existing Wigii client from localhost
REM Created by CWE on 15.04.2019
REM 

set USAGE=%0 wigii_client
set RETURNVALUE=0
SET PREVIOUS_PATH=%CD%
cd %~dp0
echo Changes code page to UTF-8
chcp 65001

set WIGII_CLIENT=%1
if "%WIGII_CLIENT%"=="" (echo Wigii ERROR: Wigii client is not defined. Usage: %USAGE% & set RETURNVALUE=1004 & goto end)

set WIGII_ENV=..\..\..\..
set WIGII_WWW=%WIGII_ENV%\www\
if not exist %WIGII_WWW% (echo Wigii ERROR: www folder has not been found & set RETURNVALUE=404 & goto end)
set WIGII_USERS=%WIGII_ENV%\users\
if not exist %WIGII_USERS% (echo Wigii ERROR: users folder has not been found & set RETURNVALUE=404 & goto end)

rem retrieves Wigii DB name from start.php
for /F "tokens=3 delims=,) " %%a in ('findstr DB_NAME %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT%\start.php') do (set WIGII_DB=%%~a)
if "%WIGII_DB%"=="" (set WIGII_DB=wigii_%WIGII_CLIENT%)

if "%WIGII_MYSQL_ENV%"=="" (set WIGII_MYSQL_ENV=C:\wamp\bin\mysql\mysql5.7.24)
set MYSQL=%WIGII_MYSQL_ENV%\bin\mysql.exe
if not exist %MYSQL% (echo Wigii ERROR: %MYSQL% does not exist & set RETURNVALUE=404 & goto end)
if "%WIGII_MYSQL_ROOTPWD%"=="" (echo Wigii MySql root password is not set. Assumes empty string. If not, please set WIGII_MYSQL_ROOTPWD environment variable.)

:deleteUsers
echo Delete users folder of %WIGII_CLIENT%
rmdir %WIGII_USERS%%WIGII_CLIENT% /s /q

:deleteWww
echo Delete www folder of %WIGII_CLIENT%
rmdir %WIGII_WWW%%WIGII_CLIENT% /s /q

:deleteApi
echo Deletes api folder of %WIGII_CLIENT%
rmdir %WIGII_ENV%\data\wigiiSystem\core\api\impl\%WIGII_CLIENT% /s /q

:deleteConfigs
echo Delete config folder of %WIGII_CLIENT%
rmdir %WIGII_ENV%\data\wigiiSystem\configs\%WIGII_CLIENT% /s /q

:deleteDb
SET MYSQL_CMD=%MYSQL% --user=root --password=%WIGII_MYSQL_ROOTPWD% -e 
echo Deletes %WIGII_DB% database
%MYSQL_CMD% "DROP DATABASE `%WIGII_DB%`"
if %ERRORLEVEL% neq 0 goto mySqlError

goto end
:mySqlError
set RETURNVALUE=2501
echo Erreur de MySql
goto end
:end
REM clears all variables and exits with return value
set WIGII_CLIENT=
set WIGII_DB=
set WIGII_ENV=
set WIGII_WWW=
set WIGII_USERS=
SET WIGII_FX=
set MYSQL_CMD=
if not "%MYSQL_CMDFILE%"=="" del /Q %MYSQL_CMDFILE%
SET MYSQL_CMDFILE=
cd %PREVIOUS_PATH%
exit /b %RETURNVALUE%