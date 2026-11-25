<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$isAdmin = auth_is('admin');
$offset  = ($pager->getCurrentPage() - 1) * $pager->getPerPage();
$hasFilter = $filters['q'] !== '' || $filters['user'] !== '';
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <form method="get" action="<?= site_url('riwayat') ?>" class="row g-2 flex-grow-1">
                <div class="col-12 col-md-5">
                    <input type="search" name="q" value="<?= esc($filters['q']) ?>" class="form-control"
                        placeholder="Cari nama simulasi atau laptop terbaik<?= $isAdmin ? ' atau pengguna' : '' ?>..." maxlength="100">
                </div>
                <?php if ($isAdmin): ?>
                    <div class="col-6 col-md-3">
                        <select name="user" class="form-select" aria-label="Filter pengguna">
                            <option value="">Semua pengguna</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int) $u['user_id'] ?>" <?= $filters['user'] === (string) $u['user_id'] ? 'selected' : '' ?>>
                                    <?= esc($u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <?php if ($hasFilter): ?>
                        <a href="<?= site_url('riwayat') ?>" class="btn btn-link">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
            <?php if (auth_is('mahasiswa')): ?>
                <a href="<?= site_url('simulasi') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Simulasi baru</a>
            <?php endif; ?>
        </div>

        <?php if ($calculations === []): ?>
            <div class="empty-state">
                <i class="bi bi-clock-history"></i>
                <h2 class="h6 mt-2"><?= $hasFilter ? 'Tidak ada riwayat yang cocok' : 'Belum ada riwayat perhitungan' ?></h2>
                <p class="text-body-secondary mb-3">
                    <?= $hasFilter
                        ? 'Coba ubah kata kunci atau filter.'
                        : ($isAdmin ? 'Riwayat muncul setelah mahasiswa menjalankan simulasi.' : 'Jalankan simulasi untuk membuat perhitungan pertama Anda.') ?>
                </p>
                <?php if (! $hasFilter && auth_is('mahasiswa')): ?>
                    <a href="<?= site_url('simulasi') ?>" class="btn btn-primary">Mulai simulasi</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Simulasi</th>
                            <?php if ($isAdmin): ?><th>Pengguna</th><?php endif; ?>
                            <th>Tanggal</th>
                            <th class="text-center">Laptop</th>
                            <th>Rekomendasi terbaik</th>
                            <th class="text-end" style="width:110px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calculations as $i => $row): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc($row['name']) ?></div>
                                    <div class="small text-body-secondary"><?= (int) $row['total_criteria'] ?> kriteria &middot; <?= esc($row['method']) ?></div>
                                </td>
                                <?php if ($isAdmin): ?><td><?= esc($row['user_name_snapshot']) ?></td><?php endif; ?>
                                <td class="text-nowrap small"><?= esc(date('d/m/Y H:i', strtotime($row['calculated_at']))) ?></td>
                                <td class="text-center"><span class="badge text-bg-secondary"><?= (int) $row['total_alternatives'] ?></span></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) $row['best_alternative_name']) ?></div>
                                    <div class="small text-body-secondary" title="<?= esc(fmt_dec($row['best_score'], 8), 'attr') ?>">
                                        V = <?= fmt_dec($row['best_score'], 4) ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="<?= site_url('riwayat/' . $row['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>Lihat
                                    </a>
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
<?= $this->endSection() ?>