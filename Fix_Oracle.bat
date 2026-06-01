@echo off
echo ===================================================
echo Oracle Installer Cleanup Script (Updated for 11g XE)
echo ===================================================
echo Stopping and deleting leftover Oracle services...

sc stop OracleServiceXE >nul 2>&1
sc delete OracleServiceXE >nul 2>&1
sc stop OracleXETNSListener >nul 2>&1
sc delete OracleXETNSListener >nul 2>&1
sc stop OracleJobSchedulerXE >nul 2>&1
sc delete OracleJobSchedulerXE >nul 2>&1
sc stop OracleXEClrAgent >nul 2>&1
sc delete OracleXEClrAgent >nul 2>&1
sc stop OracleMTSRecoveryService >nul 2>&1
sc delete OracleMTSRecoveryService >nul 2>&1
sc stop OracleOraDB21Home1TNSListener >nul 2>&1
sc delete OracleOraDB21Home1TNSListener >nul 2>&1
sc stop OracleOraDB19Home1TNSListener >nul 2>&1
sc delete OracleOraDB19Home1TNSListener >nul 2>&1
sc stop OracleRemExecServiceV2 >nul 2>&1
sc delete OracleRemExecServiceV2 >nul 2>&1
sc stop OracleVssWriterXE >nul 2>&1
sc delete OracleVssWriterXE >nul 2>&1

echo Deleting Oracle Registry Keys...
reg delete "HKLM\SOFTWARE\Oracle" /f >nul 2>&1
reg delete "HKLM\SOFTWARE\WOW6432Node\Oracle" /f >nul 2>&1
reg delete "HKLM\SYSTEM\CurrentControlSet\Services\EventLog\Application\Oracle" /f >nul 2>&1
reg delete "HKLM\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\{05A7B662-80A3-4EB9-AE1D-89A62449431C}" /f >nul 2>&1

echo Cleaning up Oracle leftover files and folders...
rmdir /s /q "C:\app" >nul 2>&1
rmdir /s /q "C:\oraclexe" >nul 2>&1
rmdir /s /q "C:\Program Files\Oracle" >nul 2>&1
rmdir /s /q "C:\Program Files (x86)\Oracle" >nul 2>&1
rmdir /s /q "C:\Program Files (x86)\InstallShield Installation Information\{05A7B662-80A3-4EB9-AE1D-89A62449431C}" >nul 2>&1
rmdir /s /q "D:\OOOOOOOOOOOracle" >nul 2>&1

echo.
echo ===================================================
echo Cleanup Complete!
echo PLEASE RESTART YOUR COMPUTER NOW.
echo After restarting, the Oracle Installer WILL prompt for a password.
echo ===================================================
pause
