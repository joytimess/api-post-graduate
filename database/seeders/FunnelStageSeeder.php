<?php

namespace Database\Seeders;

use App\Models\FunnelStage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FunnelStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            [
                'name'  => 'Informasi & Ketertarikan',
                'order' => 1,
                'description' => 'Calon mahasiswa mengetahui dan tertarik dengan program studi yang ditawarkan melalui berbagai media.',
            ],
            [
                'name'  => 'Pendaftaran Online',
                'order' => 2,
                'description' => 'Calon mahasiswa mengisi formulir pendaftaran dan membuat akun di sistem.',
            ],
            [
                'name'  => 'Kelengkapan Berkas',
                'order' => 3,
                'description' => 'Calon mahasiswa mengumpulkan dan mengunggah dokumen persyaratan seperti ijazah, transkrip, dan surat rekomendasi.',
            ],
            [
                'name'  => 'Seleksi Administrasi',
                'order' => 4,
                'description' => 'Tim akademik memverifikasi kelengkapan dan keabsahan berkas yang diunggah calon mahasiswa.',
            ],
            [
                'name'  => 'Tes Masuk',
                'order' => 5,
                'description' => 'Calon mahasiswa mengikuti tes tulis, tes potensi akademik, atau tes bahasa sesuai persyaratan program.',
            ],
            [
                'name'  => 'Wawancara',
                'order' => 6,
                'description' => 'Calon mahasiswa mengikuti sesi wawancara dengan dosen atau komite penerimaan program studi.',
            ],
            [
                'name'  => 'Pengumuman Hasil',
                'order' => 7,
                'description' => 'Hasil seleksi diumumkan — calon mahasiswa dinyatakan diterima, cadangan, atau tidak diterima.',
            ],
            [
                'name'  => 'Registrasi & Pembayaran',
                'order' => 8,
                'description' => 'Calon mahasiswa yang diterima melakukan registrasi ulang dan membayar biaya pendidikan.',
            ],
            [
                'name'  => 'Aktif Kuliah',
                'order' => 9,
                'description' => 'Mahasiswa resmi aktif dan mengikuti perkuliahan semester pertama.',
            ],
        ];

        foreach ($stages as $stage) {
            FunnelStage::create($stage);
        }
    }
}
