<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid request']);
    exit;
}

// Sanitize inputs
function clean($v) { return htmlspecialchars(strip_tags(trim($v ?? ''))); }

$ime     = clean($_POST['ime'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$storitev = clean($_POST['storitev'] ?? '');
$datum   = clean($_POST['datum'] ?? '');
$gosti   = clean($_POST['gosti'] ?? '');
$sporocilo = clean($_POST['sporocilo'] ?? '');

// Basic validation
if (!$ime || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'msg' => 'Prosimo izpolni ime in veljaven email.']);
    exit;
}

$to      = 'bosbom.streetfood@gmail.com';
$subject = '=?UTF-8?B?' . base64_encode('Novo povpraševanje — ' . $storitev) . '?=';

$body  = "Novo povpraševanje s spletne strani:\n\n";
$body .= "Ime in priimek: $ime\n";
$body .= "Email: $email\n";
$body .= "Storitev: $storitev\n";
$body .= "Datum: $datum\n";
$body .= "Število gostov: $gosti\n\n";
$body .= "Sporočilo:\n$sporocilo\n";

$headers  = "From: =?UTF-8?B?" . base64_encode('Boš? Bom. Spletna stran') . "?= <bosbom.streetfood@gmail.com>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: base64\r\n";

$ok = mail($to, $subject, base64_encode($body), $headers);

// Also send confirmation to sender
if ($ok) {
    $subj2 = '=?UTF-8?B?' . base64_encode('Prejeli smo vaše povpraševanje — Boš? Bom.') . '?=';
    $body2  = "Pozdravljeni $ime,\n\n";
    $body2 .= "Hvala za vaše povpraševanje! Odgovorili bomo v 24 urah.\n\n";
    $body2 .= "Vaši podatki:\n";
    $body2 .= "Storitev: $storitev\n";
    $body2 .= "Datum: $datum\n";
    $body2 .= "Sporočilo: $sporocilo\n\n";
    $body2 .= "Lep pozdrav,\nEkipa Boš? Bom.";
    $h2  = "From: =?UTF-8?B?" . base64_encode('Boš? Bom.') . "?= <bosbom.streetfood@gmail.com>\r\n";
    $h2 .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $h2 .= "Content-Transfer-Encoding: base64\r\n";
    mail($email, $subj2, base64_encode($body2), $h2);
}

echo json_encode(['ok' => $ok, 'msg' => $ok ? 'success' : 'Napaka pri pošiljanju. Pokliči nas na 040 744 174.']);
