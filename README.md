# Barstool 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/craigpotter/barstool.svg?style=flat-square)](https://packagist.org/packages/craigpotter/barstool)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/craigpotter/barstool/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/craigpotter/barstool/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/craigpotter/barstool/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/craigpotter/barstool/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/craigpotter/barstool.svg?style=flat-square)](https://packagist.org/packages/craigpotter/barstool)

Bartool is a dedicated Laravel package to help you keep track of your [Saloon](https://github.com/saloonphp/saloon) requests & responses.

Barstool will allow you to easily view, search, and filter your logs in a user-friendly interface or directly in your database tool of choice.

So pull up a barstool, grab a drink, and let's get logging in the Saloon! Yeehaw!

## Installation

You can install the package via composer:

```bash
composer require craigpotter/barstool
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="barstool-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="barstool-config"
```

## Usage


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](../.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover any security related issues, please email barstool@craigpotter.co.uk instead of using the issue tracker.

## Credits

- [Craig Potter](https://github.com/craigpotter)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
