<?php

if (! function_exists('auth_user')) {
    /**
     * Data user yang sedang login (dari session) atau null.
     *
     * @return array{id:int,name:string,username:string,role:string}|null
     */
    function auth_user(): ?array
    {
        $user = session()->get('auth_user');

        return is_array($user) ? $user : null;
    }
}

if (! function_exists('auth_check')) {
    function auth_check(): bool
    {
        return auth_user() !== null;
    }
}

if (! function_exists('auth_is')) {
    /** Cek apakah role user saat ini termasuk salah satu role yang diberikan. */
    function auth_is(string ...$roles): bool
    {
        $user = auth_user();

        return $user !== null && in_array($user['role'], $roles, true);
    }
}

if (! function_exists('auth_initials')) {
    /** Inisial (maks. 2 huruf) dari nama user untuk avatar. */
    function auth_initials(): string
    {
        $name  = trim((string) (auth_user()['name'] ?? ''));
        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out   = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $out .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $out !== '' ? $out : '?';
    }
}
