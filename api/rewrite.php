<?php
/**
 * API endpoint za preradu teksta s Gemini AI
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Provjera autentikacije
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Niste prijavljeni']);
    exit;
}

// Samo POST metoda
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metoda nije dozvoljena']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Dohvati JSON input
$input = json_decode(file_get_contents('php://input'), true);
$text = trim($input['text'] ?? '');

if (empty($text)) {
    echo json_encode(['success' => false, 'error' => 'Tekst je prazan']);
    exit;
}

// Gemini API
$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    echo json_encode(['success' => false, 'error' => 'GEMINI_API_KEY nije postavljen']);
    exit;
}

// Prompt za preradu
$prompt = "Preradi sljedeći novinarski članak na hrvatski jezik. Zadrži sve ključne informacije ali promijeni strukturu rečenica i riječi tako da tekst bude originalan. Ne dodaji ništa što nije u originalnom tekstu. Vrati samo prerađeni tekst bez dodatnih komentara.\n\nTekst:\n" . $text;

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 8192
    ]
];

$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode($data),
        'timeout' => 120
    ]
]);

$response = @file_get_contents($url, false, $ctx);

if ($response === false) {
    echo json_encode(['success' => false, 'error' => 'Greška pri komunikaciji s Gemini API-jem']);
    exit;
}

$result = json_decode($response, true);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode([
        'success' => true,
        'text' => $result['candidates'][0]['content']['parts'][0]['text']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Neočekivan odgovor od API-ja']);
}
