<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Indonesian messages for the validation rules Falcon uses. Rules that are
    | not listed here fall back to the English messages (APP_FALLBACK_LOCALE).
    |
    */

    'accepted' => ':Attribute harus disetujui.',
    'after' => ':Attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => ':Attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha_dash' => ':Attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'array' => ':Attribute harus berupa daftar.',
    'before' => ':Attribute harus berupa tanggal sebelum :date.',
    'before_or_equal' => ':Attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':Attribute harus berisi antara :min dan :max item.',
        'file' => ':Attribute harus berukuran antara :min dan :max kilobyte.',
        'numeric' => ':Attribute harus bernilai antara :min dan :max.',
        'string' => ':Attribute harus terdiri dari :min sampai :max karakter.',
    ],
    'boolean' => ':Attribute harus bernilai benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Password salah.',
    'date' => ':Attribute bukan tanggal yang valid.',
    'different' => ':Attribute dan :other harus berbeda.',
    'digits' => ':Attribute harus terdiri dari :digits digit.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'extensions' => ':Attribute harus berupa file dengan ekstensi: :values.',
    'file' => ':Attribute harus berupa file.',
    'filled' => ':Attribute wajib diisi.',
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'lowercase' => ':Attribute harus menggunakan huruf kecil.',
    'max' => [
        'array' => ':Attribute tidak boleh berisi lebih dari :max item.',
        'file' => ':Attribute tidak boleh lebih besar dari :max kilobyte.',
        'numeric' => ':Attribute tidak boleh lebih besar dari :max.',
        'string' => ':Attribute tidak boleh lebih dari :max karakter.',
    ],
    'mimes' => ':Attribute harus berupa file bertipe: :values.',
    'mimetypes' => ':Attribute harus berupa file bertipe: :values.',
    'min' => [
        'array' => ':Attribute harus berisi minimal :min item.',
        'file' => ':Attribute harus berukuran minimal :min kilobyte.',
        'numeric' => ':Attribute minimal bernilai :min.',
        'string' => ':Attribute minimal terdiri dari :min karakter.',
    ],
    'not_in' => ':Attribute yang dipilih tidak valid.',
    'numeric' => ':Attribute harus berupa angka.',
    'password' => [
        'letters' => ':Attribute harus mengandung minimal satu huruf.',
        'mixed' => ':Attribute harus mengandung minimal satu huruf besar dan satu huruf kecil.',
        'numbers' => ':Attribute harus mengandung minimal satu angka.',
        'symbols' => ':Attribute harus mengandung minimal satu simbol.',
        'uncompromised' => ':Attribute ini pernah bocor di internet. Silakan pilih :attribute lain.',
    ],
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'required_if' => ':Attribute wajib diisi jika :other bernilai :value.',
    'required_with' => ':Attribute wajib diisi jika :values diisi.',
    'same' => ':Attribute dan :other harus sama.',
    'size' => [
        'array' => ':Attribute harus berisi :size item.',
        'file' => ':Attribute harus berukuran :size kilobyte.',
        'numeric' => ':Attribute harus bernilai :size.',
        'string' => ':Attribute harus terdiri dari :size karakter.',
    ],
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah dipakai.',
    'uploaded' => ':Attribute gagal diunggah.',
    'url' => ':Attribute harus berupa URL yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'custom' => [],

    'attributes' => [
        'username' => 'username',
        'name' => 'nama',
        'email' => 'email',
        'role' => 'peran',
        'password' => 'password',
        'password_confirmation' => 'konfirmasi password',
        'current_password' => 'password saat ini',
    ],

];
