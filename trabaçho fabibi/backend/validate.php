<?php
/**
 * validate.php
 * Funções de validação puras — sem efeitos colaterais, sem acesso ao banco.
 * Cada função retorna um array de mensagens de erro (vazio = válido).
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Constantes
// ---------------------------------------------------------------------------
const NOME_MIN     = 2;
const NOME_MAX     = 100;
const SENHA_MIN    = 8;
const SENHA_MAX    = 72;   // limite do bcrypt
const MENSAGEM_MAX = 250;

// ---------------------------------------------------------------------------
// Validadores individuais
// ---------------------------------------------------------------------------

/**
 * Valida o campo "nome".
 *
 * @param  mixed $valor Entrada bruta
 * @return string[]     Lista de mensagens de erro
 */
function validar_nome(mixed $valor): array
{
    $erros = [];

    if (!is_string($valor) || trim($valor) === '') {
        $erros[] = 'O nome é obrigatório.';
        return $erros;
    }

    $nome = trim($valor);

    if (mb_strlen($nome) < NOME_MIN) {
        $erros[] = sprintf('O nome deve ter pelo menos %d caracteres.', NOME_MIN);
    }

    if (mb_strlen($nome) > NOME_MAX) {
        $erros[] = sprintf('O nome não pode ultrapassar %d caracteres.', NOME_MAX);
    }

    if (!preg_match('/^[\p{L}\s\'\-]+$/u', $nome)) {
        $erros[] = 'O nome só pode conter letras, espaços, hífens e apóstrofos.';
    }

    return $erros;
}

/**
 * Valida o campo "email".
 *
 * @param  mixed $valor Entrada bruta
 * @return string[]     Lista de mensagens de erro
 */
function validar_email(mixed $valor): array
{
    $erros = [];

    if (!is_string($valor) || trim($valor) === '') {
        $erros[] = 'O e-mail é obrigatório.';
        return $erros;
    }

    $email = trim($valor);

    if (mb_strlen($email) > 254) {
        $erros[] = 'O endereço de e-mail é longo demais.';
        return $erros;
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $erros[] = 'Informe um endereço de e-mail válido (ex: usuario@exemplo.com).';
    }

    return $erros;
}

/**
 * Valida o campo "senha".
 *
 * Regras:
 *  - 8 a 72 caracteres
 *  - Pelo menos uma letra maiúscula
 *  - Pelo menos uma letra minúscula
 *  - Pelo menos um número
 *  - Pelo menos um caractere especial
 *
 * @param  mixed $valor Entrada bruta
 * @return string[]     Lista de mensagens de erro
 */
function validar_senha(mixed $valor): array
{
    $erros = [];

    if (!is_string($valor) || $valor === '') {
        $erros[] = 'A senha é obrigatória.';
        return $erros;
    }

    if (mb_strlen($valor) < SENHA_MIN) {
        $erros[] = sprintf('A senha deve ter pelo menos %d caracteres.', SENHA_MIN);
    }

    if (mb_strlen($valor) > SENHA_MAX) {
        $erros[] = sprintf('A senha não pode ultrapassar %d caracteres.', SENHA_MAX);
    }

    if (!preg_match('/[A-Z]/', $valor)) {
        $erros[] = 'A senha deve conter pelo menos uma letra maiúscula.';
    }

    if (!preg_match('/[a-z]/', $valor)) {
        $erros[] = 'A senha deve conter pelo menos uma letra minúscula.';
    }

    if (!preg_match('/[0-9]/', $valor)) {
        $erros[] = 'A senha deve conter pelo menos um número.';
    }

    if (!preg_match('/[\W_]/', $valor)) {
        $erros[] = 'A senha deve conter pelo menos um caractere especial (!@#$%^&*…).';
    }

    return $erros;
}

/**
 * Valida o campo "mensagem".
 *
 * @param  mixed $valor Entrada bruta
 * @return string[]     Lista de mensagens de erro
 */
function validar_mensagem(mixed $valor): array
{
    $erros = [];

    if (!is_string($valor) || trim($valor) === '') {
        $erros[] = 'A mensagem é obrigatória.';
        return $erros;
    }

    if (mb_strlen(trim($valor)) > MENSAGEM_MAX) {
        $erros[] = sprintf('A mensagem não pode ultrapassar %d caracteres.', MENSAGEM_MAX);
    }

    return $erros;
}

// ---------------------------------------------------------------------------
// Validador agregado
// ---------------------------------------------------------------------------

/**
 * Valida todos os campos do cadastro de uma vez.
 *
 * @param  array<string,mixed> $dados Mapa de entradas brutas
 * @return array<string,string[]>     Mapa campo => erros (vazio = válido)
 */
function validar_cadastro(array $dados): array
{
    return [
        'nome'     => validar_nome($dados['nome']         ?? null),
        'email'    => validar_email($dados['email']       ?? null),
        'senha'    => validar_senha($dados['senha']       ?? null),
        'mensagem' => validar_mensagem($dados['mensagem'] ?? null),
    ];
}

/**
 * Verifica se o mapa de validação contém algum erro.
 *
 * @param  array<string,string[]> $erros
 * @return bool
 */
function tem_erros(array $erros): bool
{
    foreach ($erros as $campoErros) {
        if (!empty($campoErros)) {
            return true;
        }
    }
    return false;
}
