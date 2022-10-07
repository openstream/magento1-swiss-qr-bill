# Magento 1.x Swiss QR Bill
Generate a QR Bill with Magento 1 and OpenMage using https://github.com/sprain/php-swiss-qr-bill

## Installation

1. Copy contents of this repository into the root of your project. If you already have `composer.json` add new dependencies from the "required" section. If you are not using modman feel free to remove `modman` file.
2. If your Magento installation is not using composer autoloader add the following line topmost at your `index.php`:

```php
require_once '/vendor/autoload.php';
```

3. Run `composer install` in case you were not using composer before otherwise `composer update` to install new dependencies.
4. Flush Magento caches

## Configuration

Proceed to `System \ Configuration \ Sales \ Sales \ Qr Bill` section of your Magento admin interface and complete all the fields.
