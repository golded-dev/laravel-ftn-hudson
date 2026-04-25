# Laravel FTN Hudson

FTN/FidoNet Hudson message-base reader for PHP 8.4.

This package reads Hudson `MSGIDX.BBS`, `MSGHDR.BBS`, and `MSGTXT.BBS` files and returns normalized `ParsedMessage` objects from `golded-dev/laravel-ftn`.

It does not write Hudson files, repair broken message bases, parse packet files, read `.MSG`, JAM, Squish, or add Laravel framework bootstrapping. The package name says Laravel because it belongs to the GoldED.dev Laravel package family. The runtime code is plain PHP.

## Installation

```bash
composer require golded-dev/laravel-ftn-hudson:^1.0
```

Requires PHP 8.4+.

## Reading A Message Base

Pass the directory that contains the Hudson files:

```php
<?php

declare(strict_types=1);

use Golded\Ftn\Hudson\HudsonReader;

$reader = new HudsonReader();

foreach ($reader->read('/path/to/messages/HUDSON') as $message) {
    echo $message->msgno.PHP_EOL;
    echo $message->fromName.' -> '.$message->toName.PHP_EOL;
    echo $message->subject.PHP_EOL;
    echo $message->bodyText.PHP_EOL;
}
```

`HudsonReader::read()` looks for `MSGIDX.BBS`, `MSGHDR.BBS`, and `MSGTXT.BBS`, with lower-case filename fallback. Missing or unreadable files produce an empty result.

## Reader Options

```php
use Golded\Ftn\Hudson\HudsonReader;
use Golded\Ftn\ReaderOptions;

$messages = new HudsonReader()->read(
    path: '/path/to/messages/HUDSON',
    options: new ReaderOptions(fallbackCharset: 'CP437'),
);
```

The fallback charset is used when the message body does not declare a usable FTN charset control line. The default comes from `golded-dev/laravel-ftn`.

## What Gets Parsed

The reader extracts:

- message number from the Hudson header
- board number from the Hudson index
- from name
- to name
- subject
- body text converted to UTF-8
- raw attribute bitfield
- posted date when the Hudson date and time fields can be parsed
- reply-to message number
- first reply message number
- board-derived area code, area name, sort order, and metadata key

Hudson stores areas as board numbers, so the reader exposes area fields as `BOARD{number}` and `hudson:{number}`. It is blunt, but stable. Downstream importers can map those board numbers to prettier names.

## What You Do Not Get

- No Hudson writer or repair tool.
- No packet parsing.
- No `.MSG`, JAM, Squish, or other message-base readers.
- No external area-name discovery.
- No Laravel service provider.
- No database models.
- No queues, commands, config publishing, or framework bootstrapping.

Pair this package with your own source locator or import pipeline.

## Development

Install dependencies:

```bash
composer install
```

Run tests:

```bash
composer test
```

Run static analysis:

```bash
composer test:types
```

Run Rector dry-run:

```bash
composer test:refactor
```

Run everything:

```bash
composer test:all
```

## Versioning

This package starts at `1.0.0` and uses semantic versioning.

Versions come from Git tags. Do not add a `version` field to `composer.json`.

Breaking changes include:

- changing `HudsonReader::read()` behavior in a way that drops messages previously yielded
- changing parsed field semantics
- changing charset fallback behavior
- changing the required PHP version
- changing the `golded-dev/laravel-ftn` public contract this reader returns

Adding support for more Hudson header fields is usually a minor release when existing fields keep their meaning.

## Contributing

Contributions are welcome when they make Hudson parsing more correct without turning this into a framework package. See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

Do not report security issues in public tickets. See [SECURITY.md](SECURITY.md).

## Code Of Conduct

Be direct, useful, and not a pain on purpose. See [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

Released under the MIT License. See [LICENSE](LICENSE).
