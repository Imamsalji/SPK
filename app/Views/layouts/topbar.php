<?php $user = auth_user(); ?>
<header class="topbar">
    <button class="btn btn-link text-body d-lg-none p-0 me-3 fs-4" type="button"
        data-sidebar-toggle aria-label="Buka menu">
        <i class="bi bi-list"></i>
    </button>

    <h1 class="topbar-title"><?= esc($title ?? '') ?></h1>

    <div class="ms-auto d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm" type="button"
            data-theme-toggle aria-label="Ganti tema">
            <i class="bi bi-moon-stars" data-theme-icon></i>
        </button>

        <div class="dropdown">
            <button class="btn btn-light border d-flex align-items-center gap-2 py-1" type="button"
                data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar"><?= esc(auth_initials()) ?></span>
                <span class="d-none d-sm-inline text-start lh-sm">
                    <span class="d-block small fw-semibold"><?= esc($user['name'] ?? '') ?></span>
                    <span class="d-block text-body-secondary" style="font-size:.72rem">
                        <?= esc(ucfirst($user['role'] ?? '')) ?>
                    </span>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li>
                    <h6 class="dropdown-header">@<?= esc($user['username'] ?? '') ?></h6>
                </li>
                <li>
                    <form action="<?= site_url('logout') ?>" method="post">
                        <?= csrf_field() ?>
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>Keluar
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>