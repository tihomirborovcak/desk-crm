#!/usr/bin/env python3
"""
Transcription Worker - pokreće se na lokalnom PC-u
Koristi faster-whisper za transkripciju s GPU ubrzanjem
"""

import os
import sys
import time
import json
import requests
import tempfile
from pathlib import Path

# Konfiguracija
SERVER_URL = "https://www.zagorje-promocija.com/desk-crm/api/worker/"
API_KEY = "REDACTED_WORKER_KEY"  # Mora biti isto kao na serveru!
POLL_INTERVAL = 5  # sekundi između provjera
WHISPER_MODEL = "large-v3"  # best quality - options: tiny, base, small, medium, large-v2, large-v3
DEVICE = "cuda"  # ili "cpu" ako nema GPU
COMPUTE_TYPE = "float16"  # float16 za GPU, int8 za CPU

# Headers za API
HEADERS = {
    "X-API-Key": API_KEY,
    "Content-Type": "application/json"
}

def log(message):
    """Ispiši log s timestamp-om"""
    timestamp = time.strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{timestamp}] {message}")

def get_pending_jobs():
    """Dohvati pending jobove sa servera"""
    try:
        response = requests.get(f"{SERVER_URL}?action=pending", headers=HEADERS, timeout=30)
        if response.status_code == 200:
            return response.json().get("jobs", [])
        else:
            log(f"Greška pri dohvatu jobova: {response.status_code}")
            return []
    except Exception as e:
        log(f"Greška: {e}")
        return []

def claim_job(job_id):
    """Preuzmi job (označi kao processing)"""
    try:
        response = requests.get(f"{SERVER_URL}?action=claim&id={job_id}", headers=HEADERS, timeout=30)
        if response.status_code == 200:
            data = response.json()
            if "error" in data:
                return None
            return data.get("job")
        return None
    except Exception as e:
        log(f"Greška pri preuzimanju joba: {e}")
        return None

def download_audio(job_id, output_path):
    """Skini audio datoteku"""
    try:
        response = requests.get(f"{SERVER_URL}?action=download&id={job_id}", headers=HEADERS, timeout=300, stream=True)
        if response.status_code == 200:
            with open(output_path, "wb") as f:
                for chunk in response.iter_content(chunk_size=8192):
                    f.write(chunk)
            return True
        else:
            log(f"Greška pri skidanju audia: {response.status_code}")
            return False
    except Exception as e:
        log(f"Greška: {e}")
        return False

def transcribe(audio_path, language="hr"):
    """Transkribiraj s faster-whisper (GPU ubrzanje)"""
    from faster_whisper import WhisperModel
    import torch

    # Provjeri GPU dostupnost
    device = DEVICE if torch.cuda.is_available() else "cpu"
    compute_type = COMPUTE_TYPE if device == "cuda" else "int8"

    log(f"Učitavam faster-whisper model ({WHISPER_MODEL}) na {device}...")
    model = WhisperModel(WHISPER_MODEL, device=device, compute_type=compute_type)

    log("Transkribiram...")
    segments, info = model.transcribe(
        audio_path,
        language=language,
        beam_size=5,
        vad_filter=True,  # Voice Activity Detection za bolje filtriranje tišine
        vad_parameters=dict(min_silence_duration_ms=500)
    )

    log(f"Detektirani jezik: {info.language} (vjerojatnost: {info.language_probability:.2%})")
    log(f"Trajanje: {info.duration:.1f}s")

    return [{"start": s.start, "end": s.end, "text": s.text} for s in segments]

def format_srt(segments):
    """Pretvori segmente u SRT format"""
    def format_timestamp(seconds):
        hours = int(seconds // 3600)
        minutes = int((seconds % 3600) // 60)
        secs = int(seconds % 60)
        msecs = int((seconds % 1) * 1000)
        return f"{hours:02d}:{minutes:02d}:{secs:02d},{msecs:03d}"

    srt_lines = []
    for i, segment in enumerate(segments, 1):
        start = format_timestamp(segment["start"])
        end = format_timestamp(segment["end"])
        text = segment["text"].strip()
        srt_lines.append(f"{i}\n{start} --> {end}\n{text}\n")

    return "\n".join(srt_lines)

def upload_result(job_id, srt_content, processing_time, error=None):
    """Upload rezultata na server"""
    try:
        data = {
            "srt_content": srt_content,
            "processing_time": processing_time,
            "error": error
        }
        response = requests.post(
            f"{SERVER_URL}?action=complete&id={job_id}",
            headers=HEADERS,
            json=data,
            timeout=60
        )
        return response.status_code == 200
    except Exception as e:
        log(f"Greška pri uploadu: {e}")
        return False

def process_job(job):
    """Obradi jedan job"""
    job_id = job["id"]
    filename = job["original_filename"]
    language = job.get("language", "hr")

    log(f"=== Obrađujem job #{job_id}: {filename} ===")

    start_time = time.time()

    # Kreiraj temp direktorij
    with tempfile.TemporaryDirectory() as temp_dir:
        audio_path = os.path.join(temp_dir, "audio.mp3")

        # Skini audio
        log("Skidam audio...")
        if not download_audio(job_id, audio_path):
            upload_result(job_id, "", 0, "Greška pri skidanju audia")
            return False

        # Transkribiraj
        try:
            segments = transcribe(audio_path, language)
            srt_content = format_srt(segments)
        except Exception as e:
            log(f"Greška pri transkripciji: {e}")
            upload_result(job_id, "", 0, str(e))
            return False

        processing_time = time.time() - start_time

        # Upload rezultata
        log(f"Uploadam SRT ({len(srt_content)} bytes)...")
        if upload_result(job_id, srt_content, processing_time):
            log(f"✓ Job #{job_id} završen za {processing_time:.1f}s")
            return True
        else:
            log(f"✗ Greška pri uploadu joba #{job_id}")
            return False

def main():
    """Glavna petlja"""
    log("=" * 50)
    log("Transcription Worker pokrenut")
    log(f"Server: {SERVER_URL}")
    log(f"Model: {WHISPER_MODEL}")
    log(f"Compute type: {COMPUTE_TYPE}")
    log("=" * 50)

    # Provjeri GPU
    try:
        import torch
        if torch.cuda.is_available():
            gpu_name = torch.cuda.get_device_name(0)
            gpu_mem = torch.cuda.get_device_properties(0).total_memory / 1024**3
            log(f"GPU: {gpu_name} ({gpu_mem:.1f} GB)")
        else:
            log("GPU nije dostupan, koristim CPU (sporije)")
    except Exception as e:
        log(f"PyTorch nije instaliran ili greška: {e}")

    log("Čekam jobove...\n")

    while True:
        try:
            jobs = get_pending_jobs()

            if jobs:
                for job in jobs:
                    # Preuzmi job
                    claimed_job = claim_job(job["id"])
                    if claimed_job:
                        process_job(claimed_job)
                    time.sleep(1)
            else:
                # Čekaj prije sljedeće provjere
                time.sleep(POLL_INTERVAL)

        except KeyboardInterrupt:
            log("\nWorker zaustavljen.")
            break
        except Exception as e:
            log(f"Greška u glavnoj petlji: {e}")
            time.sleep(POLL_INTERVAL)

if __name__ == "__main__":
    main()
