# Agent Guide

## Project Overview

This is a PHP 8.3+ library that provides a Behat extension for testing JSON-based HTTP APIs. Production code uses the `Imbo\BehatApiExtension\` namespace and is PSR-4 autoloaded from `src/`.

## Repository Layout

- `src/`: Extension, contexts, initializers, comparators, matchers, and exceptions.
- `tests/`: PHPUnit unit tests. Mirror the namespace and directory layout in `src/`.
- `features/`: Behat acceptance tests and their bootstrap fixtures.
- `docs/`: Sphinx end-user documentation.
- `.github/workflows/ci.yml`: The authoritative CI matrix and required checks.

## Setup

Install dependencies with:

```sh
composer install
```

PHP 8.3 or later is required. The test environment also requires the `fileinfo`, `json`, and `sockets` extensions.

## Validation

Run the focused check appropriate to a change before running the full suite:

```sh
composer run phpunit
composer run sa
composer run cs
```

`composer run ci` runs the Behat, coding-standard, PHPUnit, and static-analysis checks.

Behat feature tests need an HTTP server at `http://localhost:8080` serving `features/bootstrap`. Start it in a separate terminal before running Behat:

```sh
composer run dev
composer run behat
```

Use `composer run cs:fix` to apply PHP-CS-Fixer fixes. Generate documentation with `composer run docs` when documentation changes.

## Code Conventions

- Declare strict types in PHP files: `<?php declare(strict_types=1);`.
- Use four spaces for PHP indentation; use two spaces for JSON, YAML, and XML. Files use LF endings and a final newline.
- Follow `imbo/imbo-coding-standard`; do not manually reformat unrelated code.
- Keep production code in `src/` and tests in `tests/` with matching namespaces.
- Add PHPUnit coverage for unit-level behavior changes. PHPUnit requires coverage metadata, so use attributes such as `#[CoversClass(...)]` for new test classes.
- Add or update Behat scenarios for user-facing extension behavior. Feature fixtures live in `features/bootstrap/`.
- Document externally visible behavior and configuration in `docs/`.

## Change Guidance

- Preserve the public API unless the requested change intentionally alters it.
- Use strict comparisons and explicit types consistent with the existing codebase.
- For bug fixes tied to a GitHub issue, add a focused regression test and reference the issue in its docblock or feature-file comment, as described in `.github/CONTRIBUTING.md`.
- Do not modify generated caches or build output: `.php-cs-fixer.cache`, `.phpunit.result.cache`, and `build/`.
