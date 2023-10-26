<?php

namespace Saade\Cep;

use GuzzleHttp\Promise\Utils;

class Cep
{
    public static function get(string $cep): ?DataObjects\CepResponse
    {
        $promises = [
            Providers\CorreiosProvider::get($cep),
            Providers\CorreiosAltProvider::get($cep),
            Providers\ViaCepProvider::get($cep),
            Providers\BrasilApiProvider::get($cep),
        ];

        return Utils::any($promises)
            ->then(onRejected: fn () => null)
            ->wait();
    }
}
