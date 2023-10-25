<?php

namespace Saade\Cep\Commands;

use Illuminate\Console\Command;

class CepCommand extends Command
{
    public $signature = 'cep-php';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
