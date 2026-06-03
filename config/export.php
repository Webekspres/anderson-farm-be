<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Excel export — warna header tabel
    |--------------------------------------------------------------------------
    |
    | Format ARGB 8 digit (mis. FFC6EFCE) atau RGB 6 digit (mis. C6EFCE).
    | Diatur via HEADER_TABLE_COLOR di .env.
    |
    */

    'header_table_color' => env('HEADER_TABLE_COLOR', 'FFC6EFCE'),

];
