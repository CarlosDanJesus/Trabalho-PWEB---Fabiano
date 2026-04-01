<?php
/**
 * db.php
 * Conexão com o banco de dados usando PDO.
 * Retorna uma instância singleton do PDO.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Configuração — ajuste conforme o seu ambiente XAMPP
// ---------------------------------------------------------------------------
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
