<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesShareTargets;
use App\Models\LearningItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLearningItemRequest extends FormRequest
{
    use ValidatesShareTargets;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isGuru() || $user->isAdmin());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'folder_id' => ['nullable', 'integer', $this->ownedFolderRule()],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', 'distinct', $this->shareableClassRule()],
            'type' => ['required', 'string', Rule::in(LearningItem::TYPES)],
            'title' => ['required', 'string', 'max:180'],
            'topic' => ['nullable', 'string', 'max:120'],
            'content' => ['nullable', 'string', 'max:5000'],
            'url' => ['nullable', 'required_if:type,link', 'url:http,https', 'max:2048'],
            'poll_options' => ['nullable', 'required_if:type,poll', 'array', 'min:2', 'max:10'],
            'poll_options.*' => ['required', 'string', 'max:200', 'distinct:ignore_case'],
            'attachment' => $this->attachmentRules(),
            'due_at' => ['nullable', 'date'],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_graded' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Rules for the uploaded file: an .h5p package for H5P items, a supported document or media file otherwise.
     *
     * @return array<int, mixed>
     */
    private function attachmentRules(): array
    {
        if ($this->input('type') === 'h5p') {
            return ['required', 'file', 'extensions:h5p', 'mimes:zip', 'max:'.LearningItem::ATTACHMENT_MAX_KILOBYTES];
        }

        return [
            'nullable',
            'file',
            'mimes:'.implode(',', LearningItem::ATTACHMENT_EXTENSIONS),
            'extensions:'.implode(',', LearningItem::ATTACHMENT_EXTENSIONS),
            'max:'.LearningItem::ATTACHMENT_MAX_KILOBYTES,
        ];
    }

    /**
     * Custom Indonesian validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $unsupported = $this->input('type') === 'h5p'
            ? 'Berkas harus berupa paket H5P berakhiran .h5p.'
            : 'Format berkas tidak didukung. Gunakan PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX, PNG, JPG, MP4, atau MP3.';

        return [
            'type.required' => 'Jenis konten wajib dipilih.',
            'title.required' => 'Judul wajib diisi.',
            'url.required_if' => 'Alamat tautan wajib diisi.',
            'url.url' => 'Alamat tautan harus diawali http:// atau https://.',
            'poll_options.required_if' => 'Polling membutuhkan pilihan jawaban.',
            'poll_options.min' => 'Polling membutuhkan minimal 2 pilihan jawaban.',
            'poll_options.max' => 'Polling maksimal memiliki 10 pilihan jawaban.',
            'poll_options.*.required' => 'Pilihan jawaban tidak boleh kosong.',
            'poll_options.*.distinct' => 'Pilihan jawaban tidak boleh sama.',
            'folder_id.exists' => 'Folder tujuan tidak ditemukan.',
            'class_ids.*.exists' => 'Anda hanya dapat membagikan ke kelas yang Anda ajar.',
            'attachment.required' => 'Pilih berkas paket H5P (.h5p).',
            'attachment.mimes' => $unsupported,
            'attachment.extensions' => $unsupported,
            'attachment.max' => 'Ukuran berkas maksimal 90 MB.',
            'attachment.uploaded' => 'Berkas gagal diunggah. Periksa ukuran berkas lalu coba lagi.',
        ];
    }
}
