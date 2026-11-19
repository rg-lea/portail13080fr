<?php
// upload.php : à placer à la racine du dépôt, à côté du dossier "files".
header('Content-Type: application/json; charset=utf-8');

const MAX_SIZE = 10 * 1024 * 1024;
const ALLOWED  = ['pdf','doc','docx','xls','xlsx','odt','ods','txt','csv','jpg','jpeg','png'];

function out($code, $data) { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok' => false, 'error' => 'méthode non autorisée']);
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) out(400, ['ok' => false, 'error' => 'aucun fichier reçu']);

$f = $_FILES['file'];
if ($f['size'] > MAX_SIZE) out(413, ['ok' => false, 'error' => 'fichier trop lourd (10 Mo maximum)']);

// Nom nettoyé : pas de chemin, pas de caractères spéciaux
$base = pathinfo(basename($f['name']), PATHINFO_FILENAME);
$ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED, true)) out(415, ['ok' => false, 'error' => 'format non accepté']);

$base = preg_replace('/[^A-Za-z0-9._-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $base));
$base = trim($base, '-.') ?: 'document';

$dir = __DIR__ . '/files';
if (!is_dir($dir) && !mkdir($dir, 0755, true)) out(500, ['ok' => false, 'error' => 'dossier files inaccessible']);

// Ne jamais écraser un fichier existant
$name = "$base.$ext";
for ($i = 1; file_exists("$dir/$name"); $i++) $name = "$base-$i.$ext";

if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) out(500, ['ok' => false, 'error' => "échec de l'enregistrement"]);

out(200, ['ok' => true, 'name' => $name]);
