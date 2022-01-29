# Kint-Twig

A Twig extension providing the familiar Kint functionality for dumping data.

Note that features like the mini-trace, variable name detection, and modifiers will not work from inside twig templates.

## Usage

```php
$twig->addExtension(new Kint\Twig\TwigExtension());
```

```twig
{{ d(data, richMode, moreData, evenMoreData) }}

{{ s(data, plainMode) }}
```

Custom function names dumpers:

```php
$ext = new Kint\Twig\TwigExtension();

$aliases = $ext->getAliases();

// Different alias for existing dumper
$aliases['dump'] = $aliases['d'];

// Custom dumper
$text = new Kint\Kint(new Kint\Parser\Parser(), new Kint\Renderer\TextRenderer());
$text->setStatesFromStatics(Kint\Kint::getStatics());

$aliases['text'] = $text;

$ext->setAliases($aliases);

$twig->addExtension($ext);
```

```twig
{{ dump() }}
{{ text() }}
```
