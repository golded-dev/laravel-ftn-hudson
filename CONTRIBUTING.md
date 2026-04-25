# Contributing

This package is intentionally narrow.

Good contributions make Hudson parsing more correct. That is the whole trick.

## Scope

Good fits:

- Hudson index parsing fixes
- Hudson header parsing fixes
- body parsing fixes for real FTN messages
- charset handling that flows through `golded-dev/laravel-ftn`
- safer handling of malformed or partial Hudson files
- tests using small fixtures or real-world edge cases
- documentation fixes

Usually not a fit:

- `.MSG`, Squish, JAM, packet, or area discovery readers
- Laravel service providers, facades, config publishing, or app bootstrapping
- database models or migrations
- importer orchestration
- broad abstractions without another reader proving the need

If a change needs a paragraph to justify why it belongs here, it probably belongs somewhere else.

## Development Setup

```bash
composer install
```

## Quality Gates

Run the full suite before opening a pull request:

```bash
composer test:all
```

For focused work:

```bash
composer test
composer test:types
composer test:refactor
```

## Coding Style

- Use strict types.
- Keep `HudsonReader` small and literal.
- Add explicit return types.
- Prefer boring parsing over clever parsing.
- Do not edit `vendor/`.
- Keep `composer.lock` in sync when `composer.json` changes.

## Public API Changes

Be careful with these:

- `HudsonReader::read()` signature
- parsed field semantics
- charset fallback behavior
- dependency constraints

Those can be breaking changes. Say so plainly in the pull request and changelog.

## Tests

Add tests for behavior, especially around:

- missing Hudson files
- upper-case and lower-case filenames
- short index, header, or text reads
- deleted index records
- date formats seen in real headers
- board-derived area metadata
- charset conversion

Tiny binary-format bugs are the worst kind of boring. Test them.

## Pull Requests

Use a clear title and explain:

- what changed
- which Hudson behavior it fixes
- whether public API changed
- which commands passed

Keep pull requests focused. Unrelated cleanup can wait.

## Security Reports

Do not report security issues in public tickets. Use the private reporting path in [SECURITY.md](SECURITY.md).
