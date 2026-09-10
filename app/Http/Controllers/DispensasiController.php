<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDispensasiRequest;
use App\Models\Dispensasi;
use App\Models\Pegawai;
use App\Models\User;
use App\Notifications\SuratDispensasiDiterbitkan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DispensasiController extends Controller
{
    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    private const MAKS_BARIS_HALAMAN_UTAMA = 12;
    private const URUTAN_WAKTU = ['T', 'TBO', 'TBI', 'CP'];

    private const POSISI_LEVEL_STAF = ['staf', 'asisten_manajer_bidang'];
    private const POSISI_LEVEL_MANAJER = ['manajer'];
    private const POSISI_LEVEL_SENIOR_MANAJER = ['senior_manajer_sekper', 'senior_manajer_bisnis', 'senior_manajer_keuangan_pelanggan'];

    public function create()
    {
        $pegawais = Pegawai::where('departemen_id', Auth::user()->departemen_id)
            ->aktif()
            ->orderBy('nama_pegawai')
            ->get();
        return view('dispensasi.create', compact('pegawais'));
    }

    public function store(StoreDispensasiRequest $request)
    {
        $data = $request->validated();
        $pegawai = Pegawai::findOrFail($data['pegawai_id']);
        $keteranganPerWaktu = collect($data['keterangan'] ?? []);

        $barisAda = Dispensasi::where('pegawai_id', $pegawai->id)
            ->where('tanggal_dispensasi', $data['tanggal_dispensasi'])
            ->whereIn('waktu_dispensasi', $data['waktu_dispensasi'])
            ->whereIn('status_pengajuan', ['disetujui', 'menunggu_persetujuan'])
            ->get(['waktu_dispensasi', 'status_pengajuan']);

        $waktuSudahDisetujui = $barisAda->where('status_pengajuan', 'disetujui')
            ->pluck('waktu_dispensasi')->unique()->values();

        if ($waktuSudahDisetujui->isNotEmpty()) {
            throw ValidationException::withMessages([
                'waktu_dispensasi' => sprintf(
                    '%s sudah memiliki dispensasi yang DISETUJUI untuk waktu %s pada tanggal %s. Pengajuan tidak bisa dikirim.',
                    $pegawai->nama_pegawai,
                    $waktuSudahDisetujui->implode(', '),
                    \Carbon\Carbon::parse($data['tanggal_dispensasi'])->translatedFormat('d F Y')
                ),
            ]);
        }

        $waktuSudahMenunggu = $barisAda->where('status_pengajuan', 'menunggu_persetujuan')
            ->pluck('waktu_dispensasi')->unique()->values();

        $waktuUntukDibuat = collect($data['waktu_dispensasi'])
            ->reject(fn ($w) => $waktuSudahMenunggu->contains($w))
            ->values();

        $buktiPerWaktu = [];
        foreach (($request->file('bukti_pendukung') ?? []) as $waktu => $file) {
            if ($file) {
                $buktiPerWaktu[$waktu] = $file->store('bukti-dispensasi', 'public');
            }
        }

        [$dispensasiBaru, $dispensasiDigabung] = DB::transaction(function () use ($data, $pegawai, $waktuUntukDibuat, $waktuSudahMenunggu, $keteranganPerWaktu, $buktiPerWaktu) {
            $digabung = [];
            if ($waktuSudahMenunggu->isNotEmpty()) {
                $barisMenunggu = Dispensasi::where('pegawai_id', $pegawai->id)
                    ->where('tanggal_dispensasi', $data['tanggal_dispensasi'])
                    ->whereIn('waktu_dispensasi', $waktuSudahMenunggu)
                    ->where('status_pengajuan', 'menunggu_persetujuan')
                    ->lockForUpdate()
                    ->get();

                foreach ($barisMenunggu as $lama) {
                    $ketBaru = trim((string) $keteranganPerWaktu->get($lama->waktu_dispensasi, ''));
                    $lama->keterangan = $this->gabungkanKeterangan($lama->keterangan, $ketBaru);

                    $buktiBaruUntukWaktu = $buktiPerWaktu[$lama->waktu_dispensasi] ?? null;
                    if (empty($lama->bukti_pendukung) && $buktiBaruUntukWaktu) {
                        $lama->bukti_pendukung = $buktiBaruUntukWaktu;
                    }

                    $lama->save();
                    $digabung[] = $lama;
                }
            }

            $baru = [];
            foreach ($waktuUntukDibuat as $waktu) {
                $nomor = Dispensasi::generateNomor();
                $baru[] = Dispensasi::create([
                    'nomor_dispensasi'    => $nomor,
                    'pegawai_id'          => $pegawai->id,
                    'departemen_id'       => $pegawai->departemen_id,
                    'subdepartemen_id'    => $pegawai->subdepartemen_id,
                    'admin_departemen_id' => Auth::id(),
                    'tanggal_pengajuan'   => now()->toDateString(),
                    'tanggal_dispensasi'  => $data['tanggal_dispensasi'],
                    'waktu_dispensasi'    => $waktu,
                    'keterangan'          => trim((string) $keteranganPerWaktu->get($waktu, '')),
                    'bukti_pendukung'     => $buktiPerWaktu[$waktu] ?? null,
                    'status_pengajuan'    => 'menunggu_persetujuan',
                ]);
            }

            return [$baru, $digabung];
        });

        foreach ($dispensasiBaru as $dispensasi) {
            $this->notifikasiPihakBerwenang($dispensasi);
        }

        $jumlahBaru = count($dispensasiBaru);
        $nomorBaruList = collect($dispensasiBaru)->pluck('nomor_dispensasi')->implode(', ');

        $pesan = $jumlahBaru > 0
            ? ($jumlahBaru > 1
                ? "{$jumlahBaru} pengajuan dispensasi berhasil dikirim ({$nomorBaruList}), menunggu persetujuan."
                : "Pengajuan dispensasi {$nomorBaruList} berhasil dikirim, menunggu persetujuan.")
            : 'Tidak ada pengajuan baru yang dibuat.';

        if ($waktuSudahMenunggu->isNotEmpty()) {
            $nomorGabungList = collect($dispensasiDigabung)->pluck('nomor_dispensasi')->implode(', ');
            $pesan .= sprintf(
                ' Waktu %s untuk %s pada tanggal %s digabung ke pengajuan sebelumnya yang masih menunggu persetujuan (%s); keterangan ditambahkan.',
                $waktuSudahMenunggu->implode(', '),
                $pegawai->nama_pegawai,
                \Carbon\Carbon::parse($data['tanggal_dispensasi'])->translatedFormat('d F Y'),
                $nomorGabungList
            );
        }

        return redirect()->route('dispensasi.index')->with('success', $pesan);
    }

    public function index(Request $request)
    {
        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');
        $belumSurat = $request->boolean('belum_surat');
        $departemenId = Auth::user()->departemen_id;

        $query = Dispensasi::where('departemen_id', $departemenId)
            ->with(['pegawai', 'subdepartemen', 'diprosesOleh']);
        if ($tahun) {
            $query->whereYear('tanggal_dispensasi', $tahun);
        }
        if ($bulan) {
            $query->whereMonth('tanggal_dispensasi', $bulan);
        }
        if ($belumSurat) {
            $query->belumDijadikanSurat();
        }

        $semuaBaris = $query
            ->orderByDesc('tanggal_pengajuan')
            ->orderByDesc('id')
            ->get();

        $kelompok = $semuaBaris
            ->groupBy(fn ($d) => $d->pegawai_id . '|' . $d->tanggal_dispensasi->format('Y-m-d'))
            ->map(function ($baris) {
                $acuan = $baris->first();
                $statusUnik = $baris->pluck('status_pengajuan')->unique();
                return (object) [
                    'acuan'            => $acuan,
                    'baris'            => $baris->sortBy(
                        fn ($d) => array_search($d->waktu_dispensasi, self::URUTAN_WAKTU)
                    )->values(),
                    'nomor_list'       => $baris->pluck('nomor_dispensasi')->implode(', '),
                    'statusSeragam'    => $statusUnik->count() === 1 ? $statusUnik->first() : null,
                    'tanggal_pengajuan' => $baris->max('tanggal_pengajuan'),
                    'idTertinggi'      => $baris->max('id'),
                    'keteranganSurat'  => $acuan->keteranganStatusSurat(),
                ];
            })
            ->sortBy([
                ['tanggal_pengajuan', 'desc'],
                ['idTertinggi', 'desc'],
            ])
            ->values();

        $perPage = 10;
        $halaman = $request->input('page', 1);
        $dispensasis = new LengthAwarePaginator(
            $kelompok->forPage($halaman, $perPage)->values(),
            $kelompok->count(),
            $perPage,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $belumDijadikanSuratCount = Dispensasi::where('departemen_id', $departemenId)
            ->belumDijadikanSurat()
            ->count();

        return view('dispensasi.index', [
            'dispensasis' => $dispensasis,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'belumSurat' => $belumSurat,
            'tahunTersedia' => $this->getTahunTersedia(),
            'namaBulan' => self::NAMA_BULAN,
            'belumDijadikanSuratCount' => $belumDijadikanSuratCount,
        ]);
    }

    public function show(Dispensasi $dispensasi)
    {
        abort_unless($dispensasi->departemen_id === Auth::user()->departemen_id, 403);
        $dispensasi->load(['pegawai', 'subdepartemen', 'adminDepartemen', 'diprosesOleh', 'dicetakOleh']);
        $kelompokTampil = Dispensasi::query()
            ->satuKelompokPegawaiTanggal($dispensasi)
            ->orderByRaw("FIELD(waktu_dispensasi, 'T','TBO','TBI','CP')")
            ->get();
        return view('dispensasi.show', compact('dispensasi', 'kelompokTampil'));
    }

    public function cetakForm(Dispensasi $dispensasi)
    {
        abort_unless($dispensasi->departemen_id === Auth::user()->departemen_id, 403);
        abort_unless($dispensasi->isDisetujui(), 403, 'Surat hanya bisa diterbitkan untuk dispensasi yang sudah disetujui.');
        abort_if($dispensasi->isSudahDicetak(), 403, 'Surat untuk pengajuan ini sudah pernah diterbitkan.');
        $dispensasi->load(['pegawai', 'departemen', 'subdepartemen', 'diprosesOleh']);
        $previewKelompok = Dispensasi::query()
            ->satuKelompokSurat($dispensasi)
            ->with('pegawai')
            ->get();
        return view('dispensasi.cetak-form', compact('dispensasi', 'previewKelompok'));
    }

    public function cetakStore(Request $request, Dispensasi $dispensasi)
    {
        abort_unless($dispensasi->departemen_id === Auth::user()->departemen_id, 403);
        abort_unless($dispensasi->isDisetujui(), 403, 'Surat hanya bisa diterbitkan untuk dispensasi yang sudah disetujui.');
        abort_if($dispensasi->isSudahDicetak(), 403, 'Surat untuk pengajuan ini sudah pernah diterbitkan.');
        $request->validate([
            'nomor_surat'   => ['required', 'string', 'max:100', Rule::unique('dispensasis', 'nomor_surat_dispensasi')],
            'tanggal_surat' => ['required', 'date'],
        ], [
            'nomor_surat.unique' => 'Nomor surat ini sudah dipakai untuk surat e-dispensasi lain.',
        ]);
        $direkturAdminKeu = User::role('direktur_administrasi_keuangan')->active()->first();
        $token = (string) Str::uuid();
        DB::transaction(function () use ($request, $dispensasi, $direkturAdminKeu, $token) {
            Dispensasi::query()
                ->satuKelompokSurat($dispensasi)
                ->update([
                    'nomor_surat_dispensasi'   => $request->input('nomor_surat'),
                    'tanggal_surat_dispensasi' => $request->input('tanggal_surat'),
                    'dicetak_oleh_id'          => Auth::id(),
                    'ditujukan_kepada_id'      => $direkturAdminKeu?->id,
                    'token_verifikasi'         => $token,
                    'dicetak_pada'             => now(),
                ]);
        });
        $this->beriTahuAdminSdm($dispensasi->fresh());
        return redirect()->route('dispensasi.index')
            ->with('success', "Surat e-dispensasi {$request->input('nomor_surat')} berhasil diterbitkan dan dikirimkan ke Admin SDM.");
    }

    public function unduhSurat(Dispensasi $dispensasi)
    {
        $user = Auth::user();
        if ($user->isAdminDepartemen()) {
            abort_unless($dispensasi->departemen_id === $user->departemen_id, 403);
        }
        abort_unless($dispensasi->isSudahDicetak(), 404, 'Surat e-dispensasi belum diterbitkan untuk pengajuan ini.');
        $dispensasi->load(['pegawai', 'departemen.divisi', 'subdepartemen', 'dicetakOleh', 'ditujukanKepada', 'diprosesOleh']);

        $kelompokDispensasi = Dispensasi::query()
            ->satuSurat($dispensasi->nomor_surat_dispensasi)
            ->with(['pegawai', 'subdepartemen', 'departemen'])
            ->orderBy('pegawai_id')
            ->orderByRaw("FIELD(waktu_dispensasi, 'T','TBO','TBI','CP')")
            ->get();

        $barisPegawai = $kelompokDispensasi
            ->groupBy('pegawai_id')
            ->map(function ($barisWaktu) {
                $acuan = $barisWaktu->first();
                return (object) [
                    'pegawai'       => $acuan->pegawai,
                    'subdepartemen' => $acuan->subdepartemen,
                    'departemen'    => $acuan->departemen,
                    'waktu'         => $barisWaktu->pluck('waktu_dispensasi')->unique()->values()->toArray(),
                    'keterangan'    => $barisWaktu->pluck('keterangan')->filter()->unique()->implode('; ') ?: '-',
                ];
            })
            ->values();

        $levelSet = $barisPegawai
            ->map(fn ($b) => $this->levelPosisi($b->pegawai->posisi ?? null))
            ->unique()
            ->values();

        $hanyaLevelManajer = $levelSet->count() === 1 && $levelSet->first() === 'manajer';
        $campuranManajerSeniorManajer = $levelSet->diff(['manajer', 'senior_manajer'])->isEmpty()
            && $levelSet->contains('manajer')
            && $levelSet->contains('senior_manajer');

        $subdeptIdUnik = $barisPegawai->pluck('subdepartemen.id')->unique();
        $subdepartemenSeragam = ($subdeptIdUnik->count() === 1 && $subdeptIdUnik->first() !== null)
            ? $barisPegawai->first()->subdepartemen
            : null;

        if ($hanyaLevelManajer || $campuranManajerSeniorManajer) {
            $tampilkanBarisSubdepartemen = false;
            $tampilkanKolomSubdept = false;
        } else {
            $tampilkanBarisSubdepartemen = $subdepartemenSeragam !== null;
            $tampilkanKolomSubdept = $subdeptIdUnik->count() > 1;
        }

        $tampilkanBarisDivisi = $campuranManajerSeniorManajer;

        $barisHalamanUtama = $barisPegawai->take(self::MAKS_BARIS_HALAMAN_UTAMA)->values();
        $barisLampiran = $barisPegawai->slice(self::MAKS_BARIS_HALAMAN_UTAMA)->values();
        $adaLampiran = $barisLampiran->isNotEmpty();

        $penyetuju = $dispensasi->diprosesOleh;

        $manajerSdmMengajukanSendiri = $dispensasi->departemen?->kode_departemen === 'SDM'
            && $penyetuju?->isDirekturAdministrasiKeuangan();

        $jabatanPenyetuju = $manajerSdmMengajukanSendiri
            ? 'Manajer SDM'
            : ($penyetuju?->jabatanLengkap() ?? '-');

        $labelInstansi = $manajerSdmMengajukanSendiri
            ? 'DEPARTEMEN SUMBER DAYA MANUSIA'
            : $dispensasi->labelInstansiPenyetuju();

        $jabatanTujuan = $manajerSdmMengajukanSendiri
            ? ($dispensasi->ditujukanKepada?->jabatanLengkap() ?? 'Direktur Administrasi dan Keuangan')
            : ($penyetuju?->isDirekturAdministrasiKeuangan()
                ? 'Manajer SDM'
                : ($dispensasi->ditujukanKepada?->jabatanLengkap() ?? 'Direktur Administrasi dan Keuangan'));

        $qrSvg = QrCode::size(140)->generate(
            route('verifikasi.surat', ['token' => $dispensasi->token_verifikasi])
        );

        $pdf = Pdf::loadView('dispensasi.e-dispen-pdf', [
            'dispensasi'                  => $dispensasi,
            'nomorSurat'                  => $dispensasi->nomor_surat_dispensasi,
            'tanggalSurat'                => $dispensasi->tanggal_surat_dispensasi,
            'penyetuju'                   => $penyetuju,
            'jabatanPenyetuju'            => $jabatanPenyetuju,
            'labelInstansi'               => $labelInstansi,
            'jabatanTujuan'               => $jabatanTujuan,
            'tampilkanBarisDivisi'        => $tampilkanBarisDivisi,
            'tampilkanBarisSubdepartemen' => $tampilkanBarisSubdepartemen,
            'subdepartemenSeragam'        => $subdepartemenSeragam,
            'tampilkanKolomSubdept'       => $tampilkanKolomSubdept,
            'adaDivisi'                   => false,
            'departemenSeragam'           => null,
            'tampilkanKolomDepartemen'    => false,
            'barisHalamanUtama'           => $barisHalamanUtama,
            'barisLampiran'               => $barisLampiran,
            'adaLampiran'                 => $adaLampiran,
            'totalPegawai'                => $barisPegawai->count(),
            'qrSvg'                       => $qrSvg,
        ])->setPaper('a4', 'portrait');

        $namaFile = str_replace('/', '_', $dispensasi->nomor_surat_dispensasi) . '.pdf';
        return $pdf->download($namaFile);
    }

    public function exportPdf(Request $request)
    {
        $departemenId = Auth::user()->departemen_id;
        $namaDepartemen = Auth::user()->departemen?->nama_departemen ?? 'Departemen';
        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');
        $query = Dispensasi::where('departemen_id', $departemenId)
            ->where('status_pengajuan', 'disetujui')
            ->with(['pegawai', 'subdepartemen', 'diprosesOleh']);
        if ($tahun) {
            $query->whereYear('tanggal_dispensasi', $tahun);
        }
        if ($bulan) {
            $query->whereMonth('tanggal_dispensasi', $bulan);
        }
        $dispensasis = $query->orderBy('tanggal_dispensasi')->get();
        $bagianNamaFile = [Str::slug($namaDepartemen)];
        if ($bulan && isset(self::NAMA_BULAN[$bulan])) {
            $bagianNamaFile[] = Str::slug(self::NAMA_BULAN[$bulan]);
        }
        if ($tahun) {
            $bagianNamaFile[] = $tahun;
        }
        $namaFile = implode('-', $bagianNamaFile) . '.pdf';
        $pdf = Pdf::loadView('dispensasi.export-pdf', [
            'dispensasis' => $dispensasis,
            'namaDepartemen' => $namaDepartemen,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ])->setPaper('a4', 'landscape');
        return $pdf->download($namaFile);
    }

    private function levelPosisi(?string $posisi): string
    {
        if (in_array($posisi, self::POSISI_LEVEL_STAF, true)) {
            return 'staf';
        }
        if (in_array($posisi, self::POSISI_LEVEL_MANAJER, true)) {
            return 'manajer';
        }
        if (in_array($posisi, self::POSISI_LEVEL_SENIOR_MANAJER, true)) {
            return 'senior_manajer';
        }
        return 'lainnya';
    }

    private function getTahunTersedia(): array
    {
        $tahun = Dispensasi::where('departemen_id', Auth::user()->departemen_id)
            ->selectRaw('DISTINCT YEAR(tanggal_dispensasi) as tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->map(fn ($t) => (int) $t)
            ->toArray();
        $tahunSekarang = now()->year;
        if (! in_array($tahunSekarang, $tahun, true)) {
            $tahun[] = $tahunSekarang;
            rsort($tahun);
        }
        return $tahun;
    }

    private function notifikasiPihakBerwenang(Dispensasi $dispensasi): void
    {
        $dispensasi->pemberiKeputusan()?->notify(new \App\Notifications\DispensasiDiajukan($dispensasi));
    }

    private function beriTahuAdminSdm(Dispensasi $dispensasi): void
    {
        User::role('admin_sdm')->active()->get()
            ->each(fn (User $adminSdm) => $adminSdm->notify(new SuratDispensasiDiterbitkan($dispensasi)));
    }

    private function gabungkanKeterangan(?string $lama, string $baru): string
    {
        $lama = trim((string) $lama);
        $baru = trim($baru);

        $segmen = array_values(array_filter(
            array_map('trim', explode(';', $lama)),
            fn ($s) => $s !== ''
        ));

        if ($baru !== '') {
            $sudahAda = collect($segmen)->contains(
                fn ($s) => mb_strtolower($s) === mb_strtolower($baru)
            );
            if (! $sudahAda) {
                $segmen[] = $baru;
            }
        }

        $segmenUnik = collect($segmen)
            ->unique(fn ($s) => mb_strtolower($s))
            ->values()
            ->all();

        return implode('; ', $segmenUnik);
    }
}