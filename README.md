# phpnomad/utils

[![Latest Version](https://img.shields.io/packagist/v/phpnomad/utils.svg)](https://packagist.org/packages/phpnomad/utils)
[![Total Downloads](https://img.shields.io/packagist/dt/phpnomad/utils.svg)](https://packagist.org/packages/phpnomad/utils)
[![PHP Version](https://img.shields.io/packagist/php-v/phpnomad/utils.svg)](https://packagist.org/packages/phpnomad/utils)
[![License](https://img.shields.io/packagist/l/phpnomad/utils.svg)](https://packagist.org/packages/phpnomad/utils)

`phpnomad/utils` is the small set of helpers PHPNomad packages reach for when they need to work with arrays, strings, numbers, and objects. It ships an `Arr` static helper alongside `Str`, `Num`, and `Obj` companions, plus two processor classes (`ArrayProcessor` and `ListFilter`) for chainable transforms and queries. Zero runtime dependencies, used throughout the PHPNomad ecosystem.

## Installation

```bash
composer require phpnomad/utils
```

## Overview

- `Arr` covers filtering, sorting, plucking, flattening, hydrating, casting, and dot-notation lookups for nested values, along with `array_*` proxies that take the input array as the first argument.
- `Str`, `Num`, and `Obj` handle the usual companion work: case conversion, pluralization, percentage math, and nested object access with getter-method fallback.
- `ArrayProcessor` wraps any `Arr` method into a chain, holds the array through each step, and can be cast to a string with a configurable separator.
- `ListFilter` queries an array of objects with chainable predicates covering dot-notation field access, `in` / `notIn` / `and` / `equals`, numeric comparisons, instance-type checks, key filters, and arbitrary callbacks.
- No runtime dependencies, so importing it anywhere in a PHPNomad stack stays cheap.

## Documentation

Full API reference and usage examples live at [phpnomad.com](https://phpnomad.com).

## License

MIT. See [LICENSE.txt](LICENSE.txt).
