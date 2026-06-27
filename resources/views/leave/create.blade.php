@extends('layouts.app')

@section('title', 'Ajukan Cuti Baru')

@section('content')
<!-- Page Header -->
<section class="flex flex-col md:flex-row md:items-center justify-between gap-md">
    <div>
        <h2 class="text-headline-lg font-headline-lg text-on-background">Ajukan Cuti Baru</h2>
        <p class="text-body-md font-body-md text-secondary">Isi form di bawah untuk mengajukan cuti</p>
    </div>
    <a href="{{ route('leave.index') }}" class="flex items-center gap-sm px-lg py-md border border-outline-variant rounded-xl font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-low transition-all no-underline">
        <span class="material-symbols-outlined">arrow_back</span>
        Kembali
    </a>
</section>

<div class="grid grid-cols-12 gap-lg">
    <!-- Left: Form -->
    <div class="col-span-12 lg:col-span-8">
        <section class="glass-card rounded-xl border border-outline-variant shadow-sm overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant bg-surface-container-low/50">
                <div class="flex items-center gap-md">
                    <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-[20px]">note_add</span>
                    </div>
                    <h4 class="text-label-md font-label-md text-on-background">Form Pengajuan Cuti</h4>
                </div>
            </div>
            <div class="p-lg">
                <form action="{{ route('leave.store') }}" method="POST" enctype="multipart/form-data" id="leaveForm" class="space-y-lg">
                    @csrf

                    @if ($errors->any())
                        <div class="p-md mb-lg bg-error/10 border border-error/20 rounded-xl">
                            <ul class="list-disc list-inside text-error text-body-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif


                    <!-- Leave Type -->
                    <div>
                        <label for="leave_type_id" class="block text-label-md font-label-md text-on-surface mb-sm">
                            <span class="material-symbols-outlined text-[18px] text-primary align-middle mr-xs">sell</span>
                            Jenis Cuti <span class="text-error">*</span>
                        </label>
                        <select id="leave_type_id" name="leave_type_id"
                                class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm"
                                required>
                            <option value="">-- Pilih Jenis Cuti --</option>
                            @foreach($leaveTypes ?? [] as $type)
                            <option value="{{ $type['id'] }}"
                                    data-max-days="{{ $type['max_hari_per_pengajuan'] }}"
                                    data-requires-doc="{{ $type['butuh_dokumen'] ? 'true' : 'false' }}">
                                {{ $type['nama'] }} (Max: {{ $type['max_hari_per_pengajuan'] }} hari)
                            </option>
                            @endforeach
                        </select>
                        <p class="mt-xs text-label-sm font-label-sm text-secondary">Pilih jenis cuti yang sesuai dengan kebutuhan Anda</p>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                        <div>
                            <label for="tanggal_mulai" class="block text-label-md font-label-md text-on-surface mb-sm">
                                <span class="material-symbols-outlined text-[18px] text-green-600 align-middle mr-xs">calendar_month</span>
                                Tanggal Mulai <span class="text-error">*</span>
                            </label>
                            <input type="date" id="tanggal_mulai" name="tanggal_mulai"
                                   min="{{ date('Y-m-d') }}"
                                   class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm"
                                   required>
                        </div>
                        <div>
                            <label for="tanggal_selesai" class="block text-label-md font-label-md text-on-surface mb-sm">
                                <span class="material-symbols-outlined text-[18px] text-error align-middle mr-xs">event_available</span>
                                Tanggal Selesai <span class="text-error">*</span>
                            </label>
                            <input type="date" id="tanggal_selesai" name="tanggal_selesai"
                                   min="{{ date('Y-m-d') }}"
                                   class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm"
                                   required>
                        </div>
                    </div>

                    <!-- Total Days (Auto calculated) -->
                    <div id="totalDaysAlert" class="hidden">
                        <div class="flex items-center gap-md p-md bg-primary/5 border border-primary/20 rounded-xl">
                            <span class="material-symbols-outlined text-primary">info</span>
                            <span class="text-body-sm font-body-sm text-on-surface">
                                Total hari cuti: <strong id="totalDaysText" class="text-primary">0 hari</strong>
                            </span>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <label for="alasan" class="block text-label-md font-label-md text-on-surface mb-sm">
                            <span class="material-symbols-outlined text-[18px] text-orange-500 align-middle mr-xs">chat</span>
                            Alasan Cuti <span class="text-error">*</span>
                        </label>
                        <textarea id="alasan" name="alasan" rows="4"
                                  placeholder="Jelaskan alasan Anda mengajukan cuti..."
                                  class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm resize-none"></textarea>
                        <p class="mt-xs text-label-sm font-label-sm text-secondary">Minimal 20 karakter. Jelaskan dengan jelas alasan pengajuan cuti.</p>
                    </div>

                    <!-- Document Upload -->
                    <div>
                        <label for="lampiran" class="block text-label-md font-label-md text-on-surface mb-sm">
                            <span class="material-symbols-outlined text-[18px] text-secondary align-middle mr-xs">attach_file</span>
                            Lampiran Dokumen
                            <span id="requiredBadge" class="hidden px-2 py-0.5 bg-error/10 text-error text-label-sm font-label-sm rounded-full ml-sm">Wajib</span>
                        </label>
                        <div class="relative">
                            <input type="file" id="lampiran" name="lampiran"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-label-sm file:font-label-sm file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer">
                        </div>
                        <p class="mt-xs text-label-sm font-label-sm text-secondary">
                            Format: PDF, JPG, PNG. Maksimal 5MB.
                            <span id="docRequirementText">Lampirkan dokumen pendukung jika diperlukan.</span>
                        </p>

                        <!-- File Preview -->
                        <div id="filePreview" class="hidden mt-sm">
                            <div class="flex items-center justify-between p-md bg-green-500/5 border border-green-500/20 rounded-xl">
                                <div class="flex items-center gap-sm">
                                    <span class="material-symbols-outlined text-green-600 text-[20px]">task</span>
                                    <span class="text-body-sm font-body-sm text-on-surface">File: <strong id="fileName"></strong></span>
                                </div>
                                <button type="button" onclick="clearFile()" class="w-8 h-8 rounded-lg flex items-center justify-center text-error hover:bg-error-container/20 transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Leave Balance Warning -->
                    <div id="balanceWarning" class="hidden">
                        <div class="flex items-center gap-md p-md bg-orange-500/5 border border-orange-500/20 rounded-xl">
                            <span class="material-symbols-outlined text-orange-500">warning</span>
                            <span class="text-body-sm font-body-sm text-on-surface">
                                <strong>Perhatian:</strong> Sisa cuti Anda: <strong id="remainingLeave" class="text-orange-600">{{ $leaveBalance->sisa ?? 0 }}</strong> hari.
                                Pastikan cukup untuk pengajuan ini.
                            </span>
                        </div>
                    </div>

                    <!-- Terms Agreement -->
                    <label class="flex items-start gap-md p-md bg-surface-container-low/50 rounded-xl cursor-pointer hover:bg-surface-container-low transition-colors">
                        <input type="checkbox" id="agreement" name="agreement"
                               class="mt-1 w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary/20" required>
                        <span class="text-body-sm font-body-sm text-on-surface">
                            Saya menyatakan bahwa data yang saya berikan adalah benar dan saya bersedia menerima konsekuensi jika terbukti palsu.
                        </span>
                    </label>

                    <!-- Submit Buttons -->
                    <div class="flex items-center gap-md pt-md border-t border-outline-variant">
                        <button type="submit" class="flex items-center gap-sm bg-primary text-on-primary px-lg py-md rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-95">
                            <span class="material-symbols-outlined">send</span>
                            Ajukan Cuti
                        </button>
                        <a href="{{ route('leave.index') }}" class="flex items-center gap-sm px-lg py-md border border-outline-variant rounded-xl font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-low transition-all no-underline">
                            <span class="material-symbols-outlined">cancel</span>
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <!-- Right: Sidebar -->
    <div class="col-span-12 lg:col-span-4 space-y-lg">
        <!-- Saldo Cuti -->
        <section class="glass-card rounded-xl border border-outline-variant shadow-sm overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant bg-surface-container-low/50">
                <div class="flex items-center gap-md">
                    <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                    </div>
                    <h4 class="text-label-md font-label-md text-on-background">Saldo Cuti</h4>
                </div>
            </div>
            <div class="p-lg space-y-sm">
                <div class="flex items-center justify-between py-md border-b border-outline-variant/50">
                    <span class="text-body-sm text-on-surface">Total Jatah</span>
                    <span class="text-label-md font-label-md text-on-background">{{ $leaveBalance->total_jatah ?? 12 }} hari</span>
                </div>
                <div class="flex items-center justify-between py-md border-b border-outline-variant/50">
                    <span class="text-body-sm text-on-surface">Sudah Digunakan</span>
                    <span class="text-label-md font-label-md text-error">{{ $leaveBalance->terpakai ?? 0 }} hari</span>
                </div>
                <div class="flex items-center justify-between py-md">
                    <span class="text-body-sm text-on-surface">Sisa Tersedia</span>
                    <span class="text-label-md font-label-md text-green-600 font-bold">{{ $leaveBalance->sisa ?? 0 }} hari</span>
                </div>
                <div class="mt-sm h-2 bg-surface-container rounded-full overflow-hidden">
                    @php
                        $total = $leaveBalance->total_jatah ?? 12;
                        $sisa = $leaveBalance->sisa ?? 0;
                        $pct = $total > 0 ? round(($sisa / $total) * 100) : 0;
                    @endphp
                    <div class="h-full bg-gradient-to-r from-primary to-primary-container rounded-full" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        </section>

        <!-- Tips -->
        <section class="glass-card rounded-xl border border-outline-variant shadow-sm overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant bg-surface-container-low/50">
                <div class="flex items-center gap-md">
                    <div class="w-8 h-8 bg-primary-fixed rounded-lg flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-[20px]">lightbulb</span>
                    </div>
                    <h4 class="text-label-md font-label-md text-on-background">Tips Pengajuan</h4>
                </div>
            </div>
            <div class="p-lg space-y-md">
                <div class="flex items-start gap-md">
                    <span class="material-symbols-outlined text-primary mt-xs text-[18px]">info</span>
                    <p class="text-body-sm text-on-surface">Ajukan cuti minimal <strong>3 hari sebelum</strong> tanggal mulai</p>
                </div>
                <div class="flex items-start gap-md">
                    <span class="material-symbols-outlined text-green-600 mt-xs text-[18px]">check_circle</span>
                    <p class="text-body-sm text-on-surface">Pastikan saldo cuti Anda mencukupi</p>
                </div>
                <div class="flex items-start gap-md">
                    <span class="material-symbols-outlined text-orange-600 mt-xs text-[18px]">description</span>
                    <p class="text-body-sm text-on-surface">Upload dokumen pendukung untuk cuti sakit</p>
                </div>
                <div class="flex items-start gap-md">
                    <span class="material-symbols-outlined text-secondary mt-xs text-[18px]">chat</span>
                    <p class="text-body-sm text-on-surface">Isi alasan dengan jelas untuk mempercepat approval</p>
                </div>
                <div class="flex items-start gap-md">
                    <span class="material-symbols-outlined text-secondary mt-xs text-[18px]">mail</span>
                    <p class="text-body-sm text-on-surface">Cek email berkala untuk notifikasi status</p>
                </div>
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
    const leaveBalance = {{ $leaveBalance->sisa ?? 0 }};

    function calculateTotalDays() {
        const startDate = document.getElementById('tanggal_mulai').value;
        const endDate = document.getElementById('tanggal_selesai').value;

        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

            document.getElementById('totalDaysText').textContent = diffDays + ' hari';
            document.getElementById('totalDaysAlert').classList.remove('hidden');

            if (diffDays > leaveBalance) {
                document.getElementById('balanceWarning').classList.remove('hidden');
            } else {
                document.getElementById('balanceWarning').classList.add('hidden');
            }

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

    document.getElementById('leave_type_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const requiresDoc = selectedOption.getAttribute('data-requires-doc') === 'true';

        const lampiranInput = document.getElementById('lampiran');
        const requiredBadge = document.getElementById('requiredBadge');
        const docRequirementText = document.getElementById('docRequirementText');

        if (requiresDoc) {
            lampiranInput.required = true;
            requiredBadge.classList.remove('hidden');
            docRequirementText.textContent = 'Dokumen pendukung WAJIB untuk jenis cuti ini.';
        } else {
            lampiranInput.required = false;
            requiredBadge.classList.add('hidden');
            docRequirementText.textContent = 'Lampirkan dokumen pendukung jika diperlukan.';
        }

        calculateTotalDays();
    });

    document.getElementById('tanggal_mulai').addEventListener('change', function() {
        document.getElementById('tanggal_selesai').min = this.value;
        calculateTotalDays();
    });

    document.getElementById('tanggal_selesai').addEventListener('change', calculateTotalDays);

    document.getElementById('lampiran').addEventListener('change', function() {
        const file = this.files[0];

        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({ icon: 'error', title: 'File Terlalu Besar', text: 'Ukuran file maksimal 5MB' });
                this.value = '';
                return;
            }

            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({ icon: 'error', title: 'Tipe File Tidak Valid', text: 'Hanya file PDF, JPG, dan PNG yang diperbolehkan' });
                this.value = '';
                return;
            }

            document.getElementById('fileName').textContent = file.name;
            document.getElementById('filePreview').classList.remove('hidden');
        }
    });

    function clearFile() {
        document.getElementById('lampiran').value = '';
        document.getElementById('filePreview').classList.add('hidden');
    }

    document.getElementById('leaveForm').addEventListener('submit', function(e) {
        const totalDays = calculateTotalDays();

        if (totalDays === 0) {
            e.preventDefault();
            Swal.fire({ icon: 'error', title: 'Error', text: 'Pilih tanggal mulai dan selesai yang valid' });
            return false;
        }

        const alasan = document.getElementById('alasan').value.trim();
        if (alasan.length === 0) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Alasan cuti wajib diisi.' });
            return false;
        }
        if (alasan.length < 20) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Alasan pengajuan cuti minimal 20 karakter.' });
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
            Swal.fire({ icon: 'warning', title: 'Persetujuan Diperlukan', text: 'Harap centang persetujuan sebelum mengajukan cuti' });
            return false;
        }

        Swal.fire({
            title: 'Memproses...',
            text: 'Sedang mengajukan cuti Anda',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    });
</script>
@endpush
@endsection
 