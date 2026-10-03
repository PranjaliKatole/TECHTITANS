<?php
function categorize($text) {
    $t = strtolower($text);
    $map = [
        'Road/Pothole'   => ['pothole','road','crater','crack','highway','asphalt','speed breaker'],
        'Streetlight'    => ['streetlight','street light','lamp','light','dark','bulb'],
        'Garbage'        => ['garbage','trash','waste','dump','smell','litter','rubbish'],
        'Water/Drainage' => ['water','leak','pipe','sewage','drain','overflow','flood'],
    ];
    $best = 'Other'; $bestHits = 0;
    foreach ($map as $cat => $words) {
        $hits = 0;
        foreach ($words as $w) if (strpos($t, $w) !== false) $hits++;
        if ($hits > $bestHits) { $bestHits = $hits; $best = $cat; }
    }
    return $best;
}

function calcScore($category, $text, $reportCount, $daysOpen) {
    $t = strtolower($text);
    $base = ['Road/Pothole'=>30,'Streetlight'=>25,'Garbage'=>20,'Water/Drainage'=>30,'Other'=>15];
    $score = $base[$category] ?? 15;

    $danger = ['accident','deep','live wire','wire','children','kids','blocked','injur','urgent','dangerous','overflow','sewage'];
    $bonus = 0;
    foreach ($danger as $w) if (strpos($t, $w) !== false) $bonus += 8;
    $score += min($bonus, 20);

    foreach (['school','hospital','college','clinic'] as $w) {
        if (strpos($t, $w) !== false) { $score += 10; break; }
    }
    $score += min($reportCount * 5, 25);
    $score += min($daysOpen * 2, 15);
    return min($score, 100);
}

function severityFromScore($s) {
    if ($s >= 80) return 'Critical';
    if ($s >= 60) return 'High';
    if ($s >= 40) return 'Medium';
    return 'Low';
}

function saveImage($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Image upload failed');
    if ($file['size'] > 5 * 1024 * 1024) throw new Exception('Image must be under 5MB');
    $mime = mime_content_type($file['tmp_name']);
    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime] ?? null;
    if (!$ext) throw new Exception('Only JPG, PNG or WEBP images allowed');
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dir = __DIR__ . '/../uploads/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) throw new Exception('Could not save image');
    return 'uploads/' . $name;
}