::update the directories bellow and remove the exit command
::remove this line when you have corrected the directories
exit 

::request for revision number
SET /p V=Revision number: 

::clean up min folder
::update the directory here and bellow
cd C:\Users\...\Documents\Workspace\Wigii\trunk\www\assets\js\min
forfiles /P . /M * /C "cmd /c if @isdir==FALSE del @file"

::use EnableDelayedExpansion and then !...! to delay the interpretation of the variables
SETLOCAL EnableDelayedExpansion  

::go through all files in src folder
::update the directory here and bellow
cd C:\Users\...\Documents\Workspace\Wigii\trunk\www\assets\js\src
for %%i in (*.js) do (
set _name=%%i
set _nameWithoutExt=!_name:.js=!
set _minExt=!_name:~-7!
::if !_minExt!==.min.js (echo %%i is minified) else (echo %%i is not minified)
if !_minExt!==.min.js (copy "%%i" "..\min\%%i") else (java -jar yuicompressor-2.4.7.jar -o "..\min\!_nameWithoutExt!.min.js" "%%i")
)
ENDLOCAL

::concatenate all files from min folder
::update the directory here
cd C:\Users\...\Documents\Workspace\Wigii\trunk\www\assets\js\min
del ..\wigii_%v%.js
copy NUL ..\wigii_%v%.js
for %%i in (*.min.js) do type "%%i" >> ..\wigii_%v%.js

pause