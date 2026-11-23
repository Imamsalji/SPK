<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?= csrf_meta() ?>
<title><?= esc($title ?? 'Aplikasi') ?> | Laptop DSS</title>

<script>
    // Terapkan tema tersimpan sebelum render agar tidak berkedip.
    (function() {
        try {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || t === 'light') {
                document.documentElement.setAttribute('data-bs-theme', t);
            }
        } catch (e) {}
    })();
</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
<?= $this->renderSection('styles') ?>