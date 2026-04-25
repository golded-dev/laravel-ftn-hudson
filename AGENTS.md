# Agent Instructions

## Project Shape

- This is `golded-dev/laravel-ftn-hudson`, a small PHP 8.4 library.
- Purpose: read FTN/FidoNet Hudson message bases and return `Golded\Ftn\ParsedMessage` objects.
- Namespace: `Golded\Ftn\Hudson\`.
- Runtime dependency: `golded-dev/laravel-ftn` for contracts, DTOs, charset detection, and text helpers.
- Despite the name, this is not a Laravel app. Do not add service providers, config publishing, facades, container assumptions, or other framework furniture unless the package explicitly grows that surface.

## Boundaries

- Keep this package focused on Hudson `MSGIDX.BBS`, `MSGHDR.BBS`, and `MSGTXT.BBS` files.
- `.MSG`, JAM, Squish, packet parsing, and external area discovery belong in other packages unless the task explicitly says otherwise.
- `HudsonReader` should stay a concrete reader, not an importer pipeline.
- Avoid runtime dependencies beyond `golded-dev/laravel-ftn` unless there is a real parsing reason.
- Do not move shared parsing helpers from `golded-dev/laravel-ftn` into this package.

## Coding Style

- Use strict types in every PHP file.
- Follow the existing style: final classes, explicit return types, small private methods, and literal names.
- Keep parsing readable. Binary formats are already hostile enough.
- Preserve public API compatibility unless the task is explicitly a breaking change.
- Prefer adding focused tests over adding abstraction.

## Hudson Notes

- Hudson stores data across `MSGIDX.BBS`, `MSGHDR.BBS`, and `MSGTXT.BBS`.
- Missing files should yield no messages, not explode.
- Preserve lower-case filename fallback for Hudson files.
- Index records point to board numbers and may mark deleted messages with `0xFFFF`.
- Header strings are fixed-width binary fields. Convert them through `Text::toUtf8()`.
- Message text is stored in 128-byte records. Respect `startrec` and `numrecs`.
- Area metadata is board-derived: `BOARD{number}` and `hudson:{number}`.
- Old encodings are part of the domain, not a bug. Use `CharsetDetector` and `ReaderOptions` instead of assuming UTF-8.

## Tests And Quality Gates

- Run the focused test when touching the reader:
  - `vendor/bin/pest tests/Feature/HudsonReaderTest.php`
- Run the full suite before handing off code changes:
  - `composer test:all`
- The Composer scripts are:
  - `composer test`
  - `composer test:types`
  - `composer test:refactor`
  - `composer test:all`
- PHPStan is configured at max level through `phpstan.neon`.
- Rector uses `odinns/coding-style`; do not fight it by hand-formatting around it.

## Dependency And File Hygiene

- Do not edit `vendor/`.
- Do not commit generated caches or local artifacts.
- Keep `composer.lock` in sync if `composer.json` changes.
- Do not add a local path repository to `composer.json`.
- Keep `golded-dev/laravel-ftn` on a stable constraint for public releases.
- `CLAUDE.md` and `GEMINI.md` should remain symlinks to `AGENTS.md`.

## When Changing Public Behavior

- Think about downstream importers before changing yielded message semantics.
- Changing `HudsonReader::read()` signature, parsed field meanings, charset fallback behavior, or dependency constraints can be breaking.
- Add tests around behavior, not just structure.

## Review Bias

- Watch for scope creep. This repo should stay a thin Hudson reader.
- Watch for encoding assumptions. UTF-8-only thinking will lie to you here.
- Watch for importer logic trying to sneak in. That part smells a bit; keep it outside.
