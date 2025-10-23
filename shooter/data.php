<?php
declare(strict_types=1);
require_once __DIR__ . '/connection.php';

// Load players
try {
    $pdo = db();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS players (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            phone VARCHAR(30) NOT NULL,
            score INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_phone (phone)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $col = $pdo->query("SELECT COUNT(*) AS c
                        FROM information_schema.COLUMNS
                        WHERE TABLE_SCHEMA = DATABASE()
                          AND TABLE_NAME = 'players'
                          AND COLUMN_NAME = 'attempts'")->fetch();
    if ((int)($col['c'] ?? 0) === 0) {
        $pdo->exec("ALTER TABLE players ADD COLUMN attempts INT NOT NULL DEFAULT 0 AFTER score");
    }
    // default: sort by score desc, then created_at asc
    $players = $pdo->query("SELECT id, full_name, phone, score, attempts, created_at FROM players ORDER BY score DESC, created_at ASC")->fetchAll() ?: [];
} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre>DB ERROR: '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'</pre>';
    exit;
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function wa_link(string $phone): string {
    // Normalize to Indonesian international format for wa.me
    $p = preg_replace('/[\s\-\(\)\.]/', '', $phone);
    if (str_starts_with($p, '+62')) $p = '62' . substr($p, 3);
    elseif (str_starts_with($p, '62')) $p = $p;
    elseif (str_starts_with($p, '0')) $p = '62' . substr($p, 1);
    // only digits
    $p = preg_replace('/\D+/', '', $p);
    return 'https://wa.me/' . $p;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Data Pemain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background:#0b1020; color:#e8f0ff; }
        .table thead th { color:#5de4c7; border-color:#334155; }
        .table tbody td { border-color:#334155; color:#e8f0ff; }
        .btn-edit { --bs-btn-bg:#0ea5e9; --bs-btn-border-color:#0284c7; --bs-btn-hover-bg:#0284c7; }
        .container-narrow { max-width: 960px; }
        a.wa { color:#93f; text-decoration:none; }
        a.wa:hover { text-decoration:underline; }
        .btn-copy-phone {
            background: transparent;
            border: none;
            color: #93f;
            cursor: pointer;
            padding: 0 0 0 8px;
            transition: color 0.2s;
        }
        .btn-copy-phone:hover { color: #b5f; }
        .btn-copy-phone.copied { color: #5de4c7; }
    </style>
</head>
<body>
    <div class="container container-narrow py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="m-0">Daftar Pemain</h3>
            <a href="index.php" class="btn btn-secondary">← Kembali ke Game</a>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle">
                <thead>
                    <tr>
                    <th style="width:70px">ID</th>
                    <th>Nama Lengkap</th>
                    <th>Nomor WhatsApp</th>
                    <th style="width:120px">Skor</th>
                    <th style="width:120px">Percobaan</th>
                    <th style="width:170px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$players): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data</td></tr>
                    <?php else: ?>
                    <?php foreach ($players as $p): ?>
                        <tr data-id="<?= (int)$p['id'] ?>">
                        <td><?= (int)$p['id'] ?></td>
                        <td class="fullname"><?= h($p['full_name']) ?></td>
                        <td class="phone">
                            <?php $plink = wa_link((string)$p['phone']); ?>
                            <a class="wa" href="<?= h($plink) ?>" target="_blank" rel="noopener noreferrer">
                            <?= h($p['phone']) ?>
                            </a>
                            <button type="button" class="btn-copy-phone" 
                                    data-phone="<?= h($p['phone']) ?>"
                                    title="Copy nomor">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </td>
                        <td class="score"><?= (int)$p['score'] ?></td>
                        <td class="attempts"><?= (int)$p['attempts'] ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-primary btn-edit"
                                        data-id="<?= (int)$p['id'] ?>"
                                        data-full_name="<?= h($p['full_name']) ?>"
                                        data-phone="<?= h($p['phone']) ?>"
                                        data-score="<?= (int)$p['score'] ?>"
                                        data-attempts="<?= (int)$p['attempts'] ?>">
                                    Edit
                                </button>
                                <button type="button" class="btn btn-sm btn-danger btn-delete"
                                        data-id="<?= (int)$p['id'] ?>"
                                        data-full_name="<?= h($p['full_name']) ?>">
                                    Delete
                                </button>
                            </div>
                        </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade text-black" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pemain</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="f_id">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="f_full_name" required minlength="3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor WhatsApp</label>
                        <input type="tel" class="form-control" id="f_phone" required>
                        <div class="form-text">Contoh: 08xxxxxxxxxx atau +62xxxxxxxxxx</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Skor</label>
                        <input type="number" class="form-control" id="f_score" min="0" step="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Percobaan</label>
                        <input type="number" class="form-control" id="f_attempts" min="0" step="1">
                    </div>
                    <div id="editErr" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100" id="btnSaveEdit">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        (function(){
            const editModalEl = document.getElementById('editModal');
            const errBox = document.getElementById('editErr');
            const f_id = document.getElementById('f_id');
            const f_full_name = document.getElementById('f_full_name');
            const f_phone = document.getElementById('f_phone');
            const f_score = document.getElementById('f_score');
            const f_attempts = document.getElementById('f_attempts');
            const btnSaveEdit = document.getElementById('btnSaveEdit');
            let editModal;

            function openEdit(rowBtn) {
                const id = rowBtn.getAttribute('data-id');
                const full_name = rowBtn.getAttribute('data-full_name') || '';
                const phone = rowBtn.getAttribute('data-phone') || '';
                const score = rowBtn.getAttribute('data-score') || '0';
                const attempts = rowBtn.getAttribute('data-attempts') || '0';

                f_id.value = id;
                f_full_name.value = full_name;
                f_phone.value = phone;
                f_score.value = score;
                f_attempts.value = attempts;

                if (!editModal) editModal = new bootstrap.Modal(editModalEl);
                errBox.classList.add('d-none');
                errBox.textContent = '';
                editModal.show();
            }

            async function deletePlayer(id, fullName) {
                if (!confirm(`Yakin ingin menghapus pemain "${fullName}"? Tindakan ini tidak dapat dibatalkan.`)) {
                    return;
                }

                try {
                    const res = await fetch('delete.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ id }).toString()
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.ok) throw new Error(data.error || 'Gagal menghapus.');
                    
                    location.reload();
                } catch (err) {
                    alert('Error: ' + err.message);
                }
            }

            function copyToClipboard(text, button) {
                navigator.clipboard.writeText(text).then(() => {
                    const icon = button.querySelector('i');
                    const originalClass = icon.className;
                    
                    icon.className = 'bi bi-clipboard-check';
                    button.classList.add('copied');
                    
                    setTimeout(() => {
                        icon.className = originalClass;
                        button.classList.remove('copied');
                    }, 2000);
                }).catch(err => {
                    alert('Gagal menyalin: ' + err.message);
                });
            }

            document.addEventListener('click', (e) => {
                const editBtn = e.target.closest('.btn-edit');
                if (editBtn) {
                    openEdit(editBtn);
                    return;
                }
                
                const delBtn = e.target.closest('.btn-delete');
                if (delBtn) {
                    const id = delBtn.getAttribute('data-id');
                    const fullName = delBtn.getAttribute('data-full_name') || '';
                    deletePlayer(id, fullName);
                    return;
                }

                const copyBtn = e.target.closest('.btn-copy-phone');
                if (copyBtn) {
                    const phone = copyBtn.getAttribute('data-phone');
                    copyToClipboard(phone, copyBtn);
                    return;
                }
            });

            // Handler untuk tombol Save Edit
            btnSaveEdit.addEventListener('click', async () => {
                errBox.classList.add('d-none');
                errBox.textContent = '';

                const id = f_id.value;
                const full_name = f_full_name.value.trim();
                let phone = f_phone.value.trim();
                const score = f_score.value.trim();
                const attempts = f_attempts.value.trim();

                if (full_name.length < 3) {
                    errBox.textContent = 'Nama minimal 3 karakter.';
                    errBox.classList.remove('d-none');
                    return;
                }
                
                phone = phone.replace(/[\s().-]/g, '');
                if (phone.startsWith('+62')) phone = '0' + phone.slice(3);
                if (phone.startsWith('62'))  phone = '0' + phone.slice(2);
                if (!/^0\d{8,14}$/.test(phone)) {
                    errBox.textContent = 'Nomor WhatsApp tidak valid.';
                    errBox.classList.remove('d-none');
                    return;
                }

                try {
                    const res = await fetch('save.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action:'update', id, full_name, phone, score, attempts }).toString()
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.ok) throw new Error(data.error || 'Gagal update.');
                    location.reload();
                } catch (err) {
                    errBox.textContent = err.message;
                    errBox.classList.remove('d-none');
                }
            });
        })();
    </script>
</body>
</html>