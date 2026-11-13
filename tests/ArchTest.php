<?php

arch('HTTP goes through the Saloon connector only')
    ->expect('ClassAtlas\USms')
    ->not->toUse([
        'Illuminate\Support\Facades\Http',
        'Illuminate\Http\Client',
        'GuzzleHttp',
        'curl_init',
        'curl_exec',
        'curl_multi_exec',
        'file_get_contents',
        'fsockopen',
    ]);

arch('no debugging calls')
    ->expect(['dd', 'ddd', 'dump', 'ray', 'var_dump', 'var_export', 'print_r', 'debug_zval_refcount'])
    ->not->toBeUsedIn('ClassAtlas\USms');
