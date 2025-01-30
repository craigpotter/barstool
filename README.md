# Barstool 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/craigpotter/barstool.svg?style=flat-square)](https://packagist.org/packages/craigpotter/barstool)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/craigpotter/barstool/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/craigpotter/barstool/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/craigpotter/barstool/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/craigpotter/barstool/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/craigpotter/barstool.svg?style=flat-square)](https://packagist.org/packages/craigpotter/barstool)

> [!CAUTION]
> This package is currently in development and using in production should be at your own risk. 
> Breaking changes could still happen before a stable v1.0. Please check back soon for updates.

Bartool is a dedicated Laravel package to help you keep track of your [Saloon](https://github.com/saloonphp/saloon) requests & responses.

Barstool will allow you to easily view, search, and filter your logs directly in your database tool of choice.

The package is designed to be as simple as possible to get up and running, with minimal configuration required.

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

That's all folks! 
Once installed, it will start logging your [Saloon](https://github.com/saloonphp/saloon) requests automatically.
Check the config out for more control. 

Here are some of the things you can see with Barstool:
- Request Method
- Connector Used
- Request Used
- Request URL
- Request Headers
- Request Body
- Response Status Code
- Response Headers
- Response Body
- Response Duration

The logging will even log fatal errors caused by your saloon requests so you can see what went wrong.
<p><img src="/art/fatal_error.png" alt="Screenshot of the fatal error logged in the database"></p>

> [!TIP]
> We will be adding more features soon, so keep an eye out for updates!


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](./.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover any security related issues, please email barstool@craigpotter.co.uk instead of using the issue tracker.

## Credits

- [Craig Potter](https://github.com/craigpotter)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
