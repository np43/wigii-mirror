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
REM This script puts live a package on the Wigii server
REM Created by CWE on 17.04.2019
REM 

set RETURNVALUE=0
SET PREVIOUS_PATH=%CD%
cd %~dp0
echo Changes code page to UTF-8
chcp 65001

if "%1"=="-preprod" (set WIGII_OPTION_PROD=0) else (set WIGII_OPTION_PROD=1)
if "%WIGII_HOST%"=="" (echo Wigii ERROR: WIGII_HOST is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_SERVER%"=="" (echo Wigii ERROR: WIGII_SERVER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_CERTIFICATE%"=="" (echo Wigii ERROR: WIGII_CERTIFICATE is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_USER%"=="" (echo Wigii ERROR: WIGII_USER is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_PWD%"=="" (echo Wigii ERROR: WIGII_PWD is not defined. & set RETURNVALUE=1009 & goto end)
if "%WIGII_UPGRADES%"=="" (set WIGII_UPGRADES=upgrades)
if "%WIGII_PREPROD%"=="" (set WIGII_PREPROD=preprod)

rem checks installation of WinSCP
if exist "%ProgramFiles%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles%\WinSCP\WinSCP.exe") else (
if exist "%ProgramFiles(x86)%\WinSCP\WinSCP.exe" (set WINSCP="%ProgramFiles(x86)%\WinSCP\WinSCP.exe") else (
	echo Wigii ERROR: WinScp is not installed & set RETURNVALUE=404 & goto end
))
rem checks presence of plink.exe
set PLINK=..\plink.exe
if not exist %PLINK% (echo Wigii ERROR: plink.exe has not been found & set RETURNVALUE=404 & goto end)

:checkProdPackage
echo Checks %WIGII_HOST%-prod folder structure
set WIGII_TARGET=%~dp0%WIGII_HOST%-prod
if not exist %WIGII_TARGET% (echo Wigii ERROR: %WIGII_HOST%-prod is not correctly setup. & set RETURNVALUE=1009 & goto end)

rem prepares Wigii server connexion string
set WIGII_CONNEXION=open ftps://%WIGII_USER%:%WIGII_PWD%@%WIGII_SERVER% -explicit -certificate="%WIGII_CERTIFICATE%"
set WINSCP_CMD=%WINSCP% /log=winscp.log /loglevel=0 /logsize=5*2M /command

rem prepares package name for prod or preprod
for /f "tokens=1-3 delims=. " %%a in ('date /T') do (set TIMESTAMP=%%c%%b%%a)
for /f "tokens=1,2 delims=: " %%a in ('time /T') do (set TIMESTAMP=%TIMESTAMP%%%a%%b)
set WIGII_PACKAGE=wigii_%TIMESTAMP%
if not "%WIGII_OPTION_PROD%"=="1" (
	set "WIGII_PACKAGE=%WIGII_PACKAGE%-preprod"
	set WIGII_OPTION_DEPLOYLABEL=preprod
) else (
	set "WIGII_PACKAGE=%WIGII_PACKAGE%-prod"
	set WIGII_OPTION_DEPLOYLABEL=live
)

:confirmGoLive
set /P WIGII_OPTION_GOLIVE="Are you OK to go %WIGII_OPTION_DEPLOYLABEL% with package %WIGII_PACKAGE% on %WIGII_HOST% ? (y/n) "
set WIGII_OPTION_GOLIVE=%WIGII_OPTION_GOLIVE:y=1%
if not "%WIGII_OPTION_GOLIVE%"=="1" (echo Wigii ERROR: Go %WIGII_OPTION_DEPLOYLABEL% operation canceled by user. & set RETURNVALUE=1010 & goto end)

:createPackageFolder
echo Creates folder %WIGII_PACKAGE% on %WIGII_HOST% / %WIGII_UPGRADES% and uploads files
rem ensures uploading to a folder called upgrades, even if parent path is not known. Prevents that way to upload in some unwanted location.
%WINSCP_CMD% ^
 "%WIGII_CONNEXION%" ^
 "lcd %WIGII_TARGET%" ^
 "cd %WIGII_UPGRADES%/../upgrades" ^
 "mkdir %WIGII_PACKAGE%" ^
 "cd %WIGII_PACKAGE%" ^
 "put -transfer=binary -nopermissions *" ^
 "close" ^
 "exit"
if %ERRORLEVEL% neq 0 goto winScpError

:copyPackageContentToLive
echo Copies package %WIGII_PACKAGE% content to %WIGII_OPTION_DEPLOYLABEL%
if not "%WIGII_OPTION_PROD%"=="1" (set "WIGII_DEPLOY_TARGET=~/%WIGII_PREPROD%/../preprod") else (set "WIGII_DEPLOY_TARGET=~/")
%PLINK% -ssh -pw %WIGII_PWD% %WIGII_USER%@%WIGII_SERVER% cp -rf ~/%WIGII_UPGRADES%/../upgrades/%WIGII_PACKAGE%/* %WIGII_DEPLOY_TARGET%
if %ERRORLEVEL% neq 0 (echo Error copying package %WIGII_PACKAGE% content to %WIGII_OPTION_DEPLOYLABEL% & set RETURNVALUE=%ERRORLEVEL% & goto end)

echo Done. Go %WIGII_OPTION_DEPLOYLABEL% of package %WIGII_PACKAGE% on %WIGII_HOST% OK.
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
set WIGII_UPGRADES=
set WIGII_PREPROD=
set WIGII_DEPLOY_TARGET=
set WIGII_OPTION_PROD=
set WIGII_OPTION_DEPLOYLABEL=
set WIGII_OPTION_GOLIVE=
set WIGII_PACKAGE=
set SHCMD=
set PLINK=
set WINSCP=
set WINSCP_CMD=
pause
cd %PREVIOUS_PATH%
exit /b %RETURNVALUE%