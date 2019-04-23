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
REM This script dumps all Wigii client databases
REM Created by CWE on 11.04.2019
REM 

rem set WIGII_HOST=wwwigii-system
rem set WIGII_SERVER=ftp.server.com
rem set WIGII_CERTIFICATE="xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx:xx"
rem set WIGII_USER=xxxx
rem set WIGII_PWD=xxxx
rem set LOCAL_DBDUMP=%~dp0
rem set WIGII_DBDUMP=wigii/db-dump

set RETURNVALUE=0
echo Changes code page to UTF-8
chcp 65001

if "%WIGII_HOST%"=="" (echo Wigii ERROR: WIGII_HOST is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_SERVER%"=="" (echo Wigii ERROR: WIGII_SERVER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_CERTIFICATE%"=="" (echo Wigii ERROR: WIGII_CERTIFICATE is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_USER%"=="" (echo Wigii ERROR: WIGII_USER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_PWD%"=="" (echo Wigii ERROR: WIGII_PWD is not defined. & set RETURNVALUE=1009 & goto end)
if "%LOCAL_DBDUMP%"=="" (set LOCAL_DBDUMP=%~dp0)
if "%WIGII_DBDUMP%"=="" (set WIGII_DBDUMP=wigii/db-dump & echo WIGII_DBDUMP is not defined. Sets it to %WIGII_DBDUMP%)
IF %LOCAL_DBDUMP:~-1%==\ SET LOCAL_DBDUMP=%LOCAL_DBDUMP:~0,-1%

rem checks installation of WinSCP
if exist "%ProgramFiles%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles%\WinSCP\WinSCP.exe") else (
if exist "%ProgramFiles(x86)%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles(x86)%\WinSCP\WinSCP.exe") else (
	echo Wigii ERROR: WinScp is not installed & set RETURNVALUE=404 & goto end
))
rem checks presence of plink.exe
set PLINK=%LOCAL_DBDUMP%\..\plink.exe
if not exist %PLINK% (echo Wigii ERROR: plink.exe has not been found & set RETURNVALUE=404 & goto end)

rem prepares Wigii server connexion string
set WIGII_CONNEXION=open ftps://%WIGII_USER%:%WIGII_PWD%@%WIGII_SERVER% -explicit -certificate="%WIGII_CERTIFICATE%"
set WINSCP_CMD=%WINSCP% /log=%LOCAL_DBDUMP%\winscp.log /loglevel=0 /logsize=5*2M /command

echo Updates mysqldump script if needed
rem svn update wigii host db-dump local folder 
svn update %LOCAL_DBDUMP%\%WIGII_HOST%-prod
rem checks presence of mysqldump.sh script
if not exist %LOCAL_DBDUMP%\%WIGII_HOST%-prod\%WIGII_HOST%-mysqldump.sh (echo Wigii ERROR: %WIGII_HOST%-prod\%WIGII_HOST%-mysqldump.sh has not been found & set RETURNVALUE=10305 & goto end)
rem  updates mysqldump script if needed
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "lcd %LOCAL_DBDUMP%/%WIGII_HOST%-prod" ^
 "cd %WIGII_DBDUMP%" ^
 "put -neweronly -transfer=ascii %WIGII_HOST%-mysqldump.sh" ^
 "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError
rem sets execution rights on mysqldump file
%PLINK% -ssh -pw %WIGII_PWD% %WIGII_USER%@%WIGII_SERVER% chmod 764 ~/%WIGII_DBDUMP%/%WIGII_HOST%-mysqldump.sh
 
rem dumps databases on server side
echo Dumps %WIGII_HOST% client databases
%PLINK% -ssh -pw %WIGII_PWD% %WIGII_USER%@%WIGII_SERVER% ~/%WIGII_DBDUMP%/%WIGII_HOST%-mysqldump.sh

rem lists dumped sql files
for /f "tokens=1-3 delims=. " %%a in ('date /T') do (set TIMESTAMP=%%c%%b%%a)
%PLINK% -ssh -pw %WIGII_PWD% %WIGII_USER%@%WIGII_SERVER% find ~/%WIGII_DBDUMP%/ -name *_%TIMESTAMP%.sql -printf "%%f\\0%%s\\n"

goto end
:winScpError
set RETURNVALUE=10303
echo Erreur de communication WinScp
goto end
:end
REM clears all variables and exits with return value
set WIGII_HOST=
set WIGII_SERVER=
set WIGII_CERTIFICATE=
set WIGII_USER=
set WIGII_PWD=
set LOCAL_DBDUMP=
set WIGII_DBDUMP=
set WIGII_CONNEXION=
set PLINK=
set WINSCP=
set WINSCP_CMD=
exit /b %RETURNVALUE%