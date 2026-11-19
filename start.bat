@echo off
setlocal

set "APACHE_BIN=E:\php\phpstudy_pro\Extensions\Apache2.4.39\bin\httpd.exe"
set "APACHE_CONF=E:\php\phpstudy_pro\Extensions\Apache2.4.39\conf\httpd.conf"
set "MYSQL_BIN=E:\php\phpstudy_pro\Extensions\MySQL5.7.26\bin\mysqld.exe"
set "MYSQL_INI=E:\php\phpstudy_pro\Extensions\MySQL5.7.26\my.ini"
set "SITE_URL=http://127.0.0.2:8080/"

echo ==========================================
echo   ShopLab Launcher
echo ==========================================

netstat -ano | findstr /c:":3306 " >nul
if errorlevel 1 (
    echo [1/3] Starting MySQL ...
    powershell -NoProfile -Command "Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{CommandLine='%MYSQL_BIN% --defaults-file=%MYSQL_INI%'} | Out-Null"
) else (
    echo [1/3] MySQL is already running
)

netstat -ano | findstr /c:":8080 " >nul
if errorlevel 1 (
    echo [2/3] Starting Apache ...
    powershell -NoProfile -Command "Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{CommandLine='%APACHE_BIN% -f %APACHE_CONF%'} | Out-Null"
) else (
    echo [2/3] Apache is already running
)

echo [3/3] Opening %SITE_URL%
ping -n 3 127.0.0.1 >nul
start "" "%SITE_URL%"

endlocal
