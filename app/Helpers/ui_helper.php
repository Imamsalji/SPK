<?php

use CodeIgniter\HTTP\RedirectResponse;

if (! function_exists('rupiah')) {
    function rupiah(float|int|string|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}

if (! function_exists('num_id')) {
    /** Angka format Indonesia, nol di belakang koma dibuang: 7.00 -> "7", 7.5 -> "7,5". */
    function num_id(float|int|string|null $value): string
    {
        $s = number_format((float) $value, 2, ',', '.');

        return rtrim(rtrim($s, '0'), ',');
    }
}

if (! function_exists('plain_number')) {
    /** Angka polos untuk atribut input: 7999000.00 -> "7999000". */
    function plain_number(float|int|string|null $value): string
    {
        $s = number_format((float) $value, 2, '.', '');

        return rtrim(rtrim($s, '0'), '.');
    }
}

if (! function_exists('format_score')) {
    function format_score(float|int|string|null $value, ?string $unit = null): string
    {
        if ($unit === 'Rp') {
            return rupiah($value);
        }

        return num_id($value) . ($unit ? ' ' . $unit : '');
    }
}

if (! function_exists('laptop_image_url')) {
    function laptop_image_url(?string $filename): ?string
    {
        return $filename ? base_url('uploads/laptops/' . rawurlencode($filename)) : null;
    }
}

if (! function_exists('form_err')) {
    /** Pesan error validasi (flash) untuk satu field, atau null. */
    function form_err(string $field): ?string
    {
        $errors = session()->getFlashdata('form_errors');

        return is_array($errors) && isset($errors[$field]) ? (string) $errors[$field] : null;
    }
}

if (! function_exists('invalid_class')) {
    function invalid_class(string $field): string
    {
        return form_err($field) !== null ? ' is-invalid' : '';
    }
}

if (! function_exists('field_feedback')) {
    function field_feedback(string $field): string
    {
        $message = form_err($field);

        return $message === null ? '' : '<div class="invalid-feedback">' . esc($message) . '</div>';
    }
}

if (! function_exists('reopen_attr')) {
    /** Atribut data-reopen untuk membuka kembali modal setelah validasi gagal. */
    function reopen_attr(): string
    {
        $state = session()->getFlashdata('reopen');

        if (! is_array($state)) {
            return '';
        }

        return ' data-reopen="' . esc(json_encode($state, JSON_UNESCAPED_UNICODE), 'attr') . '"';
    }
}

if (! function_exists('redirect_form_errors')) {
    /**
     * Kembali ke halaman daftar dengan error per field dan isian lama.
     * Jangan pernah memasukkan password ke $old.
     */
    function redirect_form_errors(string $uri, string $mode, ?int $id, array $errors, array $old): RedirectResponse
    {
        return redirect()->to(site_url($uri))
            ->with('form_errors', $errors)
            ->with('reopen', ['mode' => $mode, 'id' => $id, 'old' => $old]);
    }
}
