<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\JenisVaksin;
use App\Models\Balita;
use App\Models\ImunisasiBalita;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImunisasiRequest extends FormRequest
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

        return $user->can('create', [ImunisasiBalita::class, $this->balitaModel]);
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
                        $fail('Balita ini terdaftar di posyandu lain dan bukan binaan Anda.');
                    }
                },
            ],
            'jenis_vaksin' => [
                'required',
                Rule::enum(JenisVaksin::class),
                Rule::unique('imunisasi_balita', 'jenis_vaksin')
                    ->where('balita_id', $this->input('balita_id')),
            ],
            'tanggal_imunisasi' => [
                'required',
                'date',
                'before_or_equal:today',
                function (string $attribute, mixed $value, Closure $fail) use ($balita): void {
                    if ($balita === null || empty($value)) {
                        return;
                    }

                    $tglImunisasi = Carbon::parse((string) $value);
                    $tglLahir = Carbon::parse($balita->tanggal_lahir);

                    if ($tglImunisasi->lt($tglLahir)) {
                        $fail("Tanggal imunisasi ({$tglImunisasi->format('d/m/Y')}) tidak boleh mendahului tanggal lahir balita ({$tglLahir->format('d/m/Y')}).");
                    }
                },
            ],
            'petugas_kesehatan' => ['required', 'string', 'max:150'],
            'batch_vaksin' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'balita_id' => 'Data Balita',
            'jenis_vaksin' => 'Jenis Vaksin / Imunisasi',
            'tanggal_imunisasi' => 'Tanggal Pemberian Imunisasi',
            'petugas_kesehatan' => 'Nama Tenaga Kesehatan / Bidan',
            'batch_vaksin' => 'Nomor Batch Vaksin',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'before_or_equal' => ':attribute tidak boleh melebihi tanggal hari ini.',
            'jenis_vaksin.unique' => 'Jenis imunisasi ini sudah pernah tercatat untuk balita yang bersangkutan.',
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
        ];
    }
}
