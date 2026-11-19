<?php

declare(strict_types=1);

const DIAS = [
    1 => 'Segunda',
    2 => 'Terça',
    3 => 'Quarta',
    4 => 'Quinta',
    5 => 'Sexta',
    6 => 'Sábado',
    7 => 'Domingo',
];

const NIVEIS = [
    'iniciante' => 'Iniciante',
    'intermediario' => 'Intermediário',
    'avancado' => 'Avançado',
];

const PARENTESCOS = [
    'mae' => 'Mãe',
    'pai' => 'Pai',
    'outro' => 'Outro',
];

final class ValidacaoException extends RuntimeException
{
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = require dirname(__DIR__) . '/config/database.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $c['host'],
        $c['port'],
        $c['name'],
        $c['charset']
    );
    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '-03:00'");
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        exit('Não foi possível conectar ao banco volei. Importe database/schema.sql no MariaDB.');
    }
    return $pdo;
}

function q(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function q1(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

function exec_sql(string $sql, array $params = []): void
{
    $st = db()->prepare($sql);
    $st->execute($params);
}

function transacao(callable $fn): mixed
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $resultado = $fn($pdo);
        $pdo->commit();
        return $resultado;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function base_path(): string
{
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = rtrim($base, '/');
    if ($base === '/' || $base === '.' || $base === '') {
        return '';
    }
    return $base;
}

function novo_convite(): string
{
    return bin2hex(random_bytes(16));
}

function url_absoluta(string $path = '/'): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
    return ($https ? 'https' : 'http') . '://' . $host . url($path);
}

function caminho_convite(mixed $caminho): ?string
{
    if (!is_string($caminho) || !preg_match('#^/convite/[a-f0-9]{32}$#', $caminho)) {
        return null;
    }
    return $caminho;
}

function url(string $path = '/'): string
{
    $path = '/' . ltrim($path, '/');
    $full = base_path() . ($path === '/' ? '/' : $path);
    $partes = explode('/', $full);
    $partes = array_map(static fn (string $parte): string => rawurlencode($parte), $partes);
    $montada = implode('/', $partes);
    return $montada === '' ? '/' : $montada;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $uri = rawurldecode(is_string($uri) ? $uri : '/');
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = '/' . trim($uri, '/');
    if ($uri === '/index.php') {
        return '/';
    }
    return $uri;
}

function e(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function campo(string $nome): string
{
    $valor = $_POST[$nome] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

function campo_raw(string $nome): string
{
    $valor = $_POST[$nome] ?? '';
    return is_string($valor) ? $valor : '';
}

function campo_int(string $nome): int
{
    return (int) campo($nome);
}

function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = ['tipo' => $tipo === 'ok' ? 'ok' : 'erro', 'msg' => $mensagem];
}

function pull_flash(): ?array
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $enviado = $_POST['_csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(419);
        exit('Sessão expirada. Volte e envie o formulário de novo.');
    }
}

function render(string $view, array $data = [], bool $publico = false): void
{
    $data['publico'] = $publico;
    $data['usuario'] ??= Auth::usuario();
    $data['flash'] ??= pull_flash();
    $data['titulo'] ??= 'Escola de Vôlei';
    extract($data, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . '/views/' . $view . '.php';
    $conteudo = ob_get_clean();
    require dirname(__DIR__) . '/views/layout.php';
}

function parte(string $nome, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/views/partes/' . $nome . '.php';
}

function require_login(): array
{
    $usuario = Auth::usuario();
    if ($usuario === null) {
        redirect('/login');
    }
    return $usuario;
}

function require_papel(string ...$papeis): array
{
    $usuario = require_login();
    if (!in_array($usuario['papel'], $papeis, true)) {
        recusar('Você não pode abrir esta página.');
    }
    return $usuario;
}

function recusar(string $mensagem): never
{
    http_response_code(403);
    render('erro', [
        'titulo' => 'Acesso negado',
        'mensagem' => $mensagem,
    ]);
    exit;
}

function nao_encontrado(string $mensagem = 'Esse registro não existe.'): never
{
    http_response_code(404);
    render('erro', [
        'titulo' => 'Não encontrado',
        'mensagem' => $mensagem,
    ]);
    exit;
}

function old(array $old, string $chave, string $padrao = ''): string
{
    if (!array_key_exists($chave, $old)) {
        return $padrao;
    }
    $valor = $old[$chave];
    return is_string($valor) ? $valor : $padrao;
}

function nav_ativo(string $href): bool
{
    $atual = request_path();
    if ($href === '/') {
        return $atual === '/';
    }
    return $atual === $href || str_starts_with($atual, $href . '/');
}

function papel_label(string $papel): string
{
    return match ($papel) {
        'equipe' => 'Equipe',
        'responsavel' => 'Responsável',
        'professor' => 'Professor',
        'aluno' => 'Aluno',
        default => $papel,
    };
}

function hoje(): string
{
    return (new DateTimeImmutable('today'))->format('Y-m-d');
}

function data_valida(string $data): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    return $d instanceof DateTimeImmutable && $d->format('Y-m-d') === $data;
}

function hora_valida(string $hora): bool
{
    return (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $hora);
}

function hora_curta(string $hora): string
{
    return substr($hora, 0, 5);
}

function minutos(string $hora): int
{
    [$h, $m] = array_map('intval', explode(':', hora_curta($hora)));
    return $h * 60 + $m;
}

function formatar_data(?string $data): string
{
    if ($data === null || $data === '') {
        return '';
    }
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', substr($data, 0, 10));
    return $d instanceof DateTimeImmutable ? $d->format('d/m/Y') : $data;
}

function formatar_hora(?string $hora): string
{
    if ($hora === null || $hora === '') {
        return '';
    }
    return substr($hora, 0, 5);
}

function formatar_cpf(?string $cpf): string
{
    $cpf = preg_replace('/\D+/', '', (string) $cpf) ?? '';
    if (strlen($cpf) !== 11) {
        return '';
    }
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

function formatar_telefone(?string $telefone): string
{
    $digitos = preg_replace('/\D+/', '', (string) $telefone) ?? '';
    if (strlen($digitos) === 11) {
        return sprintf('(%s) %s-%s', substr($digitos, 0, 2), substr($digitos, 2, 5), substr($digitos, 7, 4));
    }
    if (strlen($digitos) === 10) {
        return sprintf('(%s) %s-%s', substr($digitos, 0, 2), substr($digitos, 2, 4), substr($digitos, 6, 4));
    }
    return (string) $telefone;
}

function formatar_grade(?string $grade): array
{
    if ($grade === null || $grade === '') {
        return [];
    }
    $linhas = [];
    foreach (explode(',', $grade) as $item) {
        $partes = explode('#', $item);
        if (count($partes) !== 3) {
            continue;
        }
        $dia = DIAS[(int) $partes[0]] ?? $partes[0];
        $linhas[] = $dia . ' ' . $partes[1] . '–' . $partes[2];
    }
    return $linhas;
}

function idade(string $dataNascimento): int
{
    $dataNascimento = substr($dataNascimento, 0, 10);
    $nasc = DateTimeImmutable::createFromFormat('!Y-m-d', $dataNascimento);
    if (!$nasc instanceof DateTimeImmutable) {
        throw new ValidacaoException('Informe a data de nascimento.');
    }
    return $nasc->diff(new DateTimeImmutable('today'))->y;
}

function eh_menor(string $dataNascimento): bool
{
    return idade($dataNascimento) < 18;
}

function nome_valido(string $nome): string
{
    $nome = trim($nome);
    $tamanho = mb_strlen($nome);
    if ($tamanho < 2 || $tamanho > 120) {
        throw new ValidacaoException('Informe o nome.');
    }
    return $nome;
}

function email_valido(string $email): string
{
    $email = mb_strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) {
        throw new ValidacaoException('Informe um e-mail válido.');
    }
    return $email;
}

function email_em_uso(string $email, ?int $ignorarUsuarioId = null): bool
{
    $sql = 'SELECT id FROM usuarios WHERE email = ?';
    $params = [$email];
    if ($ignorarUsuarioId !== null) {
        $sql .= ' AND id <> ?';
        $params[] = $ignorarUsuarioId;
    }
    return q1($sql, $params) !== null;
}

function cpf_valido(string $cpf): bool
{
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int) $cpf[$t] !== $digito) {
            return false;
        }
    }
    return true;
}

function cpf_ou_nulo(string $cpf): ?string
{
    $cpf = trim($cpf);
    if ($cpf === '') {
        return null;
    }
    $digitos = preg_replace('/\D+/', '', $cpf) ?? '';
    if (!cpf_valido($digitos)) {
        throw new ValidacaoException('Informe um CPF válido ou deixe o campo em branco.');
    }
    return $digitos;
}

function cpf_em_uso(string $tabela, string $cpf, ?int $ignorarId = null): bool
{
    if (!in_array($tabela, ['alunos', 'professores', 'responsaveis'], true)) {
        throw new InvalidArgumentException('Tabela inválida.');
    }
    $sql = "SELECT id FROM {$tabela} WHERE cpf = ?";
    $params = [$cpf];
    if ($ignorarId !== null) {
        $sql .= ' AND id <> ?';
        $params[] = $ignorarId;
    }
    return q1($sql, $params) !== null;
}

function telefone_ou_nulo(string $telefone): ?string
{
    $telefone = trim($telefone);
    if ($telefone === '') {
        return null;
    }
    $digitos = preg_replace('/\D+/', '', $telefone) ?? '';
    if (strlen($digitos) < 10 || strlen($digitos) > 11) {
        throw new ValidacaoException('Informe um telefone com DDD ou deixe o campo em branco.');
    }
    return $digitos;
}

function texto_ou_nulo(string $texto, int $max, string $mensagem): ?string
{
    $texto = trim($texto);
    if ($texto === '') {
        return null;
    }
    if (mb_strlen($texto) > $max) {
        throw new ValidacaoException($mensagem);
    }
    return $texto;
}

function validar_senha_campos(string $campoSenha, string $campoConfirmacao): string
{
    $senha = campo_raw($campoSenha);
    $confirmacao = campo_raw($campoConfirmacao);
    if (strlen($senha) < 6) {
        throw new ValidacaoException('A senha precisa ter pelo menos 6 caracteres.');
    }
    if ($senha !== $confirmacao) {
        throw new ValidacaoException('A confirmação da senha não confere.');
    }
    return password_hash($senha, PASSWORD_DEFAULT);
}

function validar_nova_senha(bool $obrigatoria): ?string
{
    $senha = campo_raw('senha');
    $confirmacao = campo_raw('senha_confirmacao');
    if ($senha === '' && $confirmacao === '') {
        if ($obrigatoria) {
            throw new ValidacaoException('Informe uma senha.');
        }
        return null;
    }
    if (strlen($senha) < 6) {
        throw new ValidacaoException('A senha precisa ter pelo menos 6 caracteres.');
    }
    if ($senha !== $confirmacao) {
        throw new ValidacaoException('A confirmação da senha não confere.');
    }
    return password_hash($senha, PASSWORD_DEFAULT);
}

function destino_interno(string $padrao = '/'): string
{
    $voltar = campo('voltar');
    if ($voltar === '' || $voltar[0] !== '/' || str_starts_with($voltar, '//') || str_contains($voltar, "\n") || str_contains($voltar, "\r")) {
        return $padrao;
    }
    return $voltar;
}

function grade_sql(string $aliasTurma = 't.id'): string
{
    return "(SELECT GROUP_CONCAT(CONCAT(h.dia_semana, '#', TIME_FORMAT(h.hora_inicio, '%H:%i'), '#', TIME_FORMAT(h.hora_fim, '%H:%i')) ORDER BY h.dia_semana, h.hora_inicio SEPARATOR ',') FROM horarios h WHERE h.turma_id = {$aliasTurma})";
}
