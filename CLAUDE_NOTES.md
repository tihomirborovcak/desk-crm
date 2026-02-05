# Desk CRM - Bilješke za razvoj

## Server i pristup
- **SSH**: `root@5.75.156.67` (preko SSH ključa, bez lozinke)
- **Hetzner** - hosting provider (za reset root lozinke ako treba)
- **MySQL server**: `deskcrm` / `Signal321!` @ localhost, baza: `deskcrm`
- **MySQL lokalno**: `root` / (prazno) @ 127.0.0.1:3306
- **Path na serveru**: `/home/zagorje-promocija/htdocs/www.zagorje-promocija.com/desk-crm/`
- **Web URL**: `https://www.zagorje-promocija.com/desk-crm/`
- **Deploy**: `git push origin main` → `ssh root@5.75.156.67 "cd /home/zagorje-promocija/htdocs/www.zagorje-promocija.com/desk-crm && git fetch origin && git reset --hard origin/main"`
- **GitHub repo**: `https://github.com/tihomirborovcak/desk-crm.git`

## API ključevi i credentials
- **Google Cloud Console**: https://console.cloud.google.com/
- **Google credentials**: `google-credentials.json` (service account: `vertex-express@robotic-flash-428407-f0.iam.gserviceaccount.com`)
- **Google projekt ID**: `robotic-flash-428407-f0`
- **GA4 Property ID**: `279956882` (za zagorje.com)
- **Gemini model**: `gemini-2.5-flash` (2.0-flash deprecated ožujak 2026, 3.0 je preview-only)
- **Gemini region**: `europe-central2`
- **Feedly token**: u `ga-analitika.php` (za dohvat objavljenih članaka)
- **Feedly stream ID**: `user/6e135c2a-75fe-4109-8be8-6b52fa6866e6/category/zagorjecom`

## Važne postavke Gemini API
- `thinkingConfig: { thinkingBudget: 0 }` - OBAVEZNO, inače "thinking" troši output tokene
- `maxOutputTokens`: transkripcija 32000, članak 16000

## Struktura projekta
- **transkripcija.php** - dva taba: "Transkripcija" (jedan ton) i "Više tonova" (multi-audio)
- **transkripcija-view.php** - prikaz spremljenih transkripcija, podržava više audio fajlova
- **tekst-ai.php** - AI prerada teksta (prepiši, skrati, proširi, korekcija)
- **skini-tekst.php** - skidanje i prerada članaka s URL-a
- **ga-analitika.php** - GA4 analytics dashboard
- **api/ga4-realtime.php** - realtime podaci za dashboard

## Baza - tablice
- `users` - korisnici
- `transcriptions` - transkripcije (audio_path i audio_filename mogu biti comma-separated za više fajlova)
- `tasks`, `events`, `articles`, `portals`...

## Česti problemi i rješenja

### GA4 realtime - točan broj korisnika
GA4 realtime API nema `totals` polje. Za točan ukupni broj treba dva upita:
1. Upit BEZ dimenzija = vraća jedan red s ukupnim brojem
2. Upit S dimenzijama = vraća breakdown po stranicama

### Transkripcija - bijeli ekran
Koristi POST-redirect-GET pattern sa `$_SESSION` za dugotrajne operacije.

### 24sata.hr ekstrakcija teksta
Specifičan XPath selektor: `article__content article_content_container` (ne samo `article__content` jer matchira wrappere)

### HTML entiteti u skinutom tekstu
Uvijek `html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')` za hrvatske znakove.

## Korisnik
- **Vlasnik**: Tihomir Borovčak
- **Lokalni dev**: `C:\xampp\htdocs\desk-crm\`
- **XAMPP** za lokalni PHP/MySQL

## Nedavne promjene (veljača 2026)
- Dodano: tip mobitela u GA4 uređaji (`mobileDeviceBranding`)
- Dodano: filtriranje reklamnog sadržaja u skini-tekst.php
- Fix: dekodiranje HTML entiteta za hrvatske znakove
- Fix: specifičniji XPath za 24sata.hr članke
- Upgrade: Gemini 2.0-flash → 2.5-flash (svi fajlovi)
- Dodano: thinkingConfig za isključivanje "thinking" tokena
- Fix: GA4 realtime točan broj (dva upita)
- Fix: trending usporedba s nulom
- Redizajn: Apple-style CSS (manji nav, tighter spacing)
- Dodano: "Više tonova" tab za multi-audio transkripciju

## Upload i fajlovi
- **UPLOAD_PATH**: `/home/zagorje-promocija/htdocs/www.zagorje-promocija.com/desk-crm/uploads/`
- **UPLOAD_URL**: `https://www.zagorje-promocija.com/desk-crm/uploads/`
- Audio kompresija: ffmpeg ako >20MB
