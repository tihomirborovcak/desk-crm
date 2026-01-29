<?php
/**
 * GmailClient - Gmail API OAuth 2.0 klijent
 * 
 * Handles OAuth flow, token management, and Gmail API calls.
 */

class GmailClient
{
    private array $config;
    private ?array $tokens = null;
    
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? require __DIR__ . '/config/gmail-config.php';
        $this->loadTokens();
    }
    
    // =========================================================================
    // OAuth Flow
    // =========================================================================
    
    /**
     * Generiraj URL za OAuth autorizaciju
     */
    public function getAuthUrl(?string $state = null): string
    {
        $params = [
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $this->config['scopes']),
            'access_type' => 'offline',  // Za refresh token
            'prompt' => 'consent',       // Uvijek traži consent za refresh token
        ];
        
        if ($state) {
            $params['state'] = $state;
        }
        
        return $this->config['auth_uri'] . '?' . http_build_query($params);
    }
    
    /**
     * Zamijeni authorization code za tokene
     */
    public function handleCallback(string $code): bool
    {
        $response = $this->httpPost($this->config['token_uri'], [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri' => $this->config['redirect_uri'],
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);
        
        if (isset($response['access_token'])) {
            $this->tokens = [
                'access_token' => $response['access_token'],
                'refresh_token' => $response['refresh_token'] ?? $this->tokens['refresh_token'] ?? null,
                'expires_at' => time() + ($response['expires_in'] ?? 3600),
                'token_type' => $response['token_type'] ?? 'Bearer',
            ];
            $this->saveTokens();
            return true;
        }
        
        throw new RuntimeException('Failed to get tokens: ' . json_encode($response));
    }
    
    /**
     * Osvježi access token koristeći refresh token
     */
    public function refreshAccessToken(): bool
    {
        if (empty($this->tokens['refresh_token'])) {
            throw new RuntimeException('No refresh token available. Re-authorize the app.');
        }
        
        $response = $this->httpPost($this->config['token_uri'], [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'refresh_token' => $this->tokens['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);
        
        if (isset($response['access_token'])) {
            $this->tokens['access_token'] = $response['access_token'];
            $this->tokens['expires_at'] = time() + ($response['expires_in'] ?? 3600);
            $this->saveTokens();
            return true;
        }
        
        return false;
    }
    
    /**
     * Provjeri je li korisnik autoriziran
     */
    public function isAuthorized(): bool
    {
        return !empty($this->tokens['access_token']);
    }
    
    /**
     * Dohvati validan access token (automatski refresh ako je istekao)
     */
    public function getAccessToken(): ?string
    {
        if (empty($this->tokens['access_token'])) {
            return null;
        }
        
        // Refresh 5 minuta prije isteka
        if (isset($this->tokens['expires_at']) && $this->tokens['expires_at'] < time() + 300) {
            $this->refreshAccessToken();
        }
        
        return $this->tokens['access_token'];
    }
    
    /**
     * Odjavi korisnika (obriši tokene)
     */
    public function logout(): void
    {
        $this->tokens = null;
        if (file_exists($this->config['token_file'])) {
            unlink($this->config['token_file']);
        }
    }
    
    // =========================================================================
    // Gmail API - Messages
    // =========================================================================
    
    /**
     * Dohvati listu mailova
     */
    public function listMessages(array $params = []): array
    {
        $defaults = [
            'maxResults' => 20,
            'labelIds' => 'INBOX',
        ];
        $params = array_merge($defaults, $params);
        
        return $this->apiGet('/users/me/messages', $params);
    }
    
    /**
     * Dohvati jedan mail
     */
    public function getMessage(string $messageId, string $format = 'full'): array
    {
        return $this->apiGet("/users/me/messages/{$messageId}", ['format' => $format]);
    }
    
    /**
     * Dohvati mailove s parsiranim podacima
     */
    public function getMessagesWithDetails(array $params = []): array
    {
        $list = $this->listMessages($params);
        $messages = [];
        
        foreach ($list['messages'] ?? [] as $msg) {
            $full = $this->getMessage($msg['id']);
            $messages[] = $this->parseMessage($full);
        }
        
        return $messages;
    }
    
    /**
     * Parsiraj Gmail message u čitljiv format
     */
    public function parseMessage(array $message): array
    {
        $headers = [];
        foreach ($message['payload']['headers'] ?? [] as $header) {
            $headers[strtolower($header['name'])] = $header['value'];
        }
        
        return [
            'id' => $message['id'],
            'threadId' => $message['threadId'],
            'from' => $headers['from'] ?? '',
            'to' => $headers['to'] ?? '',
            'subject' => $headers['subject'] ?? '(no subject)',
            'date' => $headers['date'] ?? '',
            'timestamp' => isset($message['internalDate']) ? (int)($message['internalDate'] / 1000) : null,
            'snippet' => $message['snippet'] ?? '',
            'labels' => $message['labelIds'] ?? [],
            'isUnread' => in_array('UNREAD', $message['labelIds'] ?? []),
            'body' => $this->getMessageBody($message),
        ];
    }
    
    /**
     * Izvuci body iz poruke
     */
    private function getMessageBody(array $message): string
    {
        $payload = $message['payload'] ?? [];
        
        // Jednostavna poruka
        if (!empty($payload['body']['data'])) {
            return $this->decodeBody($payload['body']['data']);
        }
        
        // Multipart poruka
        foreach ($payload['parts'] ?? [] as $part) {
            if ($part['mimeType'] === 'text/plain' && !empty($part['body']['data'])) {
                return $this->decodeBody($part['body']['data']);
            }
        }
        
        // HTML fallback
        foreach ($payload['parts'] ?? [] as $part) {
            if ($part['mimeType'] === 'text/html' && !empty($part['body']['data'])) {
                return strip_tags($this->decodeBody($part['body']['data']));
            }
        }
        
        return '';
    }
    
    /**
     * Dekodiraj base64url encoded body
     */
    private function decodeBody(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Pošalji email
     */
    public function sendMessage(string $to, string $subject, string $body, array $options = []): array
    {
        $from = $options['from'] ?? $this->getUserEmail();
        $cc = $options['cc'] ?? '';
        $bcc = $options['bcc'] ?? '';
        $replyTo = $options['replyTo'] ?? '';
        $isHtml = $options['html'] ?? false;
        
        // Kreiraj MIME message
        $mime = "From: {$from}\r\n";
        $mime .= "To: {$to}\r\n";
        if ($cc) $mime .= "Cc: {$cc}\r\n";
        if ($bcc) $mime .= "Bcc: {$bcc}\r\n";
        if ($replyTo) $mime .= "Reply-To: {$replyTo}\r\n";
        $mime .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $mime .= "MIME-Version: 1.0\r\n";
        $mime .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
        $mime .= "Content-Transfer-Encoding: base64\r\n";
        $mime .= "\r\n";
        $mime .= base64_encode($body);
        
        $raw = rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');
        
        return $this->apiPost('/users/me/messages/send', ['raw' => $raw]);
    }
    
    /**
     * Označi poruku kao pročitanu
     */
    public function markAsRead(string $messageId): array
    {
        return $this->apiPost("/users/me/messages/{$messageId}/modify", [
            'removeLabelIds' => ['UNREAD'],
        ]);
    }
    
    /**
     * Označi poruku kao nepročitanu
     */
    public function markAsUnread(string $messageId): array
    {
        return $this->apiPost("/users/me/messages/{$messageId}/modify", [
            'addLabelIds' => ['UNREAD'],
        ]);
    }
    
    /**
     * Arhiviraj poruku (ukloni iz INBOX)
     */
    public function archiveMessage(string $messageId): array
    {
        return $this->apiPost("/users/me/messages/{$messageId}/modify", [
            'removeLabelIds' => ['INBOX'],
        ]);
    }
    
    /**
     * Obriši poruku (premjesti u TRASH)
     */
    public function trashMessage(string $messageId): array
    {
        return $this->apiPost("/users/me/messages/{$messageId}/trash", []);
    }
    
    // =========================================================================
    // Gmail API - Labels
    // =========================================================================
    
    /**
     * Dohvati sve labele
     */
    public function listLabels(): array
    {
        return $this->apiGet('/users/me/labels');
    }
    
    // =========================================================================
    // Gmail API - Profile
    // =========================================================================
    
    /**
     * Dohvati profil korisnika
     */
    public function getProfile(): array
    {
        return $this->apiGet('/users/me/profile');
    }
    
    /**
     * Dohvati email adresu korisnika
     */
    public function getUserEmail(): string
    {
        $profile = $this->getProfile();
        return $profile['emailAddress'] ?? '';
    }
    
    // =========================================================================
    // HTTP Helpers
    // =========================================================================
    
    private function apiGet(string $endpoint, array $params = []): array
    {
        $url = $this->config['api_base'] . $endpoint;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        
        return $this->httpRequest('GET', $url, null, $this->getAuthHeaders());
    }
    
    private function apiPost(string $endpoint, array $data): array
    {
        $url = $this->config['api_base'] . $endpoint;
        return $this->httpRequest('POST', $url, json_encode($data), $this->getAuthHeaders([
            'Content-Type: application/json',
        ]));
    }
    
    private function getAuthHeaders(array $extra = []): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            throw new RuntimeException('Not authorized. Call getAuthUrl() to start OAuth flow.');
        }
        
        return array_merge([
            'Authorization: Bearer ' . $token,
        ], $extra);
    }
    
    private function httpPost(string $url, array $data): array
    {
        return $this->httpRequest('POST', $url, http_build_query($data), [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
    }
    
    private function httpRequest(string $method, string $url, ?string $body, array $headers = []): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new RuntimeException("cURL error: {$error}");
        }
        
        $data = json_decode($response, true) ?? [];
        
        if ($httpCode >= 400) {
            $errorMsg = $data['error']['message'] ?? $data['error_description'] ?? $response;
            throw new RuntimeException("API error ({$httpCode}): {$errorMsg}");
        }
        
        return $data;
    }
    
    // =========================================================================
    // Token Storage
    // =========================================================================
    
    private function loadTokens(): void
    {
        $file = $this->config['token_file'];
        if (file_exists($file)) {
            $this->tokens = json_decode(file_get_contents($file), true);
        }
    }
    
    private function saveTokens(): void
    {
        $file = $this->config['token_file'];
        $dir = dirname($file);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($file, json_encode($this->tokens, JSON_PRETTY_PRINT));
        chmod($file, 0600); // Samo owner može čitati
    }
    
    /**
     * Dohvati sirove tokene (za debugging)
     */
    public function getTokens(): ?array
    {
        return $this->tokens;
    }
}
