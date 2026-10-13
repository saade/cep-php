# High availability brazilian CEP finder with service redundancy

[![Latest Version on Packagist](https://img.shields.io/packagist/v/saade/cep-php.svg?style=flat-square)](https://packagist.org/packages/saade/cep-php)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/saade/cep-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/saade/cep-php/actions/workflows/run-tests.yml?query=branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/saade/cep-php/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/saade/cep-php/actions/workflows/fix-php-code-style-issues.yml?query=branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/saade/cep-php.svg?style=flat-square)](https://packagist.org/packages/saade/cep-php)
[![Plumb score](https://plumbphp.dev/badges/saade/cep-php/composite.svg)](https://plumbphp.dev/saade/cep-php)

## Installation

You can install the package via composer:

```bash
composer require saade/cep-php
```

## Usage

```php
use Saade\Cep\Cep;

$cep = Cep::get('28895-190');

$cep->cep; // 28895190
$cep->state; // RJ
$cep->city; // Rio das Ostras
$cep->neighborhood; // Costazul
$cep->street; // Rua Nelson Pecegueiro do Amaral
$cep->provider; // open-cep / correios / viacep / widenet / correios-alt / brasil-api
```

`Cep::get()` returns `null` when no provider can resolve the CEP.

### How it works

1. [OpenCEP](https://opencep.com) is queried first.
2. If it can't resolve the CEP, Correios, ViaCEP, WideNet, Correios (alt) and BrasilAPI are queried concurrently and the first one to answer wins.

You may also race the providers of your choice:

```php
use Saade\Cep\Cep;
use Saade\Cep\Providers\BrasilApiProvider;
use Saade\Cep\Providers\ViaCepProvider;

$cep = Cep::race('28895-190', [ViaCepProvider::class, BrasilApiProvider::class]);
```

Or query a single one, which throws when the CEP can't be resolved:

```php
use Saade\Cep\Providers\ViaCepProvider;

$cep = ViaCepProvider::get('28895-190');
```

## Testing

```bash
composer test
```

`composer test:live` runs the suite that hits the real providers.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Saade](https://github.com/saade)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
