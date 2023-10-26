# High availability brazilian CEP finder with service redundancy

[![Latest Version on Packagist](https://img.shields.io/packagist/v/saade/cep-php.svg?style=flat-square)](https://packagist.org/packages/saade/cep-php)
[![Total Downloads](https://img.shields.io/packagist/dt/saade/cep-php.svg?style=flat-square)](https://packagist.org/packages/saade/cep-php)

## Installation

You can install the package via composer:

```bash
composer require saade/cep-php
```

## Usage

```php
use Saade\Cep;

$cep = Cep::get('28895-190')

$cep->cep; // 28895190
$cep->state; // RJ
$cep->city; // Rio das Ostras
$cep->neighborhood; // Costazul
$cep->street; // Rua Nelson Pecegueiro do Amaral
$cep->provider; // correios / correios-alt / viacep / brasil-api
```

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
