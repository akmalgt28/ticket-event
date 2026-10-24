<?php

return [
    // Isi rekening resmi di sini. Jangan gunakan nomor rekening dari gambar referensi.
    // Setiap rekening: bank_name, account_number, account_holder (semuanya string).
    'bank_transfer' => [
        'accounts' => [],
    ],

    // Informasi tambahan berdasarkan slug event, tanpa mengubah skema database.
    // 'slug-event' => ['category' => '...', 'organizer_name' => '...', 'terms' => '...'],
    'events' => [],
];
