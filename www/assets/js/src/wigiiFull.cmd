SET WIGII_PATH=C:\Users\weber\Documents\dev\php-src\Wigii_git

SET /p V=Revision number: 
cd %WIGII_PATH%\www\assets\js\src

del ..\wigii_%v%.js
copy NUL ..\wigii_%v%.js

for %%i in (*.js) do type "%%i" >> ..\wigii_%v%.js

pause