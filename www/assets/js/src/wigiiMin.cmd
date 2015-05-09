SET /p V=Revision number: 
cd D:\lwr\Medair\Wigii\4 - Development\wigii.ch\3-Construction\trunk\www\assets\js\src

del ..\wigii_%v%.js
copy NUL ..\wigii_%v%.js

for %%i in (*.js) do type "%%i" >> ..\wigii_%v%.js

java -jar yuicompressor-2.4.7.jar -o ..\wigii_%v%.js ..\wigii_%v%.js

pause