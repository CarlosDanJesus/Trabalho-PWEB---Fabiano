<?php
/**
 * listar.php
 * Retorna todos os usuários cadastrados sem expor a senha.
 *
 * Aceita: GET
 * Retorna: application/json
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'sucesso' => false,
        'erros'   => ['servidor' => 'Método não permitido. Use GET.'],
    ]);
    exit;
}

try {
    $pdo  = get_db();
    $stmt = $pdo->query(
        'SELECT id, name, email, message, ip_address, created_at
         FROM users
         ORDER BY created_at DESC'
    );
    $usuarios = $stmt->fetchAll();

    // Sanitiza saída para evitar XSS
    $usuarios = array_map(function (array $u): array {
        return [
            'id'          => (int) $u['id'],
            'nome'        => htmlspecialchars($u['name'],    ENT_QUOTES, 'UTF-8'),
            'email'       => htmlspecialchars($u['email'],   ENT_QUOTES, 'UTF-8'),
            'mensagem'    => htmlspecialchars($u['message'], ENT_QUOTES, 'UTF-8'),
            'ip'          => htmlspecialchars($u['ip_address'] ?? '', ENT_QUOTES, 'UTF-8'),
            'cadastrado_em' => $u['created_at'],
        ];
    }, $usuarios);

    http_response_code(200);
    echo json_encode(['sucesso' => true, 'usuarios' => $usuarios]);

} catch (PDOException $e) {
    error_log('[listar] Erro ao buscar usuários: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erros'   => ['servidor' => 'Erro interno ao buscar registros.'],
    ]);
}
