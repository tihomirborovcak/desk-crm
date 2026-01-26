<?php
/**
 * Generiraj PWA ikone
 */

function generateIcon($size, $filename) {
    $img = imagecreatetruecolor($size, $size);

    // Boja pozadine (#2563eb)
    $bg = imagecolorallocate($img, 37, 99, 235);
    imagefill($img, 0, 0, $bg);

    // Bijela boja za tekst
    $white = imagecolorallocate($img, 255, 255, 255);

    // Tekst "CMS"
    $text = "CMS";
    $fontSize = $size / 4;

    // Centriraj tekst
    $bbox = imagettfbbox($fontSize, 0, 'C:/Windows/Fonts/arial.ttf', $text);
    $x = ($size - ($bbox[2] - $bbox[0])) / 2;
    $y = ($size + ($bbox[1] - $bbox[7])) / 2;

    imagettftext($img, $fontSize, 0, $x, $y, $white, 'C:/Windows/Fonts/arial.ttf', $text);

    imagepng($img, $filename);
    imagedestroy($img);

    echo "Generirana ikona: $filename ($size x $size)\n";
}

// Generiraj ikone
generateIcon(192, __DIR__ . '/assets/icons/icon-192.png');
generateIcon(512, __DIR__ . '/assets/icons/icon-512.png');

echo "\nGotovo! Ikone su spremljene u assets/icons/\n";
