<?php


declare(strict_types=1);


define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NOME',    'formsecure');
define('DB_USUARIO', 'root');    // altere em produção
define('DB_SENHA',   '20080916');        // altere em produção
define('DB_CHARSET', 'utf8mb4');
// ---------------------------------------------------------------------------

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NOME,
        DB_CHARSET
    );

    $opcoes = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_FOUND_ROWS   => true,
    ];

    try {
        $pdo = new PDO($dsn, DB_USUARIO, DB_SENHA, $opcoes);
    } catch (PDOException $e) {
        error_log('[DB] Falha na conexão: ' . $e->getMessage());
        http_response_code(503);
        echo json_encode([
            'sucesso' => false,
            'erros'   => ['servidor' => 'Banco de dados indisponível. Tente novamente mais tarde.'],
        ]);
        exit;
    }

    return $pdo;
}

// ---------------------------------------------------------------------------
// Deletar usuário por ID
// ---------------------------------------------------------------------------
function deletar_usuario(int $id): bool
{
    $stmt = get_db()->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->rowCount() > 0;
}

// ---------------------------------------------------------------------------
// Atualizar usuário por ID (nome, email, mensagem)
// ---------------------------------------------------------------------------
function atualizar_usuario(int $id, string $nome, string $email, string $mensagem): bool
{
    $stmt = get_db()->prepare(
        'UPDATE users
            SET name = :nome, email = :email, message = :mensagem
          WHERE id = :id'
    );
    $stmt->execute([
        ':nome'     => $nome,
        ':email'    => $email,
        ':mensagem' => $mensagem,
        ':id'       => $id,
    ]);
    return $stmt->rowCount() > 0;
}
