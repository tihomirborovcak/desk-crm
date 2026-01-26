<?php
/**
 * Helper funkcije
 */

/**
 * Escape HTML
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generiraj slug
 */
function slugify($text) {
    // Hrvatski karakteri
    $hr = ['č', 'ć', 'đ', 'š', 'ž', 'Č', 'Ć', 'Đ', 'Š', 'Ž'];
    $en = ['c', 'c', 'd', 's', 'z', 'c', 'c', 'd', 's', 'z'];
    $text = str_replace($hr, $en, $text);
    
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    return $text ?: 'n-a';
}

/**
 * Formatiraj datum
 */
function formatDate($date, $format = 'd.m.Y.') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Formatiraj datum i vrijeme
 */
function formatDateTime($date, $format = 'd.m.Y. H:i') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Relativno vrijeme (prije X minuta/sati)
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'upravo sada';
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' h';
    if ($diff < 604800) return floor($diff / 86400) . ' d';
    
    return formatDate($datetime);
}

/**
 * Skrati tekst
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Upload slike
 */
function uploadImage($file, $subdir = 'photos') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Greška pri uploadu: ' . $file['error']);
    }
    
    // Provjera veličine
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('Datoteka je prevelika (max 5MB)');
    }
    
    // Provjera ekstenzije
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        throw new Exception('Nedozvoljena vrsta datoteke');
    }
    
    // Provjera MIME tipa
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes)) {
        throw new Exception('Nedozvoljena vrsta datoteke');
    }
    
    // Generiraj ime datoteke
    $filename = date('Y-m-d_His_') . bin2hex(random_bytes(4)) . '.' . $ext;
    $uploadDir = UPLOAD_PATH . $subdir . '/' . date('Y/m/');
    
    // Kreiraj direktorij ako ne postoji
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filepath = $uploadDir . $filename;
    
    // Premjesti datoteku
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Greška pri spremanju datoteke');
    }
    
    // Dohvati dimenzije
    $imageInfo = getimagesize($filepath);
    
    // Kreiraj thumbnail
    $thumbnail = createThumbnail($filepath, $ext, 300, 200);
    
    return [
        'filename' => $filename,
        'original_name' => $file['name'],
        'filepath' => str_replace(UPLOAD_PATH, UPLOAD_URL, $filepath),
        'thumbnail' => $thumbnail,
        'mime_type' => $mimeType,
        'file_size' => $file['size'],
        'width' => $imageInfo[0] ?? null,
        'height' => $imageInfo[1] ?? null,
        'is_image' => true
    ];
}

/**
 * Upload bilo koje datoteke (slike ili dokumenti)
 */
function uploadFile($file, $subdir = 'attachments') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Greška pri uploadu: ' . $file['error']);
    }

    // Provjera veličine (10MB za dokumente)
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('Datoteka je prevelika (max 10MB)');
    }

    // Dozvoljene ekstenzije
    $allowedExt = [
        // Slike
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        // Dokumenti
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'odt', 'ods'
    ];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        throw new Exception('Nedozvoljena vrsta datoteke');
    }

    // Provjera MIME tipa
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        // Slike
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        // Dokumenti
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'application/rtf',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet'
    ];

    if (!in_array($mimeType, $allowedMimes)) {
        throw new Exception('Nedozvoljena vrsta datoteke');
    }

    // Generiraj ime datoteke
    $filename = date('Y-m-d_His_') . bin2hex(random_bytes(4)) . '.' . $ext;
    $uploadDir = UPLOAD_PATH . $subdir . '/' . date('Y/m/');

    // Kreiraj direktorij ako ne postoji
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filepath = $uploadDir . $filename;

    // Premjesti datoteku
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Greška pri spremanju datoteke');
    }

    // Provjeri je li slika
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    $width = null;
    $height = null;
    $thumbnail = null;

    if ($isImage) {
        $imageInfo = getimagesize($filepath);
        $width = $imageInfo[0] ?? null;
        $height = $imageInfo[1] ?? null;
        $thumbnail = createThumbnail($filepath, $ext, 300, 200);
    }

    return [
        'filename' => $filename,
        'original_name' => $file['name'],
        'filepath' => str_replace(UPLOAD_PATH, UPLOAD_URL, $filepath),
        'thumbnail' => $thumbnail,
        'mime_type' => $mimeType,
        'file_size' => $file['size'],
        'width' => $width,
        'height' => $height,
        'is_image' => $isImage
    ];
}

/**
 * Vrati ikonu za tip datoteke
 */
function getFileIcon($mimeType, $ext = '') {
    if (strpos($mimeType, 'image/') === 0) {
        return 'image';
    } elseif ($mimeType === 'application/pdf') {
        return 'pdf';
    } elseif (strpos($mimeType, 'word') !== false || $ext === 'doc' || $ext === 'docx' || $ext === 'odt' || $ext === 'rtf') {
        return 'word';
    } elseif (strpos($mimeType, 'excel') !== false || strpos($mimeType, 'spreadsheet') !== false || $ext === 'xls' || $ext === 'xlsx' || $ext === 'ods') {
        return 'excel';
    } elseif (strpos($mimeType, 'powerpoint') !== false || strpos($mimeType, 'presentation') !== false || $ext === 'ppt' || $ext === 'pptx') {
        return 'powerpoint';
    } elseif ($mimeType === 'text/plain') {
        return 'text';
    }
    return 'file';
}

/**
 * Kreiraj thumbnail
 */
function createThumbnail($sourcePath, $ext, $maxWidth = 300, $maxHeight = 200) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) return null;
    
    list($width, $height) = $imageInfo;
    
    // Izračunaj nove dimenzije
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    
    // Kreiraj source image
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case 'png':
            $source = imagecreatefrompng($sourcePath);
            break;
        case 'gif':
            $source = imagecreatefromgif($sourcePath);
            break;
        case 'webp':
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return null;
    }
    
    if (!$source) return null;
    
    // Kreiraj thumbnail
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    
    // Sačuvaj transparentnost za PNG
    if ($ext === 'png') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Spremi thumbnail
    $thumbPath = str_replace('.' . $ext, '_thumb.' . $ext, $sourcePath);
    
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($thumb, $thumbPath, 85);
            break;
        case 'png':
            imagepng($thumb, $thumbPath, 8);
            break;
        case 'gif':
            imagegif($thumb, $thumbPath);
            break;
        case 'webp':
            imagewebp($thumb, $thumbPath, 85);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($thumb);
    
    return str_replace(UPLOAD_PATH, UPLOAD_URL, $thumbPath);
}

/**
 * Poruka (flash message)
 */
function setMessage($type, $text) {
    $_SESSION['flash_message'] = ['type' => $type, 'text' => $text];
}

function getMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * Prijevod statusa taska
 */
function translateTaskStatus($status) {
    $statuses = [
        'pending' => 'Čeka',
        'in_progress' => 'U tijeku',
        'done' => 'Završeno',
        'cancelled' => 'Otkazano'
    ];
    return $statuses[$status] ?? $status;
}

/**
 * Boja statusa taska
 */
function taskStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'in_progress' => 'info',
        'done' => 'success',
        'cancelled' => 'secondary'
    ];
    return $colors[$status] ?? 'secondary';
}

/**
 * Prijevod prioriteta
 */
function translatePriority($priority) {
    $priorities = [
        'low' => 'Nizak',
        'normal' => 'Normalan',
        'high' => 'Visok',
        'urgent' => 'Hitno'
    ];
    return $priorities[$priority] ?? $priority;
}

/**
 * Boja prioriteta
 */
function priorityColor($priority) {
    $colors = [
        'low' => 'secondary',
        'normal' => 'info',
        'high' => 'warning',
        'urgent' => 'danger'
    ];
    return $colors[$priority] ?? 'secondary';
}

/**
 * Prijevod tipa eventa
 */
function translateEventType($type) {
    $types = [
        'press' => 'Press konferencija',
        'sport' => 'Sport',
        'kultura' => 'Kultura',
        'politika' => 'Politika',
        'drustvo' => 'Društvo',
        'dezurstvo' => 'Dežurstvo',
        'ostalo' => 'Ostalo'
    ];
    return $types[$type] ?? $type;
}

/**
 * Boja tipa eventa
 */
function eventTypeColor($type) {
    $colors = [
        'press' => 'danger',
        'sport' => 'success',
        'kultura' => 'purple',
        'politika' => 'warning',
        'drustvo' => 'info',
        'dezurstvo' => 'teal',
        'ostalo' => 'secondary'
    ];
    return $colors[$type] ?? 'secondary';
}

/**
 * Prijevod važnosti eventa
 */
function translateImportance($importance) {
    $levels = [
        'normal' => 'Normalno',
        'important' => 'Važno',
        'must_cover' => 'Obavezno pokriti'
    ];
    return $levels[$importance] ?? $importance;
}

/**
 * Prijevod uloge na eventu
 */
function translateEventRole($role) {
    $roles = [
        'reporter' => 'Novinar',
        'photographer' => 'Fotograf',
        'camera' => 'Snimatelj',
        'backup' => 'Rezerva'
    ];
    return $roles[$role] ?? $role;
}

/**
 * Prijevod uloge
 */
function translateRole($role) {
    $roles = [
        'novinar' => 'Novinar',
        'urednik' => 'Urednik',
        'admin' => 'Administrator'
    ];
    return $roles[$role] ?? $role;
}

/**
 * Prijevod tipa smjene
 */
function translateShift($type) {
    $shifts = [
        'morning' => 'Jutarnja',
        'afternoon' => 'Popodnevna',
        'full' => 'Cijeli dan'
    ];
    return $shifts[$type] ?? $type;
}

/**
 * Dohvati sve kategorije
 */
function getCategories() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM categories WHERE active = 1 ORDER BY sort_order");
    return $stmt->fetchAll();
}

/**
 * Dohvati sve aktivne korisnike
 */
function getUsers($role = null) {
    $db = getDB();
    
    if ($role) {
        $stmt = $db->prepare("SELECT * FROM users WHERE active = 1 AND role = ? ORDER BY full_name");
        $stmt->execute([$role]);
    } else {
        $stmt = $db->query("SELECT * FROM users WHERE active = 1 ORDER BY full_name");
    }
    
    return $stmt->fetchAll();
}

/**
 * Redirect s porukom
 */
function redirectWith($url, $type, $message) {
    setMessage($type, $message);
    header('Location: ' . $url);
    exit;
}

/**
 * JSON response
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Provjera je li AJAX request
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Prijevod statusa članka ZL
 */
function translateArticleStatus($status) {
    $map = [
        'nacrt' => 'Nacrt',
        'za_pregled' => 'Za pregled',
        'odobreno' => 'Odobreno',
        'odbijeno' => 'Odbijeno',
        'objavljeno' => 'Objavljeno'
    ];
    return $map[$status] ?? $status;
}

/**
 * Boja statusa članka ZL
 */
function articleStatusColor($status) {
    $map = [
        'nacrt' => '#6b7280',
        'za_pregled' => '#f59e0b',
        'odobreno' => '#10b981',
        'odbijeno' => '#ef4444',
        'objavljeno' => '#3b82f6'
    ];
    return $map[$status] ?? '#6b7280';
}

/**
 * Generiraj sigurnu lozinku
 */
function generatePassword($length = 10) {
    $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Pošalji email putem SMTP
 */
function sendEmail($to, $subject, $body, $isHtml = true) {
    // Učitaj PHPMailer
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // SMTP postavke
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // Pošiljatelj i primatelj
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);

        // Sadržaj
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body = $body;

        if ($isHtml) {
            $mail->AltBody = strip_tags($body);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Pošalji pristupne podatke korisniku
 */
function sendCredentials($userId, $newPassword = null) {
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Korisnik nije pronađen'];
    }

    // Ako nije proslijeđena lozinka, generiraj novu
    if ($newPassword === null) {
        $newPassword = generatePassword(10);
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
    }

    $loginUrl = SITE_URL . '/index.php';

    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
            .credentials { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #e5e7eb; }
            .credentials p { margin: 10px 0; }
            .label { color: #6b7280; font-size: 12px; text-transform: uppercase; }
            .value { font-size: 18px; font-weight: bold; color: #1f2937; }
            .button { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
            .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Portal CMS</h1>
            </div>
            <div class='content'>
                <p>Poštovani/a <strong>{$user['full_name']}</strong>,</p>
                <p>Kreirani su vaši pristupni podaci za Portal CMS sustav:</p>

                <div class='credentials'>
                    <p>
                        <span class='label'>Korisničko ime:</span><br>
                        <span class='value'>{$user['username']}</span>
                    </p>
                    <p>
                        <span class='label'>Lozinka:</span><br>
                        <span class='value'>{$newPassword}</span>
                    </p>
                </div>

                <p>Link za prijavu:</p>
                <a href='{$loginUrl}' class='button'>Prijavi se</a>

                <p style='margin-top: 30px; color: #6b7280; font-size: 14px;'>
                    Preporučujemo da nakon prve prijave promijenite lozinku.
                </p>
            </div>
            <div class='footer'>
                <p>Portal CMS - " . date('Y') . "</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $sent = sendEmail($user['email'], 'Pristupni podaci za Portal CMS', $body);

    if ($sent) {
        logActivity('credentials_sent', 'user', $userId);
        return ['success' => true, 'message' => 'Pristupni podaci poslani na ' . $user['email']];
    } else {
        return ['success' => false, 'message' => 'Greška pri slanju emaila'];
    }
}
