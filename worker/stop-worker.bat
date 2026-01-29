@echo off
echo Zaustavljam worker...
taskkill /F /IM pythonw.exe 2>nul
taskkill /F /FI "WINDOWTITLE eq Transcription Worker*" 2>nul
echo Worker zaustavljen.
timeout /t 2
