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
REM This script downloads one specific Wigii database
REM Created by CWE on 11.04.2019
REM 

set WIGII_CLIENT=%1
rem set WIGII_HOST=wwwigii-system
rem set WIGII_SERVER=ftp.server.com
rem set WIGII_CERTIFICATE="xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx"
rem set WIGII_USER=xxxx
rem set WIGII_PWD=xxxx
rem set LOCAL_DBDUMP=%~dp0
rem set WIGII_DBDUMP=wigii/db-dump
set USAGE=%0 wigii_client
set RETURNVALUE=0
echo Changes code page to UTF-8
chcp 65001

if "%WIGII_CLIENT%"=="" (echo Wigii ERREUR: Wigii client is not defined. Usage: %USAGE% & set RETURNVALUE=1009 & goto end)
if "%WIGII_HOST%"=="" (echo Wigii ERREUR: WIGII_HOST is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_SERVER%"=="" (echo Wigii ERREUR: WIGII_SERVER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_CERTIFICATE%"=="" (echo Wigii ERREUR: WIGII_CERTIFICATE is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_USER%"=="" (echo Wigii ERREUR: WIGII_USER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_PWD%"=="" (echo Wigii ERREUR: WIGII_PWD is not defined. & set RETURNVALUE=1009 & goto end)
if "%LOCAL_DBDUMP%"=="" (set LOCAL_DBDUMP=%~dp0)
if "%WIGII_DBDUMP%"=="" (set WIGII_DBDUMP=wigii/db-dump & echo WIGII_DBDUMP is not defined. Sets it to %WIGII_DBDUMP%)
IF %LOCAL_DBDUMP:~-1%==\ SET LOCAL_DBDUMP=%LOCAL_DBDUMP:~0,-1%

rem checks installation of WinSCP
if exist "%ProgramFiles%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles%\WinSCP\WinSCP.exe") else (
if exist "%ProgramFiles(x86)%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles(x86)%\WinSCP\WinSCP.exe") else (
	echo Wigii ERREUR: WinScp is not installed & set RETURNVALUE=404 & goto end
))
rem checks presence of plink.exe
set PLINK=%LOCAL_DBDUMP%\..\plink.exe
if not exist %PLINK% (echo Wigii ERREUR: plink.exe has not been found & set RETURNVALUE=404 & goto end)

rem prepares Wigii server connexion string
set WIGII_CONNEXION=open ftps://%WIGII_USER%:%WIGII_PWD%@%WIGII_SERVER% -explicit -certificate="%WIGII_CERTIFICATE%"
set WINSCP_CMD=%WINSCP% /log=%LOCAL_DBDUMP%\winscp.log /loglevel=0 /logsize=5*2M /command

echo Updates local dump folder %WIGII_HOST%-prod
svn update %LOCAL_DBDUMP%\%WIGII_HOST%-prod

rem gets latest database dump of given wigii client
echo Finds latest database dump for %WIGII_CLIENT%
rem loads any client / db name exceptional mappings
set "WIGII_DBDUMP_LABEL=wigii_%WIGII_CLIENT%"
if exist %LOCAL_DBDUMP%\%WIGII_HOST%-prod\%WIGII_HOST%-client-dbname.bat (for /F "tokens=2 delims==" %%a in ('findstr /C:"%WIGII_HOST%_%WIGII_CLIENT%_dbname" %LOCAL_DBDUMP%\%WIGII_HOST%-prod\%WIGII_HOST%-client-dbname.bat') do (set WIGII_DBDUMP_LABEL=%%a))
if "%WIGII_DBDUMP_LABEL%"=="" set "WIGII_DBDUMP_LABEL=wigii_%WIGII_CLIENT%"
rem gets lates database dump on server
set SHCMD=%PLINK% -ssh -pw %WIGII_PWD% %WIGII_USER%@%WIGII_SERVER% "ls -t ~/%WIGII_DBDUMP%/%WIGII_DBDUMP_LABEL%_*.sql | head -n1"
for /f "delims=" %%a in ('%SHCMD%') do (set WIGII_DB=%%a)
:extractFileNameFromPath
for /f "tokens=1* delims=/" %%a in ('echo %WIGII_DB%') do (set TAIL=%%b)
if "%TAIL%"=="" (goto endExtractFileNameFromPath)
set WIGII_DB=%TAIL%
goto extractFileNameFromPath
:endExtractFileNameFromPath
if "%WIGII_DB%"=="" (echo Wigii ERREUR: No database dump for %WIGII_CLIENT% & set RETURNVALUE=404 & goto end) else (echo %WIGII_DB%)
rem downloads lates database dump
echo Downloads dump to %LOCAL_DBDUMP%\%WIGII_HOST%-prod
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "lcd %LOCAL_DBDUMP%\%WIGII_HOST%-prod" ^
 "get -transfer=binary %WIGII_DBDUMP%/%WIGII_DB%" ^
  "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError

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
set LOCAL_DBDUMP=
set WIGII_DBDUMP=
set WIGII_DBDUMP_LABEL=
set WIGII_CONNEXION=
set SHCMD=
set PLINK=
set WINSCP=
set WINSCP_CMD=
exit /b %RETURNVALUE%