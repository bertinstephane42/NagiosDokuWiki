<?php
require_once __DIR__ . '/../includes/auth.php';
$config = require __DIR__ . '/../config/config.php';
$pdo = db($config);

// Démarrage de la session et récupération de l'utilisateur courant
start_secure_session($config);
$user = current_user($pdo);

// Vérifie que l'utilisateur a le rôle requis
require_role($user, ['user', 'admin', 'superadmin']);

// Vérification état DokuWiki
$dokuwikiPath = realpath(__DIR__ . '/../../bts_sio/');
$etat = [];
if ($dokuwikiPath && is_dir($dokuwikiPath)) {
    $etat['Répertoire DokuWiki'] = 'OK';
    $pagesDir = $dokuwikiPath . '/data/pages';
    $mediaDir = $dokuwikiPath . '/data/media';
    $confDir = $dokuwikiPath . '/conf';

    $etat['Pages'] = (is_dir($pagesDir) && count(glob("$pagesDir/*")) > 0) ? 'OK' : 'Vide/Manquant';
    $etat['Médias'] = (is_dir($mediaDir) && count(glob("$mediaDir/*")) > 0) ? 'OK' : 'Vide/Manquant';
    $etat['Config'] = (is_dir($confDir) && count(glob("$confDir/*")) > 0) ? 'OK' : 'Manquante';
    $etat['Version PHP'] = phpversion();
    $diskFreeBytes = disk_free_space($dokuwikiPath);
$diskFreeGo = $diskFreeBytes / 1024 / 1024 / 1024; // Converti en Go

// Limite maximum à 100 Go
if ($diskFreeGo > 97) {
    $diskFreeGo = 97;
}

$etat['Espace disque'] = round($diskFreeGo, 2) . " Go libres";

    // --- Nouveau : Vérification des permissions ---
    $etat['Permissions pages'] = is_writable($pagesDir) ? 'OK' : 'Non inscriptible';
    $etat['Permissions media'] = is_writable($mediaDir) ? 'OK' : 'Non inscriptible';
    $etat['Permissions conf'] = is_writable($confDir) ? 'OK' : 'Non inscriptible';

    // --- Nouveau : Vérification des extensions PHP ---
    $extensions = ['gd', 'mbstring', 'xml', 'zip'];
    foreach ($extensions as $ext) {
        $etat["Extension $ext"] = extension_loaded($ext) ? 'OK' : 'Manquante';
    }

    // --- Nouveau : Vérification du fichier local.php ---
    $localConf = $confDir . '/local.php';
    $etat['Local Config'] = file_exists($localConf) ? 'OK' : 'Manquant';

    // --- Vérification version DokuWiki et comparaison avec la dernière version en ligne ---
$changelog = $dokuwikiPath . '/VERSION';
$localVersion = file_exists($changelog) ? trim(file_get_contents($changelog)) : 'Introuvable';
$etat['Version DokuWiki'] = $localVersion;

// Récupération de la dernière version en ligne
$latestVersionUrl = 'https://download.dokuwiki.org/src/dokuwiki/stable/VERSION'; // URL officielle
$latestVersion = @file_get_contents($latestVersionUrl);
$latestVersion = $latestVersion ? trim($latestVersion) : null;

// Définir un drapeau pour le badge selon comparaison
if ($latestVersion === null) {
    $etat['Version DokuWiki'] .= ' (Impossible de vérifier la version en ligne)';
    $versionBadge = 'warn';
} elseif ($localVersion === $latestVersion) {
    $etat['Version DokuWiki'] .= ' (à jour)';
    $versionBadge = 'ok';
} else {
    $etat['Version DokuWiki'] .= " (dernière : $latestVersion)";
    $versionBadge = 'warn';
}

} else {
    $etat['Répertoire DokuWiki'] = 'Introuvable';
}

include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">Supervision DokuWiki</h1>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Élément</th>
                <th>État</th>
            </tr>
        </thead>
        <tbody>
           <?php foreach ($etat as $cle => $val): 
    // Détermine la classe de badge
    if ($cle === 'Version DokuWiki') {
        $badgeClass = $versionBadge;
    } elseif ($val === 'OK' || strpos($val, 'Mo') !== false || strpos($val, 'Go') !== false || preg_match('/^[0-9.]+$/', $val)) {
        $badgeClass = 'ok';
    } elseif ($val === 'Vide/Manquant' || $val === 'Manquant' || $val === 'Non inscriptible') {
        $badgeClass = 'warn';
    } else {
        $badgeClass = 'crit';
    }
?>
            <tr class="row-<?= $badgeClass ?>">
                <td><?= htmlspecialchars($cle) ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($val) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>