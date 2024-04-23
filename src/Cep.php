<?php

namespace Saade\Cep;

use GuzzleHttp\Promise\Utils;

class Cep
{
    public static function get(string $cep): ?DataObjects\CepResponse
    {
        $promises = [
            Providers\CorreiosProvider::getAsync($cep),
            Providers\CorreiosAltProvider::getAsync($cep),
            Providers\ViaCepProvider::getAsync($cep),
            Providers\BrasilApiProvider::getAsync($cep),
        ];

        return Utils::any($promises)
            ->then(onRejected: fn () => null)
            ->wait();
    }
}
