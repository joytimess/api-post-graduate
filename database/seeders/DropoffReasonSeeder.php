<?php

namespace Database\Seeders;

use App\Models\DropoffReason;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DropoffReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reasons = [
            // Academic
            [
                'label'       => 'Tidak memenuhi syarat IPK minimum',
                'category'    => 'academic',
                'description' => 'IPK S1 calon mahasiswa di bawah batas minimum yang disyaratkan program studi.',
            ],
            [
                'label'       => 'Tidak lulus tes potensi akademik',
                'category'    => 'academic',
                'description' => 'Nilai TPA atau tes masuk tidak mencapai passing grade yang ditetapkan.',
            ],
            [
                'label'       => 'Tidak lulus tes bahasa',
                'category'    => 'academic',
                'description' => 'Nilai TOEFL/IELTS atau tes bahasa Indonesia tidak memenuhi syarat minimum.',
            ],
            [
                'label'       => 'Tidak lulus wawancara',
                'category'    => 'academic',
                'description' => 'Calon mahasiswa tidak lolos tahap wawancara dengan komite penerimaan.',
            ],
            [
                'label'       => 'Latar belakang pendidikan tidak linier',
                'category'    => 'academic',
                'description' => 'Program studi asal tidak relevan dengan program S2/S3 yang dituju.',
            ],

            // Financial
            [
                'label'       => 'Tidak sanggup membayar biaya pendidikan',
                'category'    => 'financial',
                'description' => 'Calon mahasiswa mengundurkan diri karena kendala biaya kuliah.',
            ],
            [
                'label'       => 'Beasiswa tidak disetujui',
                'category'    => 'financial',
                'description' => 'Pengajuan beasiswa yang diandalkan calon mahasiswa ditolak.',
            ],
            [
                'label'       => 'Biaya hidup terlalu tinggi',
                'category'    => 'financial',
                'description' => 'Calon mahasiswa dari luar kota tidak sanggup menanggung biaya hidup.',
            ],

            // Personal
            [
                'label'       => 'Diterima di universitas lain',
                'category'    => 'personal',
                'description' => 'Calon mahasiswa memilih mendaftar dan diterima di perguruan tinggi lain.',
            ],
            [
                'label'       => 'Kendala kesehatan',
                'category'    => 'personal',
                'description' => 'Kondisi kesehatan calon mahasiswa tidak memungkinkan untuk melanjutkan proses pendaftaran.',
            ],
            [
                'label'       => 'Alasan keluarga',
                'category'    => 'personal',
                'description' => 'Calon mahasiswa mengundurkan diri karena urusan atau tanggung jawab keluarga.',
            ],
            [
                'label'       => 'Pindah domisili ke luar kota',
                'category'    => 'personal',
                'description' => 'Calon mahasiswa berpindah tempat tinggal sehingga tidak memungkinkan kuliah.',
            ],

            // Administrative
            [
                'label'       => 'Berkas tidak lengkap',
                'category'    => 'administrative',
                'description' => 'Calon mahasiswa tidak melengkapi dokumen persyaratan hingga batas waktu yang ditentukan.',
            ],
            [
                'label'       => 'Dokumen tidak valid atau palsu',
                'category'    => 'administrative',
                'description' => 'Berkas yang diunggah tidak dapat diverifikasi atau terindikasi tidak asli.',
            ],
            [
                'label'       => 'Melewati batas waktu pendaftaran',
                'category'    => 'administrative',
                'description' => 'Calon mahasiswa tidak menyelesaikan proses pendaftaran sebelum deadline.',
            ],
            [
                'label'       => 'Tidak melakukan registrasi ulang',
                'category'    => 'administrative',
                'description' => 'Calon mahasiswa yang dinyatakan diterima tidak melakukan registrasi ulang tepat waktu.',
            ],

            // Unknown
            [
                'label'       => 'Mengundurkan diri tanpa keterangan',
                'category'    => 'unknown',
                'description' => 'Calon mahasiswa berhenti di tengah proses tanpa memberikan alasan yang jelas.',
            ],
            [
                'label'       => 'Tidak dapat dihubungi',
                'category'    => 'unknown',
                'description' => 'Calon mahasiswa tidak merespons komunikasi dari pihak universitas.',
            ],
        ];

        foreach ($reasons as $reason) {
            DropoffReason::create($reason);
        }
    }
}
