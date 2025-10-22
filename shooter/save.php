<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/connection.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

 $action = $_POST['action'] ?? '';
// ====== UPDATE PLAYER MODE ======
if ($action === 'update') {
    try {
        $pdo = db();
        // ensure table exists
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

        $id        = (int)($_POST['id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $phone_raw = trim($_POST['phone'] ?? '');
        $score_val = (int)($_POST['score'] ?? 0);
        $attempts_val = (int)($_POST['attempts'] ?? 0);
        if ($attempts_val < 0) $attempts_val = 0;
        if ($attempts_val > 100000000) $attempts_val = 100000000;

        if ($id <= 0) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'ID tidak valid']); exit; }
        if (strlen($full_name) < 3) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'Nama minimal 3 karakter']); exit; }
        if ($score_val < 0) $score_val = 0;
        if ($score_val > 100000000) $score_val = 100000000;

        // Normalize phone
        $phone = preg_replace('/[\s\-\(\)\.]/', '', $phone_raw);
        if (str_starts_with($phone, '+62')) $phone = '0' . substr($phone, 3);
        if (str_starts_with($phone, '62'))  $phone = '0' . substr($phone, 2);
        if (!preg_match('/^0\d{8,14}$/', $phone)) {
            http_response_code(422); echo json_encode(['ok'=>false,'error'=>'Nomor WhatsApp tidak valid']); exit;
        }

        // Check duplicate phone on other id
        $chk = $pdo->prepare('SELECT id FROM players WHERE phone = ? LIMIT 1');
        $chk->execute([$phone]);
        $dup = $chk->fetch();
        if ($dup && (int)$dup['id'] !== $id) {
            http_response_code(409);
            echo json_encode(['ok'=>false,'error'=>'Nomor WhatsApp sudah digunakan pemain lain']);
            exit;
        }

        $upd = $pdo->prepare('UPDATE players SET full_name = ?, phone = ?, score = ?, attempts = ? WHERE id = ?');
        $upd->execute([$full_name, $phone, $score_val, $attempts_val, $id]);

        if ($upd->rowCount() === 0) {
            // No change or not found
            // Verify existence
            $exists = $pdo->prepare('SELECT id FROM players WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetch()) {
                http_response_code(404);
                echo json_encode(['ok'=>false,'error'=>'Player tidak ditemukan']);
                exit;
            }
        }
        echo json_encode(['ok'=>true, 'updated'=>true]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}
// ====== LEADERBOARD (LIST) MODE ======
if ($action === 'leaders') {
    try {
        $pdo = db();
        // ensure table exists (in case no registration yet)
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
        $stmt = $pdo->query("SELECT full_name, phone, score, attempts FROM players ORDER BY score DESC, created_at ASC LIMIT 15");
        $players = $stmt->fetchAll() ?: [];
        echo json_encode(['ok' => true, 'players' => $players]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ====== SCORE UPDATE MODE ======
if ($action === 'score' || isset($_POST['score'])) {
    $phone_raw = trim($_POST['phone'] ?? '');
    $score_val = (int)($_POST['score'] ?? 0);
    if ($score_val < 0) $score_val = 0;
    if ($score_val > 100000000) $score_val = 100000000;

    // Normalize phone
    $phone = preg_replace('/[\s\-\(\)\.]/', '', $phone_raw);
    if (str_starts_with($phone, '+62')) $phone = '0' . substr($phone, 3);
    if (str_starts_with($phone, '62'))  $phone = '0' . substr($phone, 2);

    if (!preg_match('/^0\d{8,14}$/', $phone)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Nomor WhatsApp tidak valid.']);
        exit;
    }

    try {
        $pdo = db();
        // $upd = $pdo->prepare('UPDATE players SET score = ?, attempts = attempts + 1 WHERE phone = ?');
        $upd = $pdo->prepare('UPDATE players SET score = GREATEST(score, ?) WHERE phone = ?'); // only update if new score is higher
        $upd->execute([$score_val, $phone]);
        if ($upd->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Player belum terdaftar.']);
            exit;
        }
        echo json_encode(['ok' => true, 'updated' => true, 'score' => $score_val]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ====== REGISTRATION MODE ======
$full_name = trim($_POST['full_name'] ?? '');
$phone_raw = trim($_POST['phone'] ?? '');

if (strlen($full_name) < 3) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Nama minimal 3 karakter.']);
    exit;
}

// Normalize phone to local format starting with 0, keep digits only (plus sign allowed initially)
$phone = preg_replace('/[\s\-\(\)\.]/', '', $phone_raw);
if (str_starts_with($phone, '+62')) $phone = '0' . substr($phone, 3);
if (str_starts_with($phone, '62'))  $phone = '0' . substr($phone, 2);

if (!preg_match('/^0\d{8,14}$/', $phone)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Nomor WhatsApp tidak valid.']);
    exit;
}

try {
    $pdo = db();
    // ensure table exists
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

    // check existing by phone
    $sel = $pdo->prepare('SELECT id FROM players WHERE phone = ? LIMIT 1');
    $sel->execute([$phone]);
    $row = $sel->fetch();
    if ($row) {
        echo json_encode(['ok' => true, 'id' => (int)$row['id'], 'existing' => true]);
        exit;
    }

    // insert new
    $ins = $pdo->prepare('INSERT INTO players (full_name, phone, score, attempts) VALUES (?, ?, 0, 0)');
    $ins->execute([$full_name, $phone]);
    $id = (int)$pdo->lastInsertId();

    echo json_encode(['ok' => true, 'id' => $id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}