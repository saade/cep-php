<?php

use function Saade\Cep\Helpers\sanitizeCEP;

it('strips everything but digits', function (string $cep) {
    expect(sanitizeCEP($cep))->toBe('28895190');
})->with([
    'masked' => '28895-190',
    'digits only' => '28895190',
    'dotted' => '28.895-190',
    'surrounding whitespace' => ' 28895-190 ',
    'with label' => 'CEP: 28895190',
]);

it('left pads short ceps with zeros', function (string $cep, string $expected) {
    expect(sanitizeCEP($cep))->toBe($expected);
})->with([
    'leading zero dropped' => ['1001000', '01001000'],
    'masked with leading zero dropped' => ['1001-000', '01001000'],
    'empty' => ['', '00000000'],
]);

it('formats the cep with a dash when asked', function (string $cep, string $expected) {
    expect(sanitizeCEP($cep, formatted: true))->toBe($expected);
})->with([
    'digits only' => ['28895190', '28895-190'],
    'already masked' => ['28895-190', '28895-190'],
    'padded' => ['1001000', '01001-000'],
]);
