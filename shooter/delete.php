<?php
declare(strict_types=1);
require_once __DIR__ . '/connection.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('ID tidak valid');
    }

    $pdo = db();
    $stmt = $pdo->prepare("DELETE FROM players WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Data tidak ditemukan');
    }

    echo json_encode(['ok' => true, 'message' => 'Data berhasil dihapus']);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}