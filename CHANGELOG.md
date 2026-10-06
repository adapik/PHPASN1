## v2.5.0
* add [`adapik/gmp-polyfill`](https://github.com/adapik/gmp-polyfill) as a pure PHP substitute for the `gmp` extension
  - `ext-gmp` is no longer a hard requirement; it is now only suggested for better performance
  - the polyfill is used automatically when the extension is not loaded
* **require PHP 8.1**
* remove the `php-curl` suggestion from `composer.json`

## v2.4.2 (2025-11-03)
* performance improvement for `Integer` [#12](https://github.com/Adapik/PHPASN1/pull/12)

## v2.4.1 (2025-11-03)
* expose the underlying GMP value of `Integer` and add tests [#11](https://github.com/Adapik/PHPASN1/pull/11)

## v2.4.0 (2025-08-02)
* require PHP 7.4
* adopt to modern PHPUnit (`^9.0`) and drop `php-coveralls` [#10](https://github.com/Adapik/PHPASN1/pull/10)
* add GitHub Actions workflow and build status badge

## v2.3.1 (2024-03-22)
* `getDecoratedObject` does not enforce proper use of end-of-content (EOC) markers [#8](https://github.com/Adapik/PHPASN1/pull/8)
* allow PHP 8.1 in CI

## v2.3 (2021-04-19)
* require PHP 7.2
* update PHPUnit to `^8.0` and `php-coveralls` to `^2.4`
