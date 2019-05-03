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
REM This script downloads a Wigii client config folder from the wigii server and commits the files to svn.
REM Created by CWE on 11.04.2019
REM 

set WIGII_CLIENT=%1
rem set WIGII_SERVER=ftp.server.com
rem set WIGII_CERTIFICATE="xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx"
rem set WIGII_USER=xxxx
rem set WIGII_PWD=xxxx
rem set LOCAL_CONFIGS=%~dp0
set USAGE=%0 wigii_client
set RETURNVALUE=0
echo Changes code page to UTF-8
chcp 65001

if "%WIGII_CLIENT%"=="" (echo Wigii ERROR: Wigii client is not defined. Usage: %USAGE% & set RETURNVALUE=1009 & goto end)
if "%WIGII_SERVER%"=="" (echo Wigii ERROR: WIGII_SERVER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_CERTIFICATE%"=="" (echo Wigii ERROR: WIGII_CERTIFICATE is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_USER%"=="" (echo Wigii ERROR: WIGII_USER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_PWD%"=="" (echo Wigii ERROR: WIGII_PWD is not defined. & set RETURNVALUE=1009 & goto end)
if "%LOCAL_CONFIGS%"=="" (set LOCAL_CONFIGS=%~dp0)
IF %LOCAL_CONFIGS:~-1%==\ SET LOCAL_CONFIGS=%LOCAL_CONFIGS:~0,-1%

rem checks Wigii client is set
if "%WIGII_CLIENT%"=="" (echo Wigii ERROR: Wigii client is not defined. Usage: %USAGE% & set RETURNVALUE=1009 & goto end)
rem checks installation of WinSCP
if exist "%ProgramFiles%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles%\WinSCP\WinSCP.exe") else (
if exist "%ProgramFiles(x86)%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles(x86)%\WinSCP\WinSCP.exe") else (
	echo Wigii ERROR: WinScp is not installed & set RETURNVALUE=404 & goto end
))

rem asserts working folder does not commit to GitHub Wigii
for /f "tokens=1 delims=" %%a in ('svn info %LOCAL_CONFIGS% ^| find /C "https://github.com/wigii/wigii"') do (
	if %%a GTR 0 (echo Wigii ERROR: Cannot commit %WIGII_CLIENT% information to GitHub/Wigii & set RETURNVALUE=405 & goto end)
)

rem creates Wigii client config prod folder
if not exist %LOCAL_CONFIGS%\%WIGII_CLIENT%-prod (mkdir %LOCAL_CONFIGS%\%WIGII_CLIENT%-prod & svn add %LOCAL_CONFIGS%\%WIGII_CLIENT%-prod & svn commit -m "Create %WIGII_CLIENT%-prod folder" %LOCAL_CONFIGS%\%WIGII_CLIENT%-prod)

rem prepares Wigii server connexion string
set WIGII_CONNEXION=open ftps://%WIGII_USER%:%WIGII_PWD%@%WIGII_SERVER% -explicit -certificate="%WIGII_CERTIFICATE%"
set WINSCP_CMD=%WINSCP% /log=%LOCAL_CONFIGS%\winscp.log /loglevel=0 /logsize=5*2M /command

rem svn updates Wigii client config folder
echo SVN updates %WIGII_CLIENT%-prod config folder
svn update %LOCAL_CONFIGS%\%WIGII_CLIENT%-prod

rem downloads Wigii client config files
echo Download %WIGII_CLIENT% config files
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "synchronize local -delete -transfer=binary -nopermissions %LOCAL_CONFIGS%\%WIGII_CLIENT%-prod %WIGII_CLIENT%" ^
 "get -transfer=binary %WIGII_CLIENT%/* %LOCAL_CONFIGS%\%WIGII_CLIENT%-prod\" ^
 "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError

rem find all files not yet into svn and add them 
for /f "tokens=2" %%a in ('svn status %LOCAL_CONFIGS%\%WIGII_CLIENT%-prod ^| findstr ?') do (svn add %%a)

rem find all deleted files and removes then from svn
for /f "tokens=2" %%a in ('svn status %LOCAL_CONFIGS%\%WIGII_CLIENT%-prod ^| findstr "!"') do (svn delete %%a)

rem commit changes
svn commit -m "Commited production configs from %WIGII_CLIENT%" %LOCAL_CONFIGS%\%WIGII_CLIENT%-prod

goto end
:winScpError
set RETURNVALUE=10303
echo Erreur de communication WinScp
goto end
:end
REM clears all variables and exits with return value
set WIGII_SERVER=
set WIGII_CERTIFICATE=
set WIGII_USER=
set WIGII_PWD=
set WIGII_CONNEXION=
set WINSCP=
set WINSCP_CMD=
exit /b %RETURNVALUE%