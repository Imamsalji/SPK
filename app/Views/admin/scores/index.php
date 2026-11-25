<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$offset        = ($pager->getCurrentPage() - 1) * $pager->getPerPage();
$criteriaCount = count($criteria);
$cards         = [
    ['Total Laptop',  $summary['total'],      'bi-laptop',             'primary'],
    ['Nilai Lengkap', $summary['complete'],   'bi-check-circle',       'success'],
    ['Belum Lengkap', $summary['incomplete'], 'bi-exclamation-circle', 'warning'],
];
?>

<div class="row g-3 mb-3">
    <?php foreach ($cards as [$label, $value, $icon, $color]): ?>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis"><i class="bi <?= $icon ?>"></i></div>
                    <div>
                        <div class="stat-value"><?= (int) $value ?></div>
                        <div class="text-body-secondary small"><?= esc($label) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($criteriaCount === 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Belum ada kriteria aktif. Aktifkan atau tambahkan kriteria di menu
        <a href="<?= site_url('admin/kriteria') ?>" class="alert-link">Kriteria</a> terlebih dahulu.
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= site_url('admin/nilai') ?>" class="row g-2 mb-3">
            <div class="col-12 col-md-4">
                <input type="search" name="q" value="<?= esc($filters['q']) ?>" class="form-control"
                    placeholder="Cari nama, model, atau merek..." maxlength="100">
            </div>
            <div class="col-6 col-md-3">
                <select name="brand" class="form-select" aria-label="Filter merek">
                    <option value="">Semua merek</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= (int) $b['id'] ?>" <?= $filters['brand'] === (string) $b['id'] ? 'selected' : '' ?>><?= esc($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="complete" class="form-select" aria-label="Filter kelengkapan">
                    <option value="">Semua kelengkapan</option>
                    <option value="1" <?= $filters['complete'] === '1' ? 'selected' : '' ?>>Lengkap</option>
                    <option value="0" <?= $filters['complete'] === '0' ? 'selected' : '' ?>>Belum lengkap</option>
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <?php if ($filters['q'] !== '' || $filters['brand'] !== '' || $filters['complete'] !== ''): ?>
                    <a href="<?= site_url('admin/nilai') ?>" class="btn btn-link">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($laptops === []): ?>
            <div class="empty-state">
                <i class="bi bi-table"></i>
                <h2 class="h6 mt-2">Tidak ada data</h2>
                <p class="text-body-secondary mb-0">Tidak ada laptop yang cocok dengan filter.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Laptop</th>
                            <?php foreach ($criteria as $c): ?>
                                <th class="text-end text-nowrap">
                                    <?= esc($c['code']) ?>
                                    <?php if ($c['source_field'] === 'price'): ?>
                                        <i class="bi bi-link-45deg" title="Otomatis dari harga laptop"></i>
                                    <?php endif; ?>
                                    <div class="small fw-normal text-body-secondary"><?= esc($c['name']) ?></div>
                                    <span class="badge text-bg-<?= $c['type'] === 'cost' ? 'danger' : 'success' ?> fw-normal">
                                        <?= $c['type'] === 'cost' ? 'Cost' : 'Benefit' ?>
                                    </span>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-center">Kelengkapan</th>
                            <th class="text-end" style="width:130px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laptops as $i => $l): ?>
                            <?php
                            $lid  = (int) $l['id'];
                            $have = 0;
                            foreach ($criteria as $c) {
                                if (isset($scores[$lid][(int) $c['id']])) {
                                    $have++;
                                }
                            }
                            $complete = $criteriaCount > 0 && $have === $criteriaCount;
                            ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc($l['name']) ?>
                                        <?php if ((int) $l['is_demo'] === 1): ?><span class="badge text-bg-warning ms-1">Demo</span><?php endif; ?>
                                        <?php if ((int) $l['is_active'] === 0): ?><span class="badge text-bg-secondary ms-1">Nonaktif</span><?php endif; ?>
                                    </div>
                                    <div class="small text-body-secondary"><?= esc($l['brand_name']) ?> &middot; <?= esc($l['model']) ?></div>
                                </td>
                                <?php foreach ($criteria as $c): ?>
                                    <?php $score = $scores[$lid][(int) $c['id']] ?? null; ?>
                                    <td class="text-end text-nowrap">
                                        <?php if ($score === null): ?>
                                            <span class="badge text-bg-warning">kosong</span>
                                        <?php else: ?>
                                            <span title="<?= esc((string) ($score['source_note'] ?? ''), 'attr') ?>">
                                                <?= esc(format_score($score['value'], $c['unit'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-center">
                                    <span class="badge text-bg-<?= $complete ? 'success' : 'warning' ?>">
                                        <?= $have ?>/<?= $criteriaCount ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= site_url('admin/laptop/' . $lid . '/nilai') ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square me-1"></i>Isi nilai
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="small text-body-secondary mt-2">
                Arahkan kursor ke sebuah nilai untuk melihat sumber datanya. Ikon <i class="bi bi-link-45deg"></i> berarti nilai diambil otomatis dari harga laptop.
            </div>
            <?= $this->include('layouts/pagination', ['pager' => $pager]) ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>