<?php

namespace Saade\Cep\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Saade\Cep\Cep
 */
class Cep extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Saade\Cep\Cep::class;
    }
}
