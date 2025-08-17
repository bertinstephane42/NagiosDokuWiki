<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$config = require __DIR__ . '/../config/config.php';
$pdo = db($config);

// Démarre la session avant de récupérer l'utilisateur
start_secure_session($config);

// Récupère l'utilisateur courant
$user = current_user($pdo);

// Vérifie qu'il a le rôle admin ou superadmin
require_role($user, ['admin', 'superadmin']);

$logFile = __DIR__ . '/../logs/journal.log';

include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Journal des accès</h1>

<div class="card">
    <div style="overflow:auto; max-height:500px;">
        <table class="table">
            <thead>
                <tr>
                    <th>Date / Heure</th>
                    <th>IP</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Contexte</th>
                    <th>Agent utilisateur</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (file_exists($logFile)) {
                    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($lines as $line) {
                        $entry = json_decode($line, true);
                        if (!$entry) continue;
                        
                        $ts = $entry['ts'] ?? '';
                        $ip = $entry['ip'] ?? '';
                        $username = $entry['user'] ?? '';
                        $action = $entry['action'] ?? '';
                        $ctx = isset($entry['ctx']) && !empty($entry['ctx']) ? json_encode($entry['ctx'], JSON_UNESCAPED_SLASHES) : '-';
                        $ua = $entry['ua'] ?? '';
                        
                        // Badge couleur selon type d'action
                        switch(strtolower($action)){
                            case 'login': $badge='ok'; break;
                            case 'logout': $badge='warn'; break;
                            case 'totp_reset':
                            case 'delete': $badge='crit'; break;
                            default: $badge='unknown';
                        }
                        
                        echo '<tr class="row-'.$badge.'">';
                        echo '<td>' . htmlspecialchars($ts) . '</td>';
                        echo '<td>' . htmlspecialchars($ip) . '</td>';
                        echo '<td>' . htmlspecialchars($username) . '</td>';
                        echo '<td><span class="badge '.$badge.'">' . htmlspecialchars($action) . '</span></td>';
                        echo '<td>' . htmlspecialchars($ctx) . '</td>';
                        echo '<td>' . htmlspecialchars($ua) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6">Aucun journal trouvé.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>