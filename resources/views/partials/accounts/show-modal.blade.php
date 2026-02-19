<div class="modal fade" id="viewAccountModal" tabindex="-1" aria-labelledby="viewAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewAccountModalLabel">
                    <i class="bi bi-info-circle me-2"></i>Detail Akun
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6>Informasi Akun</h6>
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Nama</th>
                                <td id="viewAccountName">-</td>
                            </tr>
                            <tr>
                                <th>Tipe</th>
                                <td id="viewAccountType">-</td>
                            </tr>
                            <tr>
                                <th>Saldo</th>
                                <td id="viewAccountBalance" class="currency">-</td>
                            </tr>
                            <tr>
                                <th>Saldo Awal</th>
                                <td id="viewAccountInitialBalance" class="currency">-</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6>Informasi Lainnya</h6>
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Mata Uang</th>
                                <td id="viewAccountCurrency">-</td>
                            </tr>
                            <tr>
                                <th>Nomor Akun</th>
                                <td id="viewAccountNumber">-</td>
                            </tr>
                            <tr>
                                <th>Nama Bank</th>
                                <td id="viewBankName">-</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td id="viewAccountStatus">-</td>
                            </tr>
                            <tr>
                                <th>Default</th>
                                <td id="viewAccountDefault">-</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Catatan</h6>
                        <div class="card bg-light">
                            <div class="card-body">
                                <p id="viewAccountNotes" class="mb-0">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>