<!DOCTYPE html>
<html lang="id" data-bs-theme="light">

<head>
    <?= $this->include('layouts/head') ?>
</head>

<body>
    <div class="app-shell">
        <?= $this->include('layouts/sidebar') ?>
        <div class="sidebar-backdrop" data-sidebar-close></div>

        <div class="app-main">
            <?= $this->include('layouts/topbar') ?>

            <main class="app-content">
                <?= $this->include('layouts/alerts') ?>
                <?= $this->renderSection('content') ?>
            </main>

            <?= $this->include('layouts/footer') ?>
        </div>
    </div>

    <?= $this->include('layouts/confirm_delete') ?>
    <?= $this->include('layouts/scripts') ?>
    <?= $this->renderSection('scripts') ?>
</body>

</html>