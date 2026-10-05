# amp-framework/kit: agent guide

The package `amp-framework/kit` holds the small building blocks that applications on ampf share: UUID ids with
MariaDB's UUID column, the set-up of Doctrine's migrations, the integration test case, and the guards that hold conventions as tests.
`README.md` says what it holds and how an application wires it; this file is the orientation for working **on** it, and
changes with the conventions it states.

## What belongs here

Code goes into the package when other applications would use it as it is. Where one application would have to
override it to fit, or only one needs it, it stays in that application: an application's users, its login, its controllers,
its settings and its look are not the package's, and a duplication between two applications is no reason by itself — when in
doubt, leave it where it is. The package is lean, and so is ampf, which does what every application on it needs (requests and
responses, the session, the view); the package is for what is shared above that and is not bare MVC. No class is final: every
application may extend one.

## Rules for every task

1. **Read this file, then `git status --short`.** Keep what you did not change. Commit when the checks of rule 4 pass: a subject
   line that says what a caller gains, as ampf's log does, and, where it helps to understand the change, a body; no trailer.
   The repository is meant to be published: write as its maintainer would — no "the owner", no names of private projects,
   no personal paths. Never push or tag.
2. **Applications come first.** Applications build on these names: a class, method, configuration
   key or table that changes is named in the hand-over, with what an application changes.
3. **Tests first, red first:** every behaviour gets a test seen failing for the right reason before the code exists; a fix gets
   one that is red on the previous source. Say what failed.
4. **Before handing over, run sparingly:** PHPCS, PHP-CS-Fixer and PHPStan at 0 and the tests of what changed; mutation
   testing on the files that got new logic (`sh docker/mutation path`); `sh docker/ci` as a whole — both suites, every mutant
   killed — before a release or when asked, not at every commit.
5. **ampf is read-only from here:** what the package needs from it is proposed to ampf's maintainer (additions
   only). The package runs against an unreleased checkout of ampf with `AMPF_KIT_AMPF_CHECKOUT=$PWD/../ampf`.
6. **No secrets, no project words:** nothing of a machine, a database or a log in a test or a document; no ids of a plan, no
   "step", "later" or "for now" in code, tests or documents.

## Commands

PHP runs only in Docker: a throwaway container of the image `ampf-kit-tooling`, as the calling user, no network but for
Composer; the integration suite and the mutation testing start the disposable MariaDB of `docker/compose.test.yml` (a
database for each parallel process, `ampf_kit_test_<n>`) and tear it down. One test stack at a time.

```sh
sh docker/ci [static|unit|integration|mutation]   # everything in order; AMPF_KIT_AMPF_CHECKOUT=… for ampf's checkout
sh docker/tooling composer phpcs | cs:check | phpstan | test   # phpcbf and cs:fix fix what they can: review the diff
sh docker/test-integration [--filter X]           # the integration suite
sh docker/mutation [src/Path/File.php]            # a part prints its score and never fails on the thresholds
```

## Conventions

- **ampf's** (its `AGENTS.md`, "Conventions" and "Mutation testing"): PSR-4 in exact case; native types,
  `declare(strict_types=1)`, `===`; a docblock says what a caller can rely on and what it throws.
- **An exception says what is wrong**, and a test asserts its message whole (`ExpectsExactMessage`).
- **A guard is tested against applications**, not mocks: `tests/Fixtures/<Guard>/Abiding` keeps the rules and the others break
  them (excluded by name from PHPCS, PHP-CS-Fixer and PHPStan, and never loaded by another suite); the test calls the guard's
  test methods and asserts the failure's message whole (`GuardFailures`), and `tests/Unit/Testing/Guard/Abiding/` runs each
  guard as an application's class does. A guard that boots the application (`AbstractApplicationGuard`) is tested in
  `tests/Integration/Testing/Guard/` the same way, over the fixture application, and a variant that breaks a rule is an overlay
  file (`tests/Fixtures/<Guard>/<Variant>/overlay.php`) that replaces a bean or a block of it, which the double of the guard in
  the test appends (`tests/Support/RunsAsAGuard`), never a second copy of the application. A guard's test method and its
  `#[DataProvider]` line are exempt from two mutators in `infection.json5`, with the reason.
- **Mutation testing at 100 %** over `src/` (`infection.json5`): an escaped mutant is a missing test or needless code; an
  exemption names its place and its reason. Never lower a threshold.

## Repository map

| Location | Contents |
| --- | --- |
| `src/` | `ampf\Kit\`: `Bootstrap/`, `Doctrine/`, `Helper/`, `Testing/` (the integration test case, `Guard/`) |
| `config/` | `default.php`, which an application lists after ampf's files |
| `tests/Unit/`, `tests/Integration/` | The parts and the guards that read files; the database, the integration test case and the guards that boot an application |
| `tests/Fixtures/App/` | The fixture application, a small application on the package: a shelf and its notes with their migration, one page that asks the database, and its test database (`tests/Support/config/integration.php`) |
| `tests/Fixtures/<Guard>/` | The applications a guard judges (`Abiding`, and one or more that break each rule), a project of its own for each: configuration, templates, translations, classes under a namespace that autoloads them; for a guard that boots the application, the variants of the fixture application instead: classes and an `overlay.php` each |
| `tests/Support/` | `FixtureApplicationTestCase`, `RunsAsAGuard` and `RunnableGuard` (what the double of a guard that boots the application offers a test), `GuardFailures`, `TemporaryDirectory` |
| `docker/`, the tools' files | The container, the test stack, `ci`; `infection.json5`, `phpcs.xml.dist`, `phpstan.neon.dist`, `phpunit.xml.dist` |
