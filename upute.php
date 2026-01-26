<?php
/**
 * Upute za korištenje sustava
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

define('PAGE_TITLE', 'Upute');
include 'includes/header.php';
?>

<div class="page-header">
    <h1>Upute za korištenje</h1>
    <p style="color: #6b7280; margin: 0.25rem 0 0 0;">Sve što trebate znati o korištenju Desk CRM sustava</p>
</div>

<div style="max-width: 900px;">

<!-- PRIJAVA -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Prijava u sustav</h2>
    </div>
    <div class="card-body" style="line-height: 1.8;">
        <p>Za pristup sustavu potrebno je imati korisničko ime i lozinku koje dodjeljuje administrator.</p>
        <ul style="margin: 1rem 0; padding-left: 1.5rem;">
            <li>Otvorite aplikaciju u web pregledniku</li>
            <li>Unesite korisničko ime i lozinku</li>
            <li>Kliknite "Prijava"</li>
        </ul>
        <p><strong>Napomena:</strong> Sesija traje 2 sata. Nakon toga potrebna je ponovna prijava.</p>
    </div>
</div>

<!-- DASHBOARD -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Početna stranica (Dashboard)</h2>
    </div>
    <div class="card-body" style="line-height: 1.8;">
        <p>Dashboard prikazuje pregled svih važnih informacija na jednom mjestu:</p>
        <ul style="margin: 1rem 0; padding-left: 1.5rem;">
            <li><strong>Nadolazeći eventi</strong> - popis događaja i dežurstava za sljedećih 7 dana</li>
            <li><strong>Statistika</strong> - broj tema, evenata i drugih stavki</li>
            <li><strong>Nedavna aktivnost</strong> - što se zadnje događalo u sustavu</li>
        </ul>
    </div>
</div>

<!-- TEME ZL -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Teme ZL</h2>
    </div>
    <div class="card-body" style="line-height: 1.8;">
        <p>Upravljanje temama za Zagorski list.</p>

        <h4 style="margin-top: 1rem; color: #374151;">Statusi tema:</h4>
        <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
            <li><span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px;">Novo</span> - nova tema, čeka obradu</li>
            <li><span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px;">U radu</span> - tema se obrađuje</li>
            <li><span style="background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 4px;">Objavljeno</span> - tema je objavljena</li>
            <li><span style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px;">Odbijeno</span> - tema nije prihvaćena</li>
        </ul>

        <h4 style="margin-top: 1rem; color: #374151;">Kako dodati novu temu:</h4>
        <ol style="margin: 0.5rem 0; padding-left: 1.5rem;">
            <li>Kliknite "Nova tema"</li>
            <li>Unesite naslov i opis teme</li>
            <li>Odaberite kategoriju ako je potrebno</li>
            <li>Kliknite "Spremi"</li>
        </ol>
    </div>
</div>

<!-- EVENTI -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Eventi i dežurstva</h2>
    </div>
    <div class="card-body" style="line-height: 1.8;">
        <p>Kalendar za praćenje događaja i rasporeda dežurstava.</p>

        <h4 style="margin-top: 1rem; color: #374151;">Dodavanje eventa:</h4>
        <ol style="margin: 0.5rem 0; padding-left: 1.5rem;">
            <li>Otvorite "Eventi" iz izbornika</li>
            <li>Kliknite na željeni datum u kalendaru ili "Novi event"</li>
            <li>Unesite naziv, datum, vrijeme i opis</li>
            <li>Kliknite "Spremi"</li>
        </ol>

        <h4 style="margin-top: 1rem; color: #374151;">Dežurstva:</h4>
        <p>Dežurstva se prikazuju pokraj datuma u kalendaru. Klik na ime otvara uređivanje.</p>
    </div>
</div>

<!-- TRANSKRIPCIJA -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Transkripcija (Audio u tekst)</h2>
    </div>
    <div class="card-body" style="line-height: 1.8;">
        <p>Pretvaranje audio snimki u tekst pomoću Google AI (Gemini).</p>

        <h4 style="margin-top: 1rem; color: #374151;">Podržani formati:</h4>
        <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
            <li>MP3, WAV, M4A, OGG, FLAC</li>
            <li>Maksimalna veličina: 20 MB</li>
        </ul>

        <h4 style="margin-top: 1rem; color: #374151;">Kako koristiti:</h4>
        <ol style="margin: 0.5rem 0; padding-left: 1.5rem;">
            <li>Otvorite "Transkripcija" iz izbornika</li>
            <li>Kliknite "Odaberi datoteku" i odaberite audio</li>
            <li>Unesite naslov (npr. "Intervju s gradonačelnikom")</li>
            <li>Kliknite "Transkribiraj"</li>
            <li>Pričekajte dok AI obradi snimku (može trajati 30-120 sekundi)</li>
            <li>Pregledajte i kopirajte tekst</li>
        </ol>

        <h4 style="margin-top: 1rem; color: #374151;">Prerada u članak:</h4>
        <p>Nakon transkripcije možete kliknuti "Preradi u članak" da AI strukturira tekst u novinarski format s naslovom, leadom i paragrafima.</p>

        <div style="background: #f3f4f6; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
            <strong>Savjet:</strong> Za najbolje rezultate koristite kvalitetne snimke bez previše buke u pozadini.
        </div>
    </div>
</div>

<!-- AI SLIKE -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">AI Slike (Imagen)</h2>
    </div>
    <div class="card-body" style="line-height: 1.8;">
        <p>Generiranje slika pomoću Google Imagen AI na temelju tekstualnog opisa.</p>

        <h4 style="margin-top: 1rem; color: #374151;">Kako generirati sliku:</h4>
        <ol style="margin: 0.5rem 0; padding-left: 1.5rem;">
            <li>Otvorite "AI Slike" iz izbornika</li>
            <li>Unesite opis slike na hrvatskom jeziku</li>
            <li>Označite "Prevedi na engleski" za bolje rezultate (preporučeno)</li>
            <li>Kliknite "Generiraj"</li>
            <li>Pričekajte 10-30 sekundi</li>
            <li>Slika će se prikazati - možete je preuzeti ili obrisati</li>
        </ol>

        <h4 style="margin-top: 1rem; color: #374151;">Primjeri dobrih opisa:</h4>
        <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
            <li>"Zalazak sunca nad zagorskim bregovima, fotografski stil"</li>
            <li>"Moderna uredska zgrada, poslovni stil, dnevno svjetlo"</li>
            <li>"Skupina ljudi na konferenciji, profesionalna fotografija"</li>
        </ul>

        <div style="background: #fef3c7; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
            <strong>Važno:</strong> Generirane slike su umjetno stvorene i trebaju se koristiti odgovorno.
        </div>
    </div>
</div>

<!-- SKINI TEKST -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Skini i preradi tekst</h2>
    </div>
    <div class="card-body" style="line-height: 1.8;">
        <p>Dohvaćanje i prerada tekstova s drugih web stranica.</p>

        <h4 style="margin-top: 1rem; color: #374151;">Kako koristiti:</h4>
        <ol style="margin: 0.5rem 0; padding-left: 1.5rem;">
            <li>Otvorite "Skini tekst" iz izbornika</li>
            <li>Zalijepite URL članka u polje</li>
            <li>Odaberite opciju:
                <ul style="margin: 0.5rem 0;">
                    <li><strong>Samo skini</strong> - dohvaća originalni tekst bez promjena</li>
                    <li><strong>Skini i preradi</strong> - dohvaća i prerađuje tekst pomoću AI</li>
                </ul>
            </li>
            <li>Pričekajte obradu (može trajati 30-60 sekundi za preradu)</li>
            <li>Kopirajte tekst klikom na "Kopiraj"</li>
        </ol>

        <h4 style="margin-top: 1rem; color: #374151;">Podržani portali:</h4>
        <p>Sustav može dohvatiti tekst s većine hrvatskih news portala uključujući Index, 24sata, Jutarnji, Večernji i druge.</p>
    </div>
</div>

<!-- AI TEKST -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">AI Tekst (Gemini)</h2>
    </div>
    <div class="card-body" style="line-height: 1.8;">
        <p>Razne AI operacije nad tekstom - sažimanje, proširivanje, ispravak i više.</p>

        <h4 style="margin-top: 1rem; color: #374151;">Dostupne opcije:</h4>
        <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
            <li><strong>Sažmi</strong> - skraćuje dugi tekst na ključne točke</li>
            <li><strong>Proširi</strong> - dodaje detalje i produbljuje tekst</li>
            <li><strong>Ispravi</strong> - ispravlja gramatičke i pravopisne greške</li>
            <li><strong>Prevedi</strong> - prevodi tekst na drugi jezik</li>
            <li><strong>Napiši članak</strong> - stvara članak na zadanu temu</li>
        </ul>
    </div>
</div>

<!-- PORTALI -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Praćenje portala</h2>
    </div>
    <div class="card-body" style="line-height: 1.8;">
        <p>Pregled najčitanijih članaka s hrvatskih news portala.</p>

        <h4 style="margin-top: 1rem; color: #374151;">Praćeni portali:</h4>
        <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
            <li>Index.hr</li>
            <li>24sata</li>
            <li>Jutarnji list</li>
            <li>Večernji list</li>
            <li>Zagorje International</li>
            <li>Net.hr</li>
        </ul>

        <p style="margin-top: 1rem;">Kliknite "Osvježi" za dohvat najnovijih podataka. Klik na članak otvara opciju za skidanje i preradu teksta.</p>
    </div>
</div>

<!-- SAVJETI -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Korisni savjeti</h2>
    </div>
    <div class="card-body" style="line-height: 1.8;">
        <ul style="margin: 0; padding-left: 1.5rem;">
            <li><strong>Preglednik:</strong> Koristite Chrome ili Firefox za najbolje iskustvo</li>
            <li><strong>AI obrada:</strong> Može trajati 10-120 sekundi - budite strpljivi i ne osvježavajte stranicu</li>
            <li><strong>Kopiranje:</strong> Koristite gumb "Kopiraj" ili Ctrl+C za označeni tekst</li>
            <li><strong>Greške:</strong> Ako nešto ne radi, osvježite stranicu (F5) i pokušajte ponovno</li>
            <li><strong>Odjava:</strong> Uvijek se odjavite ako koristite dijeljeno računalo</li>
        </ul>
    </div>
</div>

<!-- PODRŠKA -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Podrška</h2>
    </div>
    <div class="card-body" style="line-height: 1.8;">
        <p>Za pomoć ili prijavu problema obratite se administratoru sustava.</p>
    </div>
</div>

</div>

<?php include 'includes/footer.php'; ?>
