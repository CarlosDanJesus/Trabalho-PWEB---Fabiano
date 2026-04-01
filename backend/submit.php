<?php
/**
 * submit.php
 * Endpoint principal para receber o formulário de cadastro.
 *
 * Aceita: POST application/json
 * Retorna: application/json
 *
 * Proteções implementadas:
 *  - Apenas método POST permitido
 *  - Corpo JSON (sem $_POST — evita injeção multipart)
 *  - Validação completa server-side via validate.php
 *  - Prepared statements PDO — prevenção de SQL Injection
 *  - Hash bcrypt da senha (password_hash / PASSWORD_BCRYPT)
 *  - htmlspecialchars na saída — prevenção de XSS
 *  - IP e User-Agent armazenados para auditoria
 *  - Log de auditoria gravado independente do resultado
 *  - Erros reais logados server-side, mensagens genéricas ao cliente
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validate.php';

// ---------------------------------------------------------------------------
// Cabeçalhos
// ---------------------------------------------------------------------------
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// ---------------------------------------------------------------------------
// Validação do método HTTP
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'sucesso' => false,
        'erros'   => ['servidor' => 'Método não permitido. Use POST.'],
    ]);
    exit;
}

// ---------------------------------------------------------------------------
// Leitura e decodificação do corpo JSON
// ---------------------------------------------------------------------------
$bruto = file_get_contents('php://input');
$corpo = json_decode($bruto, true);

if (!is_array($corpo)) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'erros'   => ['servidor' => 'Corpo da requisição inválido. Esperado JSON.'],
    ]);
    exit;
}

// ---------------------------------------------------------------------------
// Sanitização básica (remove null bytes e espaços extras)
// ---------------------------------------------------------------------------
function sanitizar_string(mixed $v): string
{
    if (!is_string($v)) return '';
    return trim(str_replace("\0", '', $v));
}

$entrada = [
    'nome'     => sanitizar_string($corpo['nome']     ?? ''),
    'email'    => sanitizar_string($corpo['email']    ?? ''),
    'senha'    => isset($corpo['senha']) && is_string($corpo['senha'])
                    ? $corpo['senha']   // NÃO aplicar trim em senhas
                    : '',
    'mensagem' => sanitizar_string($corpo['mensagem'] ?? ''),
];

// ---------------------------------------------------------------------------
// Validação
// ---------------------------------------------------------------------------
$erros = validar_cadastro($entrada);

if (tem_erros($erros)) {
    $errosCliente = [];
    foreach ($erros as $campo => $msgs) {
        if (!empty($msgs)) {
            $errosCliente[$campo] = $msgs[0];
        }
    }
    http_response_code(422);
    echo json_encode(['sucesso' => false, 'erros' => $errosCliente]);
    exit;
}

// ---------------------------------------------------------------------------
// Operações no banco de dados
// ---------------------------------------------------------------------------
$pdo       = get_db();
$ip        = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
$userAgent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);

// Verifica e-mail duplicado
try {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $entrada['email']]);

    if ($stmt->fetch()) {
        gravar_log($pdo, null, $ip, 'failure', 'E-mail duplicado');
        http_response_code(409);
        echo json_encode([
            'sucesso' => false,
            'erros'   => ['email' => 'Este endereço de e-mail já está cadastrado.'],
        ]);
        exit;
    }
} catch (PDOException $e) {
    error_log('[submit] Verificação de duplicata falhou: ' . $e->getMessage());
    enviar_erro_servidor();
}

// Hash da senha com bcrypt (custo 12)
$hashSenha = password_hash($entrada['senha'], PASSWORD_BCRYPT, ['cost' => 12]);

// Inserção do usuário
try {
    $insert = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, message, ip_address, user_agent)
         VALUES (:nome, :email, :hash_senha, :mensagem, :ip, :ua)'
    );
    $insert->execute([
        ':nome'       => $entrada['nome'],
        ':email'      => $entrada['email'],
        ':hash_senha' => $hashSenha,
        ':mensagem'   => $entrada['mensagem'],
        ':ip'         => $ip,
        ':ua'         => $userAgent,
    ]);

    $novoId = (int) $pdo->lastInsertId();
} catch (PDOException $e) {
    error_log('[submit] Inserção falhou: ' . $e->getMessage());
    gravar_log($pdo, null, $ip, 'failure', 'Erro na inserção no banco');
    enviar_erro_servidor();
}

// Grava log de auditoria (sucesso)
gravar_log($pdo, $novoId, $ip, 'success', null);

// ---------------------------------------------------------------------------
// Resposta de sucesso
// ---------------------------------------------------------------------------
http_response_code(201);
echo json_encode([
    'sucesso'  => true,
    'mensagem' => 'Cadastro realizado com sucesso!',
]);
exit;

// ===========================================================================
// Funções auxiliares
// ===========================================================================

/**
 * Grava uma entrada na tabela submission_logs.
 */
function gravar_log(
    PDO     $pdo,
    ?int    $usuarioId,
    string  $ip,
    string  $status,
    ?string $motivo
): void {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO submission_logs (user_id, ip_address, status, failure_reason)
             VALUES (:uid, :ip, :status, :motivo)'
        );
        $stmt->execute([
            ':uid'    => $usuarioId,
            ':ip'     => $ip,
            ':status' => $status,
            ':motivo' => $motivo,
        ]);
    } catch (PDOException $e) {
        error_log('[submit] Falha ao gravar log: ' . $e->getMessage());
    }
}

/**
 * Envia resposta de erro 500 e encerra a execução.
 */
function enviar_erro_servidor(): never
{
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erros'   => ['servidor' => 'Ocorreu um erro interno. Tente novamente mais tarde.'],
    ]);
    exit;
}
