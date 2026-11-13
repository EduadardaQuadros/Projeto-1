<?php

declare(strict_types=1);

function tela_login(): void
{
    if (Auth::usuario() !== null) {
        redirect('/');
    }
    render('login', [
        'titulo' => 'Entrar',
        'erro' => null,
        'email' => '',
        'dica_inicial' => senha_inicial_ativa(),
    ], true);
}

function entrar(): void
{
    if (Auth::usuario() !== null) {
        redirect('/');
    }
    $email = mb_strtolower(trim(campo('email')));
    $senha = campo_raw('senha');
    $erro = 'E-mail ou senha incorretos.';
    $usuario = q1('SELECT id, nome, email, senha_hash, papel, ativo FROM usuarios WHERE email = ?', [$email]);
    if ($usuario !== null && password_verify($senha, $usuario['senha_hash'])) {
        if (!(int) $usuario['ativo']) {
            $erro = 'Acesso desativado. Fale com a equipe da escola.';
        } else {
            Auth::entrar($usuario);
            redirect('/');
        }
    }
    render('login', [
        'titulo' => 'Entrar',
        'erro' => $erro,
        'email' => $email,
        'dica_inicial' => senha_inicial_ativa(),
    ], true);
}

function sair(): void
{
    require_login();
    Auth::sair();
    redirect('/login');
}

function senha_inicial_ativa(): bool
{
    $admin = q1('SELECT senha_hash FROM usuarios WHERE email = ?', ['admin@escola.local']);
    return $admin !== null && password_verify('volei123', $admin['senha_hash']);
}

function tela_senha(): void
{
    $usuario = require_login();
    render('senha', [
        'titulo' => 'Trocar senha',
        'erro' => null,
        'usuario' => $usuario,
    ]);
}

function salvar_senha(): void
{
    $usuario = require_login();
    try {
        trocar_propria_senha((int) $usuario['id']);
        flash('ok', 'Senha alterada.');
        redirect('/');
    } catch (ValidacaoException $e) {
        render('senha', [
            'titulo' => 'Trocar senha',
            'erro' => $e->getMessage(),
            'usuario' => $usuario,
        ]);
    }
}
