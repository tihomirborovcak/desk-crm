@echo off
echo Instaliram Transcription Worker kao scheduled task...

schtasks /create /tn "TranscriptionWorker" /tr "pythonw C:\xampp\htdocs\desk-crm\worker\transcription_worker.py" /sc onstart /ru "%USERNAME%" /rl highest /f

echo.
echo Task kreiran! Worker ce se pokrenuti automatski pri startu Windowsa.
echo.
echo Za rucno pokretanje: schtasks /run /tn "TranscriptionWorker"
echo Za zaustavljanje: schtasks /end /tn "TranscriptionWorker"
echo Za brisanje: schtasks /delete /tn "TranscriptionWorker" /f
echo.
pause
