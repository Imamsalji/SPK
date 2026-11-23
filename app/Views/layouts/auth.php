<!DOCTYPE html>
<html lang="id" data-bs-theme="light">

<head>
    <?= $this->include('layouts/head') ?>
</head>

<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <?= $this->renderSection('content') ?>
            <p class="text-center text-white-50 small mt-3 mb-0">
                &copy; <?= date('Y') ?> Laptop Decision Support System
            </p>
        </div>
    </div>

    <?= $this->include('layouts/scripts') ?>
</body>

</html>