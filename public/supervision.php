<?php
require_once __DIR__ . '/../includes/auth.php';
$config = require __DIR__ . '/../config/config.php';
$pdo = db($config);

start_secure_session($config);
$user = current_user($pdo);
require_role($user, ['user', 'admin', 'superadmin']);

$dokuwikiPath = realpath(__DIR__ . '/../../bts_sio/');
$etat = [];
$valeur = [];

if ($dokuwikiPath && is_dir($dokuwikiPath)) {
    $etat['Répertoire DokuWiki'] = 'OK';
    $valeur['Répertoire DokuWiki'] = $dokuwikiPath;

    $pagesDir = $dokuwikiPath . '/data/pages';
    $mediaDir = $dokuwikiPath . '/data/media';
    $confDir = $dokuwikiPath . '/conf';

    $etat['Pages'] = (is_dir($pagesDir) && count(glob("$pagesDir/*")) > 0) ? 'OK' : 'Vide/Manquant';
    $valeur['Pages'] = count(glob("$pagesDir/*")) . ' fichiers';

    $etat['Médias'] = (is_dir($mediaDir) && count(glob("$mediaDir/*")) > 0) ? 'OK' : 'Vide/Manquant';
    $valeur['Médias'] = count(glob("$mediaDir/*")) . ' fichiers';

    $etat['Config'] = (is_dir($confDir) && count(glob("$confDir/*")) > 0) ? 'OK' : 'Manquante';
    $valeur['Config'] = count(glob("$confDir/*")) . ' fichiers';

    $etat['Version PHP'] = 'OK';
    $valeur['Version PHP'] = phpversion();

    $diskFreeBytes = disk_free_space($dokuwikiPath);
    $diskFreeGo = round($diskFreeBytes / 1024 / 1024 / 1024, 2);
    if ($diskFreeGo > 97) $diskFreeGo = 97;
    $etat['Espace disque'] = 'OK';
    $valeur['Espace disque'] = $diskFreeGo . " Go libres";

    $etat['Permissions pages'] = is_writable($pagesDir) ? 'OK' : 'Non inscriptible';
    $valeur['Permissions pages'] = is_writable($pagesDir) ? 'Writable' : 'Read-only';

    $etat['Permissions media'] = is_writable($mediaDir) ? 'OK' : 'Non inscriptible';
    $valeur['Permissions media'] = is_writable($mediaDir) ? 'Writable' : 'Read-only';

    $etat['Permissions conf'] = is_writable($confDir) ? 'OK' : 'Non inscriptible';
    $valeur['Permissions conf'] = is_writable($confDir) ? 'Writable' : 'Read-only';

    $extensions = ['gd', 'mbstring', 'xml', 'zip'];
    foreach ($extensions as $ext) {
        $etat["Extension $ext"] = extension_loaded($ext) ? 'OK' : 'Manquante';
        $valeur["Extension $ext"] = extension_loaded($ext) ? 'Loaded' : 'Missing';
    }

    $localConf = $confDir . '/local.php';
    $etat['Local Config'] = file_exists($localConf) ? 'OK' : 'Manquant';
    $valeur['Local Config'] = file_exists($localConf) ? 'Fichier présent' : 'Fichier absent';

    $changelog = $dokuwikiPath . '/VERSION';
    $localVersion = file_exists($changelog) ? trim(file_get_contents($changelog)) : 'Introuvable';
    $etat['Version DokuWiki'] = 'OK';
    $valeur['Version DokuWiki'] = $localVersion;

    $latestVersionUrl = 'https://download.dokuwiki.org/src/dokuwiki/stable/VERSION';
    $latestVersion = @file_get_contents($latestVersionUrl);
    $latestVersion = $latestVersion ? trim($latestVersion) : null;

    if ($latestVersion === null) {
        $valeur['Version DokuWiki'] .= ' (Impossible de vérifier la version en ligne)';
        $versionBadge = 'warn';
    } elseif ($localVersion === $latestVersion) {
        $valeur['Version DokuWiki'] .= ' (à jour)';
        $versionBadge = 'ok';
    } else {
        $valeur['Version DokuWiki'] .= " (dernière : $latestVersion)";
        $versionBadge = 'warn';
    }
} else {
    $etat['Répertoire DokuWiki'] = 'Introuvable';
    $valeur['Répertoire DokuWiki'] = $dokuwikiPath ?: 'Path non trouvé';
}

include __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1 class="page-title">Tableau de supervision</h1>
    <div class="legend">
      <span class="badge ok">OK</span>
      <span class="badge warn">WARN</span>
      <span class="badge crit">CRIT</span>
      <span class="badge unknown">UNKNOWN</span>
    </div>
  </section>
<h1>DokuWiki — État du système</h1>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Élément</th>
                <th>État</th>
                <th>Valeur</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($etat as $cle => $val):
            if ($cle === 'Version DokuWiki') {
                $badgeClass = $versionBadge;
            } elseif ($val === 'OK') {
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
                <td>
                    <span class="badge <?= $badgeClass ?>" title="<?= htmlspecialchars($valeur[$cle]) ?>">
                        <?= htmlspecialchars($valeur[$cle]) ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
