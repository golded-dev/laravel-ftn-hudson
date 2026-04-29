# Changelog

Notable changes to `golded-dev/laravel-ftn-hudson`.

This project uses semantic versioning.

## 1.1.0 - 2026-04-29

### Added

- Attach parsed FTN control-line metadata to returned `ParsedMessage` objects.
- Attach message provenance with Hudson text-file path, message number, and text offset.
- Require `golded-dev/laravel-ftn` v1.2.0 in the lockfile.

## 1.0.0 - 2026-04-25

Initial stable release.

### Added

- Add Hudson message-base reader for `MSGIDX.BBS`, `MSGHDR.BBS`, and `MSGTXT.BBS` files.
- Add parsing for Hudson index records, message headers, header names, subject, posted date, raw attributes, and message body.
- Add reply-to and first-reply message number extraction.
- Add board-derived area metadata.
- Add charset detection through `golded-dev/laravel-ftn`.
- Add Pest, PHPStan, and Rector quality gates.
- Add public package documentation, security policy, code of conduct, archive hygiene, and CI workflow.
