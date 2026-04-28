<?php
// Tracking + cloaking d'un clic affiliation Amazon : /go/{slug-produit}?campaign=xxx
// Strategie de resolution :
//   1. Si /go/{slug} et le slug existe en table products -> utilise asin ou amazon_url + tag
//   2. Si ?asin=B0XXXXXXXX en parametre -> direct
//   3. Fallback : home Amazon France + tag

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Layout;

// Recuperation du slug : 3 sources possibles selon comment Apache route la requete
//   1. ?dest=xxx        (via mod_rewrite RewriteRule)
//   2. PATH_INFO        (FastCGI mange le slug en PATH_INFO et n'applique pas mod_rewrite)
//   3. parsing du REQUEST_URI brut en dernier recours
$dest = '';
if (isset($_GET['dest'])) {
    $dest = (string)$_GET['dest'];
}
if ($dest === '' && !empty($_SERVER['PATH_INFO'])) {
    $dest = trim((string)$_SERVER['PATH_INFO'], '/');
}
if ($dest === '' && !empty($_SERVER['REQUEST_URI'])) {
    $uri = (string)$_SERVER['REQUEST_URI'];
    $path = parse_url($uri, PHP_URL_PATH) ?: '';
    if (preg_match('#^/go/([a-z0-9\-_]+)#i', $path, $m)) {
        $dest = $m[1];
    }
}
$dest = preg_replace('/[^a-z0-9\-_]/', '', strtolower($dest));

$asinArg  = isset($_GET['asin']) ? strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$_GET['asin'])) : '';
$campaign = isset($_GET['campaign']) ? substr((string)$_GET['campaign'], 0, 100) : ($dest ?: 'go');
// Mode debug uniquement accessible aux admins connectes
$debug    = false;
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    try {
        $debug = \App\Core\Auth::isLoggedIn();
    } catch (Throwable $e) { $debug = false; }
}

$target = '';
$productId = null;
$debugInfo = [
    'dest_received'   => $dest,
    'get_dest'        => $_GET['dest'] ?? null,
    'path_info'       => $_SERVER['PATH_INFO'] ?? null,
    'request_uri'     => $_SERVER['REQUEST_URI'] ?? null,
    'asin_arg'        => $asinArg,
    'campaign'        => $campaign,
];

// 1. ASIN passe en parametre direct
if ($asinArg !== '' && strlen($asinArg) >= 10) {
    $target = Layout::amazonLink($asinArg, $campaign);
}

// 2. Resolution depuis la table products
if ($target === '' && $dest !== '') {
    try {
        $pdo = Database::pdo();
        $debugInfo['pdo_ok'] = true;
        $stmt = $pdo->prepare("SELECT id, slug, asin, amazon_url, status FROM products WHERE slug = ? LIMIT 1");
        $stmt->execute([$dest]);
        $row = $stmt->fetch();
        $debugInfo['row_found'] = $row ? true : false;
        if ($row) {
            $debugInfo['row'] = $row;
            $productId = (int)$row['id'];
            $asin = trim((string)($row['asin'] ?? ''));
            $url  = trim((string)($row['amazon_url'] ?? ''));
            $debugInfo['asin_extracted'] = $asin;
            $debugInfo['url_extracted']  = $url;
            $debugInfo['status'] = $row['status'] ?? '';
            if ($row['status'] !== 'published') {
                $debugInfo['skip_reason'] = 'status != published (=' . $row['status'] . ')';
            } elseif ($asin !== '') {
                $target = Layout::amazonLink($asin, $campaign);
                $debugInfo['target_built_from_asin'] = $target;
            } elseif ($url !== '') {
                $target = Layout::amazonLink($url, $campaign);
                $debugInfo['target_built_from_url'] = $target;
            } else {
                $debugInfo['skip_reason'] = 'asin et amazon_url vides';
            }
        }
    } catch (Throwable $e) {
        $debugInfo['error'] = $e->getMessage();
    }
}

// 3. Fallback secondaire : table settings (cle amazon_link_{slug})
if ($target === '' && $dest !== '') {
    try {
        $pdo = $pdo ?? Database::pdo();
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute(['amazon_link_' . $dest]);
        $val = $stmt->fetchColumn();
        if (is_string($val) && $val !== '') {
            $target = Layout::amazonLink($val, $campaign);
        }
    } catch (Throwable $e) {}
}

// 4. Fallback ultime : home Amazon avec tag
if ($target === '') {
    $target = Layout::amazonLink('https://www.amazon.fr', $campaign);
    $debugInfo['fallback_used'] = 'home_amazon';
}

$debugInfo['final_target'] = $target;

// Mode debug : on n'execute pas le redirect, on affiche l'etat
if ($debug) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($debugInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// Log click (best-effort)
try {
    $pdo = $pdo ?? Database::pdo();
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ipHash = hash('sha256', explode(',', $ip)[0]);
    $pdo->prepare(
        "INSERT INTO article_clicks (article_id, ip_hash, user_agent, referer, utm_source, utm_medium, utm_campaign)
         VALUES (NULL, ?, ?, ?, 'cafetiereagrain', 'affiliate', ?)"
    )->execute([
        $ipHash,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500),
        substr($campaign, 0, 100),
    ]);
} catch (Throwable $e) {}

header('Location: ' . $target, true, 302);
exit;
