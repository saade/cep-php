<?php

namespace Saade\Cep\Helpers;

function sanitizeCEP(string $cep, bool $formatted = false): string
{
    return str($cep)
        ->replaceMatches('/[^0-9]/', '')
        ->padLeft(8, '0')
        ->when($formatted, fn (\Illuminate\Support\Stringable $cep) => $cep->replaceMatches('/^(\d{5})(\d{3})$/', '$1-$2'));
}
