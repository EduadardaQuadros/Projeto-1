<?php

declare(strict_types=1);

final class PagadorIncompletoException extends RuntimeException
{
    public function __construct(public readonly array $ate)
    {
        parent::__construct('Informe o CPF de quem paga.');
    }
}

function asaas_config(): array
{
    $config = require dirname(__DIR__) . '/config/asaas.php';
    if (!is_array($config) || !is_string($config['api_key'] ?? null) || !str_starts_with($config['api_key'], '$aact_')) {
        throw new ValidacaoException('A chave do Asaas não está configurada.');
    }
    return $config;
}

function asaas_request(string $method, string $path, ?array $body = null): array
{
    $config = asaas_config();
    $url = rtrim((string) $config['base_url'], '/') . '/' . ltrim($path, '/');
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: EscolaVolei',
        'access_token: ' . $config['api_key'],
    ];
    $http = [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'timeout' => 60,
        'ignore_errors' => true,
    ];
    if ($body !== null) {
        $http['content'] = json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    $bruto = file_get_contents($url, false, stream_context_create(['http' => $http]));
    if ($bruto === false) {
        throw new ValidacaoException('Não foi possível falar com o Asaas.');
    }
    $status = 0;
    foreach ($http_response_header ?? [] as $linha) {
        if (preg_match('#HTTP/\S+\s+(\d{3})#', $linha, $encontrado)) {
            $status = (int) $encontrado[1];
        }
    }
    $json = json_decode($bruto, true);
    if ($status >= 400) {
        $mensagem = 'O Asaas recusou esta cobrança.';
        if (is_array($json) && !empty($json['errors'][0]['description'])) {
            $mensagem = (string) $json['errors'][0]['description'];
        }
        throw new ValidacaoException($mensagem);
    }
    return is_array($json) ? $json : [];
}

function dados_pagador(int $usuarioId): array
{
    $usuario = q1('SELECT nome, email FROM usuarios WHERE id = ?', [$usuarioId]);
    if ($usuario === null) {
        throw new ValidacaoException('Login não encontrado.');
    }
    $responsavel = q1('SELECT id, cpf, telefone FROM responsaveis WHERE usuario_id = ?', [$usuarioId]);
    $aluno = q1('SELECT id, cpf, telefone FROM alunos WHERE usuario_id = ?', [$usuarioId]);
    $cpf = $responsavel['cpf'] ?? $aluno['cpf'] ?? null;
    $telefone = $responsavel['telefone'] ?? $aluno['telefone'] ?? null;
    return [
        'nome' => (string) $usuario['nome'],
        'email' => (string) $usuario['email'],
        'cpf' => is_string($cpf) && $cpf !== '' ? $cpf : null,
        'telefone' => is_string($telefone) && $telefone !== '' ? $telefone : null,
        'responsavel_id' => $responsavel === null ? null : (int) $responsavel['id'],
        'aluno_id' => $aluno === null ? null : (int) $aluno['id'],
    ];
}

function guardar_cpf_pagador(int $usuarioId, string $cpf): void
{
    $digitos = cpf_ou_nulo($cpf);
    if ($digitos === null) {
        throw new ValidacaoException('Informe o CPF de quem paga.');
    }
    $pagador = dados_pagador($usuarioId);
    if ($pagador['responsavel_id'] !== null) {
        if (cpf_em_uso('responsaveis', $digitos, $pagador['responsavel_id'])) {
            throw new ValidacaoException('Este CPF já está em outro cadastro.');
        }
        exec_sql('UPDATE responsaveis SET cpf = ? WHERE id = ?', [$digitos, $pagador['responsavel_id']]);
        return;
    }
    if ($pagador['aluno_id'] !== null) {
        if (cpf_em_uso('alunos', $digitos, $pagador['aluno_id'])) {
            throw new ValidacaoException('Este CPF já está em outro cadastro.');
        }
        exec_sql('UPDATE alunos SET cpf = ? WHERE id = ?', [$digitos, $pagador['aluno_id']]);
        return;
    }
    throw new ValidacaoException('Não há cadastro para guardar o CPF.');
}

function guardar_telefone_pagador(int $usuarioId, string $telefone): void
{
    $digitos = telefone_ou_nulo($telefone);
    if ($digitos === null) {
        throw new ValidacaoException('Informe o celular de quem paga.');
    }
    $pagador = dados_pagador($usuarioId);
    if ($pagador['responsavel_id'] !== null) {
        exec_sql('UPDATE responsaveis SET telefone = ? WHERE id = ?', [$digitos, $pagador['responsavel_id']]);
        return;
    }
    if ($pagador['aluno_id'] !== null) {
        exec_sql('UPDATE alunos SET telefone = ? WHERE id = ?', [$digitos, $pagador['aluno_id']]);
        return;
    }
    throw new ValidacaoException('Não há cadastro para guardar o celular.');
}

function atualizar_cliente_asaas(string $customerId, array $pagador): void
{
    if ($pagador['cpf'] === null || $pagador['telefone'] === null) {
        return;
    }
    asaas_request('PUT', '/customers/' . rawurlencode($customerId), [
        'name' => $pagador['nome'],
        'cpfCnpj' => $pagador['cpf'],
        'email' => $pagador['email'],
        'phone' => $pagador['telefone'],
        'mobilePhone' => $pagador['telefone'],
        'notificationDisabled' => true,
    ]);
}

function garantir_cliente_asaas(int $usuarioId): string
{
    $existente = q1('SELECT customer_id FROM asaas_clientes WHERE usuario_id = ?', [$usuarioId]);
    if ($existente !== null) {
        return (string) $existente['customer_id'];
    }
    $pagador = dados_pagador($usuarioId);
    if ($pagador['cpf'] === null) {
        throw new PagadorIncompletoException([]);
    }
    $lista = asaas_request('GET', '/customers?externalReference=' . rawurlencode((string) $usuarioId) . '&limit=1');
    $customerId = $lista['data'][0]['id'] ?? null;
    if (!is_string($customerId) || $customerId === '') {
        $corpo = [
            'name' => $pagador['nome'],
            'cpfCnpj' => $pagador['cpf'],
            'email' => $pagador['email'],
            'externalReference' => (string) $usuarioId,
            'notificationDisabled' => true,
        ];
        if ($pagador['telefone'] !== null) {
            $corpo['phone'] = $pagador['telefone'];
            $corpo['mobilePhone'] = $pagador['telefone'];
        }
        $criado = asaas_request('POST', '/customers', $corpo);
        $customerId = (string) ($criado['id'] ?? '');
    }
    if ($customerId === '') {
        throw new ValidacaoException('O Asaas não devolveu o cliente.');
    }
    exec_sql(
        'INSERT INTO asaas_clientes (usuario_id, customer_id) VALUES (?, ?)',
        [$usuarioId, $customerId]
    );
    return $customerId;
}

function congelar_parcela(array $parcela): array
{
    $partes = explode('|', (string) $parcela['chave'], 2);
    $alunoId = (int) ($partes[0] ?? 0);
    $competencia = (string) $parcela['competencia'];
    $existe = q1(
        'SELECT id, situacao FROM cobrancas WHERE aluno_id = ? AND competencia = ?',
        [$alunoId, $competencia]
    );
    if ($existe !== null) {
        if ($existe['situacao'] === 'paga') {
            throw new ValidacaoException('Essa parcela já está paga.');
        }
        return ['id' => (int) $existe['id'], 'nova' => false];
    }
    $conta = conta_do_aluno($alunoId);
    if ($conta === null) {
        throw new ValidacaoException('A mensalidade desse aluno não está definida.');
    }
    exec_sql(
        'INSERT INTO cobrancas (
            aluno_id, pagador_id, competencia, plano_id, valor_cheio_centavos,
            desconto_inicial, desconto_familia, desconto_extra, valor_final_centavos, situacao
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'aberta\')',
        [
            $alunoId,
            (int) $conta['pagador_id'],
            $competencia,
            (int) $conta['plano_id'],
            (int) $parcela['cheio'],
            $parcela['inicial'],
            $parcela['familia'],
            $parcela['extra'],
            (int) $parcela['final'],
        ]
    );
    return ['id' => (int) db()->lastInsertId(), 'nova' => true];
}

function cancelar_pagamentos_pendentes(int $usuarioId): void
{
    $pendentes = q(
        "SELECT id, asaas_id FROM pagamentos WHERE usuario_id = ? AND situacao = 'pendente'",
        [$usuarioId]
    );
    foreach ($pendentes as $pendente) {
        $asaasId = (string) ($pendente['asaas_id'] ?? '');
        if ($asaasId !== '') {
            encerrar_cobranca_asaas($asaasId, (int) $pendente['id']);
        }
        exec_sql("UPDATE pagamentos SET situacao = 'cancelado' WHERE id = ? AND situacao = 'pendente'", [(int) $pendente['id']]);
    }
}

function encerrar_cobranca_asaas(string $asaasId, int $pagamentoId): void
{
    $caminho = str_starts_with($asaasId, 'pay_')
        ? '/payments/' . rawurlencode($asaasId)
        : '/checkouts/' . rawurlencode($asaasId) . '/cancel';
    $metodo = str_starts_with($asaasId, 'pay_') ? 'DELETE' : 'POST';
    try {
        asaas_request($metodo, $caminho);
    } catch (ValidacaoException $e) {
        $consulta = str_starts_with($asaasId, 'pay_')
            ? '/payments/' . rawurlencode($asaasId)
            : '/checkouts/' . rawurlencode($asaasId);
        try {
            $remoto = asaas_request('GET', $consulta);
        } catch (ValidacaoException) {
            throw $e;
        }
        $status = (string) ($remoto['status'] ?? '');
        if (in_array($status, ['PAID', 'CONFIRMED', 'RECEIVED'], true)) {
            confirmar_pagamento_local($pagamentoId, data_pagamento_asaas($remoto));
            throw new ValidacaoException('Esse pagamento já foi confirmado. Atualize a página.');
        }
        $encerrado = str_starts_with($asaasId, 'pay_')
            ? ($status === '' || !empty($remoto['deleted']))
            : in_array($status, ['CANCELED', 'EXPIRED'], true);
        if (!$encerrado) {
            throw $e;
        }
    }
}

function desfazer_cobranca_nova(int $pagamentoId, array $novas): void
{
    exec_sql('DELETE FROM pagamento_itens WHERE pagamento_id = ?', [$pagamentoId]);
    exec_sql('DELETE FROM pagamentos WHERE id = ?', [$pagamentoId]);
    foreach ($novas as $cobrancaId) {
        exec_sql(
            "DELETE FROM cobrancas WHERE id = ? AND situacao = 'aberta'",
            [$cobrancaId]
        );
    }
}

function cobrar_no_asaas(int $usuarioId, mixed $ate): string
{
    $preparo = preparar_pagamento($usuarioId, $ate);
    $novas = [];
    $cobrancas = [];
    foreach ($preparo['parcelas'] as $parcela) {
        $congelada = congelar_parcela($parcela);
        $cobrancas[] = $congelada['id'];
        if ($congelada['nova']) {
            $novas[] = $congelada['id'];
        }
    }
    $pagamentoId = 0;
    try {
        cancelar_pagamentos_pendentes($usuarioId);
        exec_sql(
            "INSERT INTO pagamentos (usuario_id, valor_centavos, situacao) VALUES (?, ?, 'pendente')",
            [$usuarioId, (int) $preparo['total']]
        );
        $pagamentoId = (int) db()->lastInsertId();
        foreach ($cobrancas as $cobrancaId) {
            exec_sql(
                'INSERT INTO pagamento_itens (pagamento_id, cobranca_id) VALUES (?, ?)',
                [$pagamentoId, $cobrancaId]
            );
        }
        $descricao = descricao_pagamento($preparo['parcelas']);
        $checkout = [
            'billingTypes' => ['PIX', 'CREDIT_CARD'],
            'chargeTypes' => ['DETACHED'],
            'minutesToExpire' => 1440,
            'externalReference' => (string) $pagamentoId,
            'items' => [[
                'name' => 'Mensalidade',
                'description' => $descricao,
                'quantity' => 1,
                'value' => centavos_para_reais((int) $preparo['total']),
            ]],
        ];
        $retorno = url_absoluta('/pagamentos');
        $checkout['callback'] = [
            'successUrl' => $retorno,
            'cancelUrl' => $retorno,
            'expiredUrl' => $retorno,
        ];
        $cobranca = asaas_request('POST', '/checkouts', $checkout);
    } catch (ValidacaoException $e) {
        if ($pagamentoId > 0) {
            desfazer_cobranca_nova($pagamentoId, $novas);
        } else {
            foreach ($novas as $cobrancaId) {
                exec_sql("DELETE FROM cobrancas WHERE id = ? AND situacao = 'aberta'", [$cobrancaId]);
            }
        }
        throw $e;
    }
    $asaasId = (string) ($cobranca['id'] ?? '');
    $fatura = $asaasId === '' ? '' : url_checkout_asaas($asaasId);
    if ($fatura === '' || !fatura_asaas_valida($fatura)) {
        if ($asaasId !== '') {
            try {
                asaas_request('POST', '/checkouts/' . rawurlencode($asaasId) . '/cancel');
            } catch (ValidacaoException) {
            }
        }
        desfazer_cobranca_nova($pagamentoId, $novas);
        throw new ValidacaoException('O Asaas não devolveu a página de pagamento.');
    }
    exec_sql(
        'UPDATE pagamentos SET asaas_id = ?, fatura_url = ? WHERE id = ?',
        [$asaasId, $fatura, $pagamentoId]
    );
    return $fatura;
}

function descricao_pagamento(array $parcelas): string
{
    $partes = [];
    foreach ($parcelas as $parcela) {
        $partes[] = $parcela['aluno_nome'] . ' ' . formatar_mes((string) $parcela['competencia']);
    }
    $texto = 'Mensalidade: ' . implode(', ', $partes);
    if (mb_strlen($texto) > 500) {
        $texto = mb_substr($texto, 0, 497) . '...';
    }
    return $texto;
}

function centavos_para_reais(int $centavos): float
{
    return (int) ($centavos / 100) + (($centavos % 100) / 100);
}

function url_checkout_asaas(string $id): string
{
    $config = asaas_config();
    $host = str_contains((string) $config['base_url'], 'sandbox') ? 'sandbox.asaas.com' : 'asaas.com';
    return 'https://' . $host . '/checkoutSession/show?id=' . rawurlencode($id);
}

function fatura_asaas_valida(string $url): bool
{
    $host = parse_url($url, PHP_URL_HOST);
    return is_string($host) && preg_match('/(^|\.)asaas\.com$/', $host) === 1;
}

function data_pagamento_asaas(array $remoto): string
{
    foreach (['clientPaymentDate', 'paymentDate'] as $campo) {
        $data = (string) ($remoto[$campo] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $data)) {
            return substr($data, 0, 10);
        }
    }
    return hoje();
}

function confirmar_pagamento_local(int $pagamentoId, string $pagoEm): void
{
    $pagamento = q1('SELECT id, situacao FROM pagamentos WHERE id = ?', [$pagamentoId]);
    if ($pagamento === null || $pagamento['situacao'] === 'cancelado') {
        return;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $pagoEm)) {
        $pagoEm = hoje();
    }
    transacao(function (PDO $pdo) use ($pagamentoId, $pagoEm): void {
        exec_sql("UPDATE pagamentos SET situacao = 'pago' WHERE id = ?", [$pagamentoId]);
        exec_sql(
            "UPDATE cobrancas c
             JOIN pagamento_itens i ON i.cobranca_id = c.id
             SET c.situacao = 'paga', c.pago_em = ?
             WHERE i.pagamento_id = ? AND c.situacao <> 'paga'",
            [$pagoEm, $pagamentoId]
        );
    });
}

function sincronizar_todos_pendentes(): void
{
    $usuarios = q(
        "SELECT DISTINCT usuario_id FROM pagamentos WHERE situacao = 'pendente' AND asaas_id IS NOT NULL"
    );
    foreach ($usuarios as $usuario) {
        sincronizar_pagamentos_pendentes((int) $usuario['usuario_id']);
    }
}

function sincronizar_pagamentos_pendentes(int $usuarioId): void
{
    try {
        asaas_config();
    } catch (ValidacaoException $e) {
        return;
    }
    $pendentes = q(
        "SELECT id, asaas_id FROM pagamentos WHERE usuario_id = ? AND situacao = 'pendente' AND asaas_id IS NOT NULL",
        [$usuarioId]
    );
    foreach ($pendentes as $pendente) {
        $asaasId = (string) $pendente['asaas_id'];
        $consulta = str_starts_with($asaasId, 'pay_')
            ? '/payments/' . rawurlencode($asaasId)
            : '/checkouts/' . rawurlencode($asaasId);
        try {
            $remoto = asaas_request('GET', $consulta);
        } catch (ValidacaoException $e) {
            error_log($e->getMessage());
            continue;
        }
        $status = (string) ($remoto['status'] ?? '');
        if (in_array($status, ['PAID', 'CONFIRMED', 'RECEIVED'], true)) {
            confirmar_pagamento_local((int) $pendente['id'], data_pagamento_asaas($remoto));
        } elseif (in_array($status, ['CANCELED', 'EXPIRED'], true)) {
            exec_sql(
                "UPDATE pagamentos SET situacao = 'cancelado' WHERE id = ? AND situacao = 'pendente'",
                [(int) $pendente['id']]
            );
        }
    }
}

function registrar_evento_asaas(string $eventoId): bool
{
    try {
        exec_sql('INSERT INTO asaas_eventos (id) VALUES (?)', [$eventoId]);
        return true;
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '23000') {
            return false;
        }
        throw $e;
    }
}

function processar_webhook_asaas(string $corpo): void
{
    $config = asaas_config();
    $token = (string) ($_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? '');
    $esperado = (string) ($config['webhook_token'] ?? '');
    if ($esperado === '' || $token === '' || !hash_equals($esperado, $token)) {
        http_response_code(401);
        exit('Token inválido.');
    }
    $dados = json_decode($corpo, true);
    if (!is_array($dados)) {
        http_response_code(200);
        exit('ok');
    }
    $evento = (string) ($dados['event'] ?? '');
    $eventoId = (string) ($dados['id'] ?? '');
    if ($eventoId === '') {
        $eventoId = hash('sha256', $corpo);
    }
    if (q1('SELECT id FROM asaas_eventos WHERE id = ?', [$eventoId]) !== null) {
        http_response_code(200);
        exit('ok');
    }
    $checkout = is_array($dados['checkout'] ?? null) ? $dados['checkout'] : [];
    $pagamento = is_array($dados['payment'] ?? null) ? $dados['payment'] : [];
    $localId = (int) ($pagamento['externalReference'] ?? $checkout['externalReference'] ?? 0);
    if ($localId === 0 && isset($checkout['id'])) {
        $salvo = q1('SELECT id FROM pagamentos WHERE asaas_id = ?', [(string) $checkout['id']]);
        $localId = $salvo === null ? 0 : (int) $salvo['id'];
    }
    if (($evento === 'CHECKOUT_PAID' || $evento === 'PAYMENT_CONFIRMED' || $evento === 'PAYMENT_RECEIVED') && $localId > 0) {
        confirmar_pagamento_local($localId, $pagamento === [] ? hoje() : data_pagamento_asaas($pagamento));
    } elseif (in_array($evento, ['CHECKOUT_CANCELED', 'CHECKOUT_EXPIRED', 'PAYMENT_DELETED'], true) && $localId > 0) {
        exec_sql(
            "UPDATE pagamentos SET situacao = 'cancelado' WHERE id = ? AND situacao = 'pendente'",
            [$localId]
        );
    }
    registrar_evento_asaas($eventoId);
    http_response_code(200);
    echo 'ok';
}
