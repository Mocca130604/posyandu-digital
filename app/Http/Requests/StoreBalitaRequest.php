<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\JenisKelamin;
use App\Models\Balita;
use App\Models\OrangTua;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBalitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', Balita::class);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $batasUsiaMaks = Carbon::now()->subMonths(60)->toDateString();

        return [
            // Pilihan orang tua: sudah ada (id) atau buat baru
            'orang_tua_id' => [
                'nullable',
                'required_without:nik_ibu',
                'exists:orang_tua,id',
            ],
            // Jika orang tua dibuat bersamaan
            'no_kk' => ['nullable', 'required_without:orang_tua_id', 'string', 'regex:/^[0-9]{16}$/'],
            'nik_ibu' => [
                'nullable',
                'required_without:orang_tua_id',
                'string',
                'regex:/^[0-9]{16}$/',
                'unique:orang_tua,nik_ibu',
            ],
            'nama_ibu' => ['nullable', 'required_without:orang_tua_id', 'string', 'max:150'],
            'nik_ayah' => ['nullable', 'string', 'regex:/^[0-9]{16}$/'],
            'nama_ayah' => ['nullable', 'string', 'max:150'],
            'alamat' => ['nullable', 'required_without:orang_tua_id', 'string', 'max:1000'],
            'golongan_darah' => [
                'nullable',
                'string',
                Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'A', 'B', 'AB', 'O']),
            ],

            // Data Balita
            'posyandu_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->user()?->isAdminDesa()),
                'exists:posyandu,id',
            ],
            'nik_anak' => [
                'nullable',
                'string',
                'regex:/^[0-9]{16}$/',
                'unique:balita,nik_anak',
            ],
            'nama_anak' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', Rule::enum(JenisKelamin::class)],
            'tanggal_lahir' => [
                'required',
                'date',
                'before_or_equal:today',
                "after_or_equal:{$batasUsiaMaks}",
            ],
            'berat_lahir_kg' => [
                'required',
                'numeric',
                'min:0.5',
                'max:30.0',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'panjang_lahir_cm' => [
                'required',
                'numeric',
                'min:35.0',
                'max:130.0',
                'regex:/^\d+(\.\d{1})?$/',
            ],
            'lingkar_kepala_lahir_cm' => [
                'required',
                'numeric',
                'min:25.0',
                'max:65.0',
                'regex:/^\d+(\.\d{1})?$/',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'orang_tua_id' => 'Data Orang Tua Terdaftar',
            'no_kk' => 'Nomor KK',
            'nik_ibu' => 'NIK Ibu',
            'nama_ibu' => 'Nama Ibu',
            'nik_ayah' => 'NIK Ayah',
            'nama_ayah' => 'Nama Ayah',
            'alamat' => 'Alamat Domisili',
            'golongan_darah' => 'Golongan Darah',
            'posyandu_id' => 'Posyandu Terdaftar',
            'nik_anak' => 'NIK Balita',
            'nama_anak' => 'Nama Lengkap Balita',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tanggal_lahir' => 'Tanggal Lahir',
            'berat_lahir_kg' => 'Berat Lahir (kg)',
            'panjang_lahir_cm' => 'Panjang Lahir (cm)',
            'lingkar_kepala_lahir_cm' => 'Lingkar Kepala Lahir (cm)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'required_without' => ':attribute wajib diisi apabila data orang tua belum dipilih.',
            'numeric' => ':attribute harus berupa angka.',
            'regex' => ':attribute tidak sesuai format yang ditentukan.',
            'nik_anak.regex' => 'NIK Balita harus terdiri dari tepat 16 digit angka.',
            'nik_ibu.regex' => 'NIK Ibu harus terdiri dari tepat 16 digit angka.',
            'no_kk.regex' => 'Nomor KK harus terdiri dari tepat 16 digit angka.',
            'unique' => ':attribute sudah terdaftar di sistem posyandu.',
            'before_or_equal' => ':attribute tidak boleh melebihi tanggal hari ini.',
            'after_or_equal' => ':attribute tidak boleh lebih dari 60 bulan yang lalu (batas usia pemantauan balita).',
            'min' => ':attribute minimal bernilai :min.',
            'max' => ':attribute maksimal bernilai :max.',
            'berat_lahir_kg.regex' => 'Berat lahir maksimal memiliki 2 angka di belakang koma.',
            'panjang_lahir_cm.regex' => 'Panjang lahir maksimal memiliki 1 angka di belakang koma.',
            'lingkar_kepala_lahir_cm.regex' => 'Lingkar kepala lahir maksimal memiliki 1 angka di belakang koma.',
        ];
    }
}
