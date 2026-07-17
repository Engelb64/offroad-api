<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media disk
    |--------------------------------------------------------------------------
    |
    | Disco usado para fotos de talleres (y futuros media de usuario).
    | Local: "public" (storage/app/public + php artisan storage:link).
    | Producción: "s3" (o R2 compatible) sin cambiar el código de negocio.
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

    'max_upload_kb' => (int) env('MEDIA_MAX_UPLOAD_KB', 5120), // 5 MB

    'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],

    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],

];
