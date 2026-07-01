<?php
// src/admin_backup.php
session_start();
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || $admin_role !== 'admin') {
    die("<div style='color:red; padding:20px;'>Accès réservé aux administrateurs.</div>");
}
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">💾 Sauvegardes & Restauration</h2>
    <p style="color: #8b949e; margin-bottom: 24px;">Exportez l'ensemble des données pour une sauvegarde manuelle, un transfert d'instance ou la création d'un jeu de démo. Les comptes administrateurs ne sont jamais écrasés lors d'un import.</p>

    <div id="backup-message" style="display:none; padding:12px; border-radius:6px; margin-bottom:20px;"></div>

    <!-- ===== EXPORT ===== -->
    <div style="background:#0d1117; border:1px solid #30363d; border-radius:8px; padding:20px; margin-bottom:24px;">
        <h3 style="color:#3b82f6; margin-top:0;">📤 Exporter les données</h3>
        <p style="color:#8b949e; font-size:0.9rem; margin-bottom:16px;">Téléchargez l'intégralité de la base dans le format de votre choix.</p>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <a href="api_backup.php?action=export&format=json"
               style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; background:rgba(59,130,246,0.1); color:#3b82f6; border:1px solid #3b82f6; border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9rem;">
                ⬇️ JSON
                <span style="font-size:0.75rem; font-weight:normal; color:#8b949e;">Recommandé — ré-importable</span>
            </a>
            <a href="api_backup.php?action=export&format=sql"
               style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; background:rgba(249,115,22,0.1); color:#f97316; border:1px solid #f97316; border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9rem;">
                ⬇️ SQL
                <span style="font-size:0.75rem; font-weight:normal; color:#8b949e;">Compatible MySQL / MariaDB</span>
            </a>
        </div>
    </div>

    <!-- ===== IMPORT ===== -->
    <div style="background:#0d1117; border:1px solid rgba(218,41,28,0.4); border-radius:8px; padding:20px;">
        <h3 style="color:var(--accent-red); margin-top:0;">📥 Restaurer depuis une sauvegarde</h3>

        <div style="background:rgba(218,41,28,0.08); border:1px solid rgba(218,41,28,0.3); border-radius:6px; padding:12px 16px; margin-bottom:20px; font-size:0.88rem; color:#f87171; line-height:1.6;">
            ⚠️ <strong>Attention :</strong> cette opération <strong>remplace définitivement</strong> toutes les données existantes (scénarios, ateliers, référentiels…) par celles du fichier sélectionné.<br>
            Les comptes administrateurs ne sont <strong>jamais</strong> modifiés. Format accepté : <code>.json</code> uniquement.
        </div>

        <form id="form-import" enctype="multipart/form-data">
            <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                <div style="flex:1; min-width:220px;">
                    <label style="display:block; font-size:0.8rem; color:#8b949e; margin-bottom:6px;">Fichier de sauvegarde (.json)</label>
                    <input type="file" id="backup-file" name="backup_file" accept=".json" required
                        style="width:100%; box-sizing:border-box; padding:9px; background:#161b22; border:1px solid #30363d; color:#c9d1d9; border-radius:4px; font-size:0.9rem;">
                </div>
                <button type="submit" id="btn-import"
                    style="padding:10px 20px; background:rgba(218,41,28,0.15); color:var(--accent-red); border:1px solid var(--accent-red); border-radius:6px; cursor:pointer; font-weight:bold; font-size:0.9rem; white-space:nowrap;">
                    🔁 Restaurer
                </button>
            </div>
            <div style="margin-top:12px;">
                <label style="display:flex; align-items:center; gap:8px; color:#8b949e; font-size:0.85rem; cursor:pointer;">
                    <input type="checkbox" id="confirm-import" style="margin:0;">
                    Je comprends que cette action est irréversible et j'ai vérifié le fichier sélectionné.
                </label>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const msgBox  = document.getElementById('backup-message');
    const btnImp  = document.getElementById('btn-import');
    const confirm = document.getElementById('confirm-import');

    function showMsg(text, isError) {
        msgBox.style.display = 'block';
        msgBox.textContent   = text;
        msgBox.style.backgroundColor = isError ? 'rgba(255,68,68,0.15)' : 'rgba(0,230,184,0.1)';
        msgBox.style.color   = isError ? '#ff4d4d' : 'var(--accent-green)';
        msgBox.style.border  = `1px solid ${isError ? '#ff4d4d' : 'var(--accent-green)'}`;
    }

    document.getElementById('form-import').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!confirm.checked) {
            showMsg('Veuillez cocher la case de confirmation avant de restaurer.', true);
            return;
        }

        const fileInput = document.getElementById('backup-file');
        if (!fileInput.files[0]) {
            showMsg('Aucun fichier sélectionné.', true);
            return;
        }

        btnImp.disabled   = true;
        btnImp.textContent = '⏳ Import en cours…';

        const formData = new FormData();
        formData.append('backup_file', fileInput.files[0]);

        try {
            const res  = await fetch('api_backup.php?action=import', { method: 'POST', body: formData });
            const json = await res.json();
            if (json.status === 'success') {
                showMsg('✅ ' + json.message, false);
                this.reset();
            } else {
                showMsg('❌ ' + json.message, true);
            }
        } catch(err) {
            showMsg('❌ Erreur réseau lors de l\'import.', true);
        } finally {
            btnImp.disabled   = false;
            btnImp.textContent = '🔁 Restaurer';
        }
    });
})();
</script>
