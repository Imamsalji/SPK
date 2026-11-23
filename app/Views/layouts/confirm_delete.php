<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content" id="confirmDeleteForm" data-loading data-loading-text="Menghapus...">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title h5" id="confirmDeleteTitle">Konfirmasi hapus</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Anda yakin ingin menghapus <strong data-delete-name></strong>?</p>
                <p class="small text-body-secondary mb-0" data-delete-note></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
            </div>
        </form>
    </div>
</div>