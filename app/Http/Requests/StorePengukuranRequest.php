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

class StorePengukuranRequest extends FormRequest
{
    /**
     * Balita yang akan diukur.
     */
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

                    // Scoping posyandu ketat
                    if (!$user->isAdminDesa() && $balita->posyandu_id !== $user->posyandu_id) {
                        $fail('Balita ini terdaftar di posyandu lain dan tidak berada dalam wewenang Anda.');
                    }
                },
            ],
            'tanggal_kunjungan' => [
                'required',
                'date',
                'before_or_equal:today',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $balitaId = $this->input('balita_id');
                    if (!$balitaId || empty($value)) {
                        return;
                    }

                    $tgl = Carbon::parse((string) $value)->format('Y-m-d');
                    $exists = PengukuranBalita::where('balita_id', $balitaId)
                        ->where(function ($query) use ($tgl) {
                            $query->whereDate('tanggal_kunjungan', $tgl)
                                ->orWhere('tanggal_kunjungan', 'like', $tgl . '%');
                        })
                        ->exists();

                    if ($exists) {
                        $fail('Data pengukuran untuk balita ini pada tanggal yang sama sudah tercatat.');
                    }
                },
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
                        $fail("Usia balita pada tanggal kunjungan ({$diffMonths} bulan) melebihi batas pemantauan maksimal 60 bulan.");
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
            'vit_a_diberikan' => ['boolean'],
            'obat_cacing_diberikan' => ['boolean'],
            'catatan_kader' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'balita_id' => 'Data Balita',
            'tanggal_kunjungan' => 'Tanggal Kunjungan Posyandu',
            'berat_badan_kg' => 'Berat Badan (kg)',
            'tinggi_badan_cm' => 'Tinggi / Panjang Badan (cm)',
            'cara_ukur_tinggi' => 'Posisi / Cara Ukur Tinggi',
            'lingkar_lengan_atas_cm' => 'Lingkar Lengan Atas (LiLA) (cm)',
            'lingkar_kepala_cm' => 'Lingkar Kepala (cm)',
            'vit_a_diberikan' => 'Pemberian Vitamin A',
            'obat_cacing_diberikan' => 'Pemberian Obat Cacing',
            'catatan_kader' => 'Catatan Tambahan Kader',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'numeric' => ':attribute harus berupa angka desimal yang valid.',
            'min' => ':attribute minimal bernilai :min.',
            'max' => ':attribute maksimal bernilai :max.',
            'before_or_equal' => ':attribute tidak boleh berada di masa depan.',
            'tanggal_kunjungan.unique' => 'Pengukuran untuk balita ini pada tanggal yang sama sudah pernah dicatat.',
            'berat_badan_kg.regex' => 'Berat badan maksimal memiliki 2 angka di belakang koma (contoh: 8.45).',
            'tinggi_badan_cm.regex' => 'Tinggi/panjang badan maksimal memiliki 1 angka di belakang koma (contoh: 72.5).',
            'lingkar_lengan_atas_cm.regex' => 'LiLA maksimal memiliki 1 angka di belakang koma.',
            'lingkar_kepala_cm.regex' => 'Lingkar kepala maksimal memiliki 1 angka di belakang koma.',
        ];
    }
}
