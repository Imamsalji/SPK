<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="card shadow-lg border-0">
    <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="brand-mark mb-3"><i class="bi bi-laptop"></i></div>
            <h1 class="h4 fw-bold mb-1">Laptop Decision Support System</h1>
            <p class="text-body-secondary small mb-0">
                Pemilihan laptop terbaik untuk mahasiswa dengan metode SAW
            </p>
        </div>

        <?= $this->include('layouts/alerts') ?>

        <form action="<?= site_url('login') ?>" method="post" data-loading data-loading-text="Memproses..." novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username"
                        value="<?= esc((string) session()->getFlashdata('old_username')) ?>"
                        maxlength="50" autocomplete="username" autofocus required>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password"
                        maxlength="255" autocomplete="current-password" required>
                    <button class="btn btn-outline-secondary" type="button"
                        data-toggle-password="password" aria-label="Tampilkan password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
            </button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>