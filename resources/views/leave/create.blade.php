@extends('layouts.app')

@section('title', 'Ajukan Cuti Baru')

@section('content')
<div class="page-header">
    <h1 class="page-title">Ajukan Cuti Baru</h1>
    <p class="page-subtitle">Isi form di bawah untuk mengajukan cuti</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-file-earmark-plus me-2"></i> Form Pengajuan Cuti
            </div>
            <div class="card-body">
                <form action="{{ route('leave.store') }}" method="POST" enctype="multipart/form-data" id="leaveForm">
                    @csrf
                    
                    <!-- Leave Type -->
                    <div class="mb-4">
                        <label for="leave_type_id" class="form-label">
                            <i class="bi bi-tag text-primary me-2"></i>Jenis Cuti
                        </label>
                        <select class="form-select" id="leave_type_id" name="leave_type_id" required>
                            <option value="">-- Pilih Jenis Cuti --</option>
                            @foreach($leaveTypes ?? [] as $type)
                            <option value="{{ $type['id'] }}" 
                                    data-max-days="{{ $type['max_hari_per_pengajuan'] }}"
                                    data-requires-doc="{{ $type['butuh_dokumen'] ? 'true' : 'false' }}">
                                {{ $type['nama'] }} (Max: {{ $type['max_hari_per_pengajuan'] }} hari)
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Pilih jenis cuti yang sesuai dengan kebutuhan Anda</small>
                    </div>
                    
                    <!-- Date Range -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="tanggal_mulai" class="form-label">
                                <i class="bi bi-calendar-event text-success me-2"></i>Tanggal Mulai
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   id="tanggal_mulai" 
                                   name="tanggal_mulai" 
                                   min="{{ date('Y-m-d') }}"
                                   required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="tanggal_selesai" class="form-label">
                                <i class="bi bi-calendar-check text-danger me-2"></i>Tanggal Selesai
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   id="tanggal_selesai" 
                                   name="tanggal_selesai" 
                                   min="{{ date('Y-m-d') }}"
                                   required>
                        </div>
                    </div>
                    
                    <!-- Total Days (Auto calculated) -->
                    <div class="mb-4">
                        <div class="alert alert-info" id="totalDaysAlert" style="display: none;">
                            <i class="bi bi-info-circle me-2"></i>
                            Total hari cuti: <strong id="totalDaysText">0 hari</strong>
                        </div>
                    </div>
                    
                    <!-- Reason -->
                    <div class="mb-4">
                        <label for="alasan" class="form-label">
                            <i class="bi bi-chat-left-text text-warning me-2"></i>Alasan Cuti
                        </label>
                        <textarea class="form-control" 
                                  id="alasan" 
                                  name="alasan" 
                                  rows="4" 
                                  placeholder="Jelaskan alasan Anda mengajukan cuti..."
                                  minlength="20"
                                  required></textarea>
                        <small class="text-muted">Minimal 20 karakter. Jelaskan dengan jelas alasan pengajuan cuti.</small>
                    </div>
                    
                    <!-- Document Upload -->
                    <div class="mb-4" id="documentSection">
                        <label for="lampiran" class="form-label">
                            <i class="bi bi-paperclip text-info me-2"></i>Lampiran Dokumen
                            <span id="requiredBadge" class="badge bg-danger ms-2" style="display: none;">Wajib</span>
                        </label>
                        <input type="file" 
                               class="form-control" 
                               id="lampiran" 
                               name="lampiran" 
                               accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">
                            Format: PDF, JPG, PNG. Maksimal 5MB. 
                            <span id="docRequirementText">Lampirkan dokumen pendukung jika diperlukan (contoh: surat dokter).</span>
                        </small>
                        
                        <!-- File Preview -->
                        <div id="filePreview" class="mt-3" style="display: none;">
                            <div class="alert alert-success">
                                <i class="bi bi-file-earmark-check me-2"></i>
                                File terpilih: <strong id="fileName"></strong>
                                <button type="button" class="btn btn-sm btn-outline-danger float-end" onclick="clearFile()">
                                    <i class="bi bi-x"></i> Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Leave Balance Warning -->
                    <div class="alert alert-warning" id="balanceWarning" style="display: none;">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Perhatian:</strong> Sisa cuti Anda: <strong id="remainingLeave">{{ $leaveBalance->sisa ?? 0 }}</strong> hari. 
                        Pastikan cukup untuk pengajuan ini.
                    </div>
                    
                    <!-- Terms Agreement -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="agreement" name="agreement" required>
                        <label class="form-check-label" for="agreement">
                            Saya menyatakan bahwa data yang saya berikan adalah benar dan saya bersedia menerima konsekuensi jika terbukti palsu.
                        </label>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i>Ajukan Cuti
                        </button>
                        <a href="{{ route('leave.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Info Card -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <i class="bi bi-info-circle me-2"></i> Tips Pengajuan Cuti
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li class="mb-2">Ajukan cuti minimal <strong>3 hari sebelum</strong> tanggal mulai cuti</li>
                    <li class="mb-2">Pastikan saldo cuti Anda mencukupi</li>
                    <li class="mb-2">Upload dokumen pendukung untuk cuti sakit (surat dokter)</li>
                    <li class="mb-2">Isi alasan dengan jelas untuk mempercepat proses approval</li>
                    <li>Cek email secara berkala untuk notifikasi status pengajuan</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const leaveBalance = {{ $leaveBalance->sisa ?? 0 }};
    
    // Calculate total days
    function calculateTotalDays() {
        const startDate = document.getElementById('tanggal_mulai').value;
        const endDate = document.getElementById('tanggal_selesai').value;
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            document.getElementById('totalDaysText').textContent = diffDays + ' hari';
            document.getElementById('totalDaysAlert').style.display = 'block';
            
            // Check leave balance
            if (diffDays > leaveBalance) {
                document.getElementById('balanceWarning').style.display = 'block';
            } else {
                document.getElementById('balanceWarning').style.display = 'none';
            }
            
            // Check max days per leave type
            const leaveTypeSelect = document.getElementById('leave_type_id');
            const selectedOption = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
            const maxDays = parseInt(selectedOption.getAttribute('data-max-days') || 999);
            
            if (diffDays > maxDays) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan',
                    text: `Total hari (${diffDays}) melebihi batas maksimal untuk jenis cuti ini (${maxDays} hari).`
                });
            }
            
            return diffDays;
        }
        
        return 0;
    }
    
    // Leave type change handler
    document.getElementById('leave_type_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const requiresDoc = selectedOption.getAttribute('data-requires-doc') === 'true';
        
        const lampiranInput = document.getElementById('lampiran');
        const requiredBadge = document.getElementById('requiredBadge');
        const docRequirementText = document.getElementById('docRequirementText');
        
        if (requiresDoc) {
            lampiranInput.required = true;
            requiredBadge.style.display = 'inline';
            docRequirementText.textContent = 'Dokumen pendukung WAJIB untuk jenis cuti ini.';
        } else {
            lampiranInput.required = false;
            requiredBadge.style.display = 'none';
            docRequirementText.textContent = 'Lampirkan dokumen pendukung jika diperlukan.';
        }
        
        calculateTotalDays();
    });
    
    // Date change handlers
    document.getElementById('tanggal_mulai').addEventListener('change', function() {
        document.getElementById('tanggal_selesai').min = this.value;
        calculateTotalDays();
    });
    
    document.getElementById('tanggal_selesai').addEventListener('change', calculateTotalDays);
    
    // File upload handler
    document.getElementById('lampiran').addEventListener('change', function() {
        const file = this.files[0];
        
        if (file) {
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Terlalu Besar',
                    text: 'Ukuran file maksimal 5MB'
                });
                this.value = '';
                return;
            }
            
            // Validate file type
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Tipe File Tidak Valid',
                    text: 'Hanya file PDF, JPG, dan PNG yang diperbolehkan'
                });
                this.value = '';
                return;
            }
            
            // Show preview
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('filePreview').style.display = 'block';
        }
    });
    
    // Clear file
    function clearFile() {
        document.getElementById('lampiran').value = '';
        document.getElementById('filePreview').style.display = 'none';
    }
    
    // Form validation before submit
    document.getElementById('leaveForm').addEventListener('submit', function(e) {
        const totalDays = calculateTotalDays();
        
        if (totalDays === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Pilih tanggal mulai dan selesai yang valid'
            });
            return false;
        }
        
        if (totalDays > leaveBalance) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Saldo Cuti Tidak Cukup',
                text: `Anda memerlukan ${totalDays} hari, tetapi sisa cuti Anda hanya ${leaveBalance} hari.`,
                showCancelButton: true,
                confirmButtonText: 'Lanjutkan (Cuti Tanpa Gaji)',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Change to unpaid leave
                    const leaveTypeSelect = document.getElementById('leave_type_id');
                    for (let i = 0; i < leaveTypeSelect.options.length; i++) {
                        if (leaveTypeSelect.options[i].text.includes('Tanpa Gaji')) {
                            leaveTypeSelect.selectedIndex = i;
                            this.submit();
                            break;
                        }
                    }
                }
            });
            return false;
        }
        
        const agreement = document.getElementById('agreement').checked;
        if (!agreement) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Persetujuan Diperlukan',
                text: 'Harap centang persetujuan sebelum mengajukan cuti'
            });
            return false;
        }
        
        // Show loading
        Swal.fire({
            title: 'Memproses...',
            text: 'Sedang mengajukan cuti Anda',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    });
</script>
@endpush
