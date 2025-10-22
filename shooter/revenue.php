<?php
declare(strict_types=1);
require_once __DIR__ . '/connection.php';

// Load revenue data
try {
    $pdo = db();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS players (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            phone VARCHAR(30) NOT NULL,
            score INT NOT NULL DEFAULT 0,
            attempts INT NOT NULL DEFAULT 0,
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
    
    // Get all players with attempts
    $players = $pdo->query("
        SELECT id, full_name, phone, score, attempts, created_at 
        FROM players 
        ORDER BY attempts DESC, created_at ASC
    ")->fetchAll() ?: [];
    
    // Calculate revenue statistics
    $pricePerPlay = 5000; // Rp 5.000 per play (excluding first free play)
    $totalRevenue = 0;
    $totalAttempts = 0;
    $totalPaidPlays = 0;
    $totalFreePlays = 0;
    $totalPlayers = count($players);
    
    foreach ($players as &$player) {
        $attempts = (int)$player['attempts'];
        $totalAttempts += $attempts;
        
        // First play is free, the rest are paid
        $paidPlays = max(0, $attempts - 1);
        $freePlays = min(1, $attempts);
        
        $revenue = $paidPlays * $pricePerPlay;
        
        $player['paid_plays'] = $paidPlays;
        $player['free_plays'] = $freePlays;
        $player['revenue'] = $revenue;
        
        $totalPaidPlays += $paidPlays;
        $totalFreePlays += $freePlays;
        $totalRevenue += $revenue;
    }
    
} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre>DB ERROR: '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'</pre>';
    exit;
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function formatRupiah(int $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Laporan Pendapatan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body { background:#0b1020; color:#e8f0ff; }
        .table thead th { color:#5de4c7; border-color:#334155; }
        .table tbody td { border-color:#334155; color:#e8f0ff; }
        .container-narrow { max-width: 1200px; }
        .stat-card { 
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .stat-value { 
            font-size: 2rem; 
            font-weight: 700; 
            color: #5de4c7;
            margin: 0.5rem 0;
        }
        .stat-label { 
            font-size: 0.875rem; 
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .revenue-highlight {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: white;
        }
        .badge-paid { background: #0ea5e9; }
        .badge-free { background: #10b981; }
    </style>
</head>
<body>
    <div class="container container-narrow py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h3 class="m-0">💰 Laporan Pendapatan</h3>
            <div class="d-flex gap-2">
                <a href="data.php" class="btn btn-secondary">Data Pemain</a>
                <a href="index.php" class="btn btn-secondary">Game</a>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-label">Total Pendapatan</div>
                    <div class="stat-value revenue-highlight p-3 rounded text-center">
                        <?= formatRupiah($totalRevenue) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-label">Total Pemain</div>
                    <div class="stat-value"><?= number_format($totalPlayers, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-label">Main Berbayar</div>
                    <div class="stat-value text-info"><?= number_format($totalPaidPlays, 0, ',', '.') ?>x</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-label">Main Gratis</div>
                    <div class="stat-value text-success"><?= number_format($totalFreePlays, 0, ',', '.') ?>x</div>
                </div>
            </div>
        </div>

        <!-- Additional Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Total Percobaan</div>
                    <div class="stat-value text-warning"><?= number_format($totalAttempts, 0, ',', '.') ?>x</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Rata-rata per Pemain</div>
                    <div class="stat-value">
                        <?= $totalPlayers > 0 ? formatRupiah((int)($totalRevenue / $totalPlayers)) : 'Rp 0' ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Harga per Main</div>
                    <div class="stat-value text-primary"><?= formatRupiah($pricePerPlay) ?></div>
                    <small class="text-muted">Main pertama gratis</small>
                </div>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="card bg-dark border-secondary">
            <div class="card-header bg-dark border-secondary">
                <h5 class="mb-0">Detail Pendapatan per Pemain</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Nama Pemain</th>
                                <th style="width:130px">Nomor WA</th>
                                <th style="width:100px" class="text-center">Skor</th>
                                <th style="width:120px" class="text-center">Total Main</th>
                                <th style="width:120px" class="text-center">Gratis</th>
                                <th style="width:120px" class="text-center">Berbayar</th>
                                <th style="width:150px" class="text-end">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$players): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data</td></tr>
                            <?php else: ?>
                            <?php foreach ($players as $idx => $p): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <strong><?= h($p['full_name']) ?></strong>
                                        <br><small class="text-muted"><?= h($p['phone']) ?></small>
                                    </td>
                                    <td>
                                        <a href="https://wa.me/<?= preg_replace('/\D/', '', $p['phone']) ?>" 
                                           target="_blank" 
                                           class="text-decoration-none">
                                            📱 Chat
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark"><?= number_format((int)$p['score'], 0, ',', '.') ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= (int)$p['attempts'] ?>x</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-free"><?= (int)$p['free_plays'] ?>x</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-paid"><?= (int)$p['paid_plays'] ?>x</span>
                                    </td>
                                    <td class="text-end">
                                        <strong class="<?= $p['revenue'] > 0 ? 'text-info' : 'text-muted' ?>">
                                            <?= formatRupiah((int)$p['revenue']) ?>
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <!-- Total Row -->
                            <tr class="table-primary">
                                <td colspan="4" class="text-end"><strong>TOTAL</strong></td>
                                <td class="text-center"><strong><?= number_format($totalAttempts, 0, ',', '.') ?>x</strong></td>
                                <td class="text-center"><strong><?= number_format($totalFreePlays, 0, ',', '.') ?>x</strong></td>
                                <td class="text-center"><strong><?= number_format($totalPaidPlays, 0, ',', '.') ?>x</strong></td>
                                <td class="text-end"><strong class="text-info fs-5"><?= formatRupiah($totalRevenue) ?></strong></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="alert alert-info mt-4" role="alert">
            <h6 class="alert-heading">📊 Informasi Perhitungan</h6>
            <ul class="mb-0">
                <li>Setiap pemain mendapat <strong>1x main gratis</strong></li>
                <li>Main kedua dan seterusnya dikenakan biaya <strong><?= formatRupiah($pricePerPlay) ?></strong></li>
                <li>Rumus: <code>Pendapatan = (Total Main - 1) × Rp 5.000</code></li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>