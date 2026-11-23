<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $offset = ($pager->getCurrentPage() - 1) * $pager->getPerPage(); ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <form method="get" action="<?= site_url('admin/pengguna') ?>" class="row g-2 flex-grow-1">
                <div class="col-12 col-md-5">
                    <input type="search" name="q" value="<?= esc($keyword) ?>" class="form-control"
                        placeholder="Cari nama, username, atau email..." maxlength="100">
                </div>
                <div class="col-6 col-md-3">
                    <select name="role" class="form-select" aria-label="Filter role">
                        <option value="">Semua role</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int) $r['id'] ?>" <?= $role === (string) $r['id'] ? 'selected' : '' ?>><?= esc($r['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <?php if ($keyword !== '' || $role !== ''): ?>
                        <a href="<?= site_url('admin/pengguna') ?>" class="btn btn-link">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" data-mode="create">
                <i class="bi bi-person-plus me-1"></i>Tambah Pengguna
            </button>
        </div>

        <?php if ($users === []): ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <h2 class="h6 mt-2">Tidak ada pengguna</h2>
                <p class="text-body-secondary mb-0">Tidak ada akun yang cocok dengan filter.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Pengguna</th>
                            <th>Role</th>
                            <th class="text-center">Status</th>
                            <th>Login terakhir</th>
                            <th class="text-end" style="width:130px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $i => $u): ?>
                            <?php
                            $isSelf = (int) $u['id'] === $selfId;
                            $values = [
                                'id'        => (int) $u['id'],
                                'name'      => $u['name'],
                                'username'  => $u['username'],
                                'email'     => $u['email'] ?? '',
                                'role_id'   => (string) $u['role_id'],
                                'is_active' => (string) (int) $u['is_active'],
                            ];
                            ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc($u['name']) ?><?= $isSelf ? ' <span class="badge text-bg-info">Anda</span>' : '' ?></div>
                                    <div class="small text-body-secondary">@<?= esc($u['username']) ?><?= $u['email'] ? ' &middot; ' . esc($u['email']) : '' ?></div>
                                </td>
                                <td><span class="badge text-bg-<?= $u['role_name'] === 'admin' ? 'primary' : 'secondary' ?>"><?= esc($u['role_label']) ?></span></td>
                                <td class="text-center">
                                    <span class="badge text-bg-<?= (int) $u['is_active'] === 1 ? 'success' : 'secondary' ?>">
                                        <?= (int) $u['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td class="small text-body-secondary">
                                    <?= $u['last_login_at'] ? esc(date('d/m/Y H:i', strtotime($u['last_login_at']))) : 'Belum pernah' ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal" data-bs-target="#userModal" data-mode="edit"
                                        data-values="<?= esc(json_encode($values, JSON_UNESCAPED_UNICODE), 'attr') ?>"
                                        aria-label="Ubah"><i class="bi bi-pencil"></i></button>
                                    <?php if ($isSelf): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                            title="Tidak dapat menghapus akun sendiri" aria-label="Hapus"><i class="bi bi-trash"></i></button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-confirm-delete
                                            data-action="<?= site_url('admin/pengguna/' . $u['id'] . '/hapus') ?>"
                                            data-name="<?= esc($u['username'], 'attr') ?>"
                                            data-note="Pengguna yang sudah memiliki riwayat perhitungan tidak dapat dihapus; nonaktifkan saja."
                                            aria-label="Hapus"><i class="bi bi-trash"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= $this->include('layouts/pagination', ['pager' => $pager]) ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true" data-crud-modal<?= reopen_attr() ?>>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="post" class="modal-content" action="<?= site_url('admin/pengguna') ?>" data-loading autocomplete="off"
            data-create-url="<?= site_url('admin/pengguna') ?>"
            data-update-url="<?= site_url('admin/pengguna/__ID__/ubah') ?>"
            data-title-create="Tambah Pengguna" data-title-edit="Ubah Pengguna">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title h5" data-modal-title>Tambah Pengguna</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="us_name" class="form-label">Nama lengkap</label>
                        <input type="text" id="us_name" name="name" class="form-control<?= invalid_class('name') ?>" maxlength="100" required>
                        <?= field_feedback('name') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="us_username" class="form-label">Username</label>
                        <input type="text" id="us_username" name="username" class="form-control<?= invalid_class('username') ?>"
                            maxlength="50" autocomplete="off" required>
                        <?= field_feedback('username') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="us_email" class="form-label">Email <span class="text-body-secondary">(opsional)</span></label>
                        <input type="email" id="us_email" name="email" class="form-control<?= invalid_class('email') ?>" maxlength="150">
                        <?= field_feedback('email') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="us_role" class="form-label">Role</label>
                        <select id="us_role" name="role_id" class="form-select<?= invalid_class('role_id') ?>" required>
                            <option value="">-- Pilih role --</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= (int) $r['id'] ?>"><?= esc($r['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_feedback('role_id') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="us_password" class="form-label">Password</label>
                        <div class="input-group<?= invalid_class('password') ? ' has-validation' : '' ?>">
                            <input type="password" id="us_password" name="password" class="form-control<?= invalid_class('password') ?>"
                                maxlength="72" autocomplete="new-password" data-required-create>
                            <button class="btn btn-outline-secondary" type="button" data-toggle-password="us_password" aria-label="Tampilkan password">
                                <i class="bi bi-eye"></i>
                            </button>
                            <?= field_feedback('password') ?>
                        </div>
                        <div class="form-text" data-mode-only="create">Minimal 8 karakter, mengandung huruf dan angka.</div>
                        <div class="form-text d-none" data-mode-only="edit">Kosongkan jika tidak ingin mengubah password.</div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div>
                            <input type="hidden" name="is_active" value="0">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="us_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="us_active">Akun aktif</label>
                            </div>
                            <?php if (form_err('is_active')): ?><div class="text-danger small"><?= esc(form_err('is_active')) ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>