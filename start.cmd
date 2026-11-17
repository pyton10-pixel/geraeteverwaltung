@echo off
rem ============================================================
rem  start.cmd - startet die Geraeteverwaltung mit Laragon-PHP
rem  Aufruf: start.cmd   (oder Doppelklick im Explorer)
rem ============================================================
setlocal EnableExtensions
chcp 65001 >nul

cd /d "%~dp0"

rem --- 1. PHP aus Laragon suchen (neueste Version zuerst) -------------
set "PHP_EXE="
set "LARAGON=C:\laragon"
if defined LARAGON_ROOT set "LARAGON=%LARAGON_ROOT%"

for /f "delims=" %%D in ('dir /b /ad /o-n "%LARAGON%\bin\php" 2^>nul') do (
    if not defined PHP_EXE if exist "%LARAGON%\bin\php\%%D\php.exe" set "PHP_EXE=%LARAGON%\bin\php\%%D\php.exe"
)

rem --- 2. Fallback: PHP im PATH --------------------------------------
if not defined PHP_EXE for /f "delims=" %%P in ('where php 2^>nul') do (
    if not defined PHP_EXE set "PHP_EXE=%%P"
)

rem --- 3. Fallback: XAMPP --------------------------------------------
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"

if not defined PHP_EXE (
    echo Kein PHP gefunden.
    echo Bitte Laragon installieren oder PHP in den PATH aufnehmen.
    echo.
    pause
    exit /b 1
)

echo Verwende PHP: %PHP_EXE%
echo.

rem Hauptprogramm starten; Argumente werden weitergegeben
"%PHP_EXE%" "%~dp0verwaltung.php" %*

exit /b %errorlevel%
