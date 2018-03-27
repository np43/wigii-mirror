SET /p V=Revision number: 
cd C:\Users\Lionel\Documents\workspace\Wigii.org\trunk\www\assets\js\src

del ..\wigii_%v%.js
copy NUL ..\wigii_%v%.js

for %%i in (*.js) do type "%%i" >> ..\wigii_%v%.js

pause