<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\OrangTua;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrangTuaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', OrangTua::class);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'no_kk' => ['required', 'string', 'regex:/^[0-9]{16}$/'],
            'nik_ibu' => ['required', 'string', 'regex:/^[0-9]{16}$/', 'unique:orang_tua,nik_ibu'],
            'nama_ibu' => ['required', 'string', 'max:150'],
            'nik_ayah' => ['nullable', 'string', 'regex:/^[0-9]{16}$/'],
            'nama_ayah' => ['nullable', 'string', 'max:150'],
            'nomor_telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['required', 'string', 'max:1000'],
            'golongan_darah' => [
                'nullable',
                'string',
                Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'A', 'B', 'AB', 'O']),
            ],
            'buat_akun_login' => ['boolean'],
            'email' => [
                'nullable',
                'required_if:buat_akun_login,true',
                'email',
                'max:255',
                'unique:users,email',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'no_kk' => 'Nomor Kartu Keluarga (KK)',
            'nik_ibu' => 'NIK Ibu',
            'nama_ibu' => 'Nama Lengkap Ibu',
            'nik_ayah' => 'NIK Ayah',
            'nama_ayah' => 'Nama Lengkap Ayah',
            'nomor_telepon' => 'Nomor Telepon / WhatsApp',
            'alamat' => 'Alamat Domisili',
            'golongan_darah' => 'Golongan Darah',
            'buat_akun_login' => 'Buat Akun Portal Orang Tua',
            'email' => 'Alamat Email',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
            'unique' => ':attribute sudah terdaftar di sistem.',
            'regex' => ':attribute harus berupa 16 digit angka valid sesuai ketentuan Dukcapil.',
            'email' => 'Format :attribute tidak valid.',
            'in' => ':attribute yang dipilih tidak valid.',
            'required_if' => ':attribute wajib diisi jika ingin membuatkan akun portal.',
        ];
    }
}
