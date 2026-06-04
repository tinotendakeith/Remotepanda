@echo off
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0test-clinic-backup-restore.ps1" %*
