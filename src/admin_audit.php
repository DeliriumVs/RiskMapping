<?php
// src/admin_audit.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

// Sécurité stricte
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || $_SESSION['admin_role'] !== 'admin') { 
    die("<div style='color:red; padding:20px;'>Accès refusé. Privilèges administrateur requis.</div>"); 
}

// On récupère les 200 derniers logs avec le nom de l'utilisateur concerné
$logs = $pdo->query("
    SELECT a.action, a.details, a.ip_address, a.created_at, u.username 
    FROM audit_logs a 
    LEFT JOIN admin_users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 200
")->fetchAll();
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">📜 Journal d'Audit Système</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Traçabilité des actions sensibles et des connexions.</p>

    <div style="max-height: 600px; overflow-y: auto;">
        <table style="width: 100%; border-collapse: collapse; font-family: monospace; font-size: 0.9rem;">
            <thead>
                <tr style="text-align: left; color: #8b949e; background: #0d1117; position: sticky; top: 0;">
                    <th style="padding: 10px; border-bottom: 1px solid #30363d;">Horodatage</th>
                    <th style="padding: 10px; border-bottom: 1px solid #30363d;">Utilisateur</th>
                    <th style="padding: 10px; border-bottom: 1px solid #30363d;">Événement</th>
                    <th style="padding: 10px; border-bottom: 1px solid #30363d;">Détails & IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="4" style="padding: 10px; color: #8b949e; text-align: center;">Aucun log enregistré.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        // Couleurs selon le type d'action
                        $color = '#c9d1d9';
                        if (strpos($log['action'], 'FAILED') !== false || strpos($log['action'], 'DELETE') !== false) {
                            $color = '#ff4d4d'; // Rouge
                        } elseif (strpos($log['action'], 'SUCCESS') !== false || strpos($log['action'], 'CREATE') !== false) {
                            $color = 'var(--accent-green)'; // Vert
                        }
                    ?>
                    <tr style="border-bottom: 1px solid #21262d;">
                        <td style="padding: 8px; color: #8b949e; white-space: nowrap;">
                            <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                        </td>
                        <td style="padding: 8px; color: #fff; font-weight: bold;">
                            <?= htmlspecialchars($log['username'] ?? 'Système/Anonyme') ?>
                        </td>
                        <td style="padding: 8px; color: <?= $color ?>; font-weight: bold;">
                            <?= htmlspecialchars($log['action']) ?>
                        </td>
                        <td style="padding: 8px; color: #c9d1d9;">
                            <?= htmlspecialchars($log['details']) ?>
                            <span style="display: block; font-size: 0.8rem; color: #484f58; margin-top: 3px;">
                                IP: <?= htmlspecialchars($log['ip_address']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
