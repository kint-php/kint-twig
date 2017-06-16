# Kint-Twig

A Twig extension providing the familiar `d()` and `s()` functions for dumping data.

Note that features like the mini-trace, variable name detection, and modifiers will not work from inside twig templates.

## Usage

```php
$twig->addExtension(new Kint_TwigExtension());
```

```twig
{{ d(data, richMode, moreData, evenMoreData) }}

{{ s(data, plainMode) }}
```
