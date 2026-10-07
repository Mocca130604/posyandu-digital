<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CaraUkurTinggi;
use App\Models\Balita;
use App\Models\PengukuranBalita;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewPengukuranRequest extends FormRequest
{
    protected ?Balita $balitaModel = null;

    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $balitaId = $this->input('balita_id');
        if (!is_numeric($balitaId)) {
            return false;
        }

        $this->balitaModel = Balita::find((int) $balitaId);
        if ($this->balitaModel === null) {
            return false;
        }

        return $user->can('create', [PengukuranBalita::class, $this->balitaModel]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $balita = $this->balitaModel;

        return [
            'balita_id' => [
                'required',
                'integer',
                'exists:balita,id',
                function (string $attribute, mixed $value, Closure $fail) use ($balita): void {
                    $user = $this->user();
                    if ($user === null || $balita === null) {
                        return;
                    }

                    if (!$user->isAdminDesa() && $balita->posyandu_id !== $user->posyandu_id) {
                        $fail('Balita ini terdaftar di posyandu lain dan tidak berada dalam wewenang Anda.');
                    }
                },
            ],
            'tanggal_kunjungan' => [
                'required',
                'date',
                'before_or_equal:today',
                function (string $attribute, mixed $value, Closure $fail) use ($balita): void {
                    if ($balita === null || empty($value)) {
                        return;
                    }

                    $tglKunjungan = Carbon::parse((string) $value);
                    $tglLahir = Carbon::parse($balita->tanggal_lahir);

                    if ($tglKunjungan->lt($tglLahir)) {
                        $fail("Tanggal kunjungan ({$tglKunjungan->format('d/m/Y')}) tidak boleh mendahului tanggal lahir balita ({$tglLahir->format('d/m/Y')}).");
                    }

                    $diffMonths = (int) $tglLahir->diffInMonths($tglKunjungan, false);
                    if ($diffMonths > 60) {
                        $fail("Usia balita pada tanggal kunjungan ({$diffMonths} bulan) melebihi batas pemantauan 60 bulan.");
                    }
                },
            ],
            'berat_badan_kg' => [
                'required',
                'numeric',
                'min:0.5',
                'max:30.0',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'tinggi_badan_cm' => [
                'required',
                'numeric',
                'min:35.0',
                'max:130.0',
                'regex:/^\d+(\.\d{1})?$/',
            ],
            'cara_ukur_tinggi' => ['required', Rule::enum(CaraUkurTinggi::class)],
            'lingkar_lengan_atas_cm' => [
                'nullable',
                'numeric',
                'min:5.0',
                'max:30.0',
                'regex:/^\d+(\.\d{1})?$/',
            ],
            'lingkar_kepala_cm' => [
                'nullable',
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
            'balita_id' => 'Data Balita',
            'tanggal_kunjungan' => 'Tanggal Kunjungan',
            'berat_badan_kg' => 'Berat Badan (kg)',
            'tinggi_badan_cm' => 'Tinggi / Panjang Badan (cm)',
            'cara_ukur_tinggi' => 'Posisi / Cara Ukur Tinggi',
            'lingkar_lengan_atas_cm' => 'Lingkar Lengan Atas (LiLA)',
            'lingkar_kepala_cm' => 'Lingkar Kepala',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'numeric' => ':attribute harus berupa angka yang valid.',
            'min' => ':attribute minimal bernilai :min.',
            'max' => ':attribute maksimal bernilai :max.',
            'before_or_equal' => ':attribute tidak boleh berada di masa depan.',
            'berat_badan_kg.regex' => 'Berat badan maksimal 2 desimal.',
            'tinggi_badan_cm.regex' => 'Tinggi badan maksimal 1 desimal.',
        ];
    }
}
