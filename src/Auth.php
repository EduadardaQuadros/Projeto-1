<?php

declare(strict_types=1);

final class Auth
{
    private static bool $resolvido = false;
    private static ?array $usuario = null;

    public static function usuario(): ?array
    {
        if (self::$resolvido) {
            return self::$usuario;
        }
        self::$resolvido = true;
        $id = $_SESSION['usuario_id'] ?? null;
        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            return null;
        }
        $usuario = q1(
            'SELECT id, nome, email, papel, ativo FROM usuarios WHERE id = ?',
            [(int) $id]
        );
        if ($usuario === null || !(int) $usuario['ativo']) {
            unset($_SESSION['usuario_id']);
            return null;
        }
        self::$usuario = $usuario;
        return $usuario;
    }

    public static function entrar(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION = ['usuario_id' => (int) $usuario['id']];
        self::$resolvido = false;
        self::$usuario = null;
    }

    public static function sair(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        self::$resolvido = false;
        self::$usuario = null;
    }
}
