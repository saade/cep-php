<?php

namespace Saade\Cep\Helpers;

function sanitizeCEP(string $cep, bool $formatted = false): string
{
    $cep = preg_replace('/[^0-9]/', '', $cep);
    $cep = str_pad($cep, 8, '0', STR_PAD_LEFT);

    if ($formatted) {
        $cep = preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $cep);
    }

    return $cep;
}
