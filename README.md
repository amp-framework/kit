# ampf-kit

Small building blocks that applications on [ampf](https://github.com/amp-framework/ampf) have in common and that are
not the framework's business: UUID ids with MariaDB's `UUID` column, the set-up of Doctrine's migrations, an integration
test case on a disposable database, and guards that hold an application to a set of conventions.

What belongs here fits an application as it is. What every application would have to override — its users, its login,
its controllers, its settings, its look — is the application's own, and so is what only one application needs.

**Installation.** The package is not on Packagist. An application requires it from its Git repository, as any private
package: `"repositories": [{"type": "vcs", "url": "<the repository's URL>"}]` and `"amp-framework/ampf-kit": "dev-main"`.
While the package and an application are developed together, a path repository (`{"type": "path", "url":
"../ampf-kit"}`) links a checkout into the application's `vendor/` instead.

## What it holds

`ampf\Kit\` in `src/`:

| Part | What it is |
| --- | --- |
| `Helper\Uuid` | A UUID v7 (`generate()`), whether a text is a UUID (`isValid()`), and the pattern of one for a route (`PATTERN`) |
| `Helper\Text` | What the rules for the texts a person types have in common: kept without white space at its ends (`trim()`), one line (`isOneLine()`), lines (`isLines()`) |
| `Doctrine\Entity\BaseEntity` | What every entity has: a UUID that the application assigns when the object is made, and `TABLE_OPTIONS`, the one character set and collation of every table (utf8mb4 and `utf8mb4_uca1400_ai_ci`) |
| `Doctrine\Type\UuidType` | DBAL's `guid` as MariaDB's `UUID` column (16 bytes, text in and out) |
| `Bootstrap\MigrationsFactory`, `Bootstrap\DoctrineConfiguration` | Doctrine Migrations over the application's `migrations` block (`bin/doctrine` and the guard of the migrations both use it), and the name of the migrations' own table, which the schema tool is told to leave alone (`ignoreMigrationsTable()`) |
| `Testing\IntegrationTestCase` | ampf's `ApplicationTestCase` on a disposable MariaDB (below) |
| `Testing\Guard\…` | Conventions as tests that an application points at its own files and at its running self (below) |

`config/default.php` is the package's `doctrine` block, which an application lists after ampf's two files: every datetime in
UTC and a database's enum read as a string (ampf's own entries) and `guid` as `UuidType`, which `doctrine.mappingOverrides`
reads back as `guid`. No class is final.

## How an application wires it

**Entry points.** `public/index.php`, `bin/index.php` and `bin/doctrine` list the package's `config/default.php` after
ampf's two files and before the application's own:

```php
$config = ApplicationContext::boot([
    $root . '/vendor/amp-framework/ampf/config/default.php',
    $root . '/vendor/amp-framework/ampf/config/http.php',      // or cli.php
    $root . '/vendor/amp-framework/ampf-kit/config/default.php',
    $root . '/config/default.php',
    $root . '/config/http.php',                                // or cli.php
    $root . '/config/local.php',
]);
```

The files merge one level deep: an application that sets `doctrine.typeOverrides` or `doctrine.mappingOverrides` itself
replaces the package's whole block and repeats its entries beside its own (`DoctrineSettingsGuard` holds it to that).

**The database.** MariaDB 10.10 or later (the `UUID` type, the UCA 14 collations), a connection in UTC (`SET time_zone =
'UTC'`). `config/local.php` builds the ORM configuration with ampf's `DoctrineConfiguration::create()` (the attribute
mapping and native lazy objects; no cache directory on a development machine). An application's entities extend `BaseEntity`
and put their tables on `BaseEntity::TABLE_OPTIONS`:

```php
#[ORM\Entity(repositoryClass: NoteRepo::class)]
#[ORM\Table(name: 'notes', options: self::TABLE_OPTIONS)]
class NoteEntity extends BaseEntity {}
```

**The migrations.** The application's `migrations` block lists its migrations; `MigrationsFactory::create($config,
$entityManager)` reads it (`bin/doctrine` and `MigrationsGuard` both call it):

```php
'migrations' => [
    'migrations_paths' => ['acme\notes\Migration' => dirname(__DIR__) . '/src/Migration'],
    'table_storage' => ['table_name' => DoctrineConfiguration::MIGRATIONS_TABLE],
    'transactional' => false,
],
```

**The tests.** `ampf\Kit\Testing\IntegrationTestCase` is ampf's `ApplicationTestCase` with the package's `config/default.php`
after ampf's files and a disposable MariaDB: a subclass names the project root (`projectRoot()`), and
`tests/Support/config/integration.php` of the project, which boots in place of `config/local.php`, names the database. It
refuses a database whose name does not end in `_test` or `_test_<n>` (`disposableDatabasePattern()`, which an application may
tighten), makes the schema from the mapping once per process, and starts every test with empty tables. `$em` is the test's own
entity manager, which the beans of `ownBean()` share; a request's is closed when the next request starts. `dbText()`,
`dbTexts()`, `countSelects()` and `rebuildSchema()` are the helpers. It extends PHPUnit's `TestCase`: an application that uses it
has PHPUnit as a development dependency.

**The guards.** `ampf\Kit\Testing\Guard\` holds conventions as tests of an application's own files, in the
pattern of ampf's guards (`ampf\Testing\Guard\`): a guard is an abstract `TestCase` that carries its tests and data providers,
and the application extends it in one small class of its `tests/Unit/` (those that read files) or its `tests/Integration/`
(those that boot the application) that names its project root (`projectRoot()`) and whatever else is its own; the hooks have
these conventions as their defaults.

```php
final class TemplateTextTest extends TemplateTextGuard
{
    protected static function projectRoot(): string { return dirname(__DIR__, 2); }

    protected static function allowedLiterals(): array { return [...parent::allowedLiterals(), 'Note', 'Book']; }
}
```

A failure says what is wrong and names the file (from the project's root), the key, the section, the table or the class at
fault; one that finds nothing to look at (a directory without a template, a list of words that is empty) fails too.
`SensitiveParametersGuard` is the one that does not: a source with no parameter named for a password has nothing unmarked, so it
passes with one data set that says so, and is never skipped. Those that read the configuration merge it as the entry points do:
ampf's files, the package's `config/default.php`, the application's.

| Guard | Holds | The application names | Hooks (default) |
| --- | --- | --- | --- |
| `TemplateTextGuard` | A template has no text of its own: the text of an element (also the content of a `<template>` element) and the attributes a person reads (`alt`, `title`, `label`, `aria-label`, `aria-description`, `aria-placeholder`, `aria-roledescription`, `aria-valuetext`, `placeholder`, `value`, a meta description) come from PHP | | `allowedLiterals()` (`·`, `\|`, `/` and `*`: add the wordmark's pieces), `templateDirectory()` (`views/http`) |
| `TemplateMarkupGuard` | No inline script, style or `on…=` (the Content Security Policy refuses them); an error alert (any element whose `class` lists the error class, among others and in any order) takes the focus | | `errorAlertClass()` (`alert alert--error`: its last word is the one looked for), `templatesWithoutAlertFocus()` (none: name the templates whose page puts the focus on a field, the login's say), `templateDirectory()` |
| `TranslationFilesGuard` | A file for every language and a language for every file; keys in dotted groups, sorted, the same in each; the same arguments and ICU arguments; texts safe to print; ICU messages their language reads | `languages()`: the codes, the base language first | `translationDirectory()` (`config/translations`), `baseLanguage()` (the first) |
| `TranslationKeyUsageGuard` | A string written like a key of a group the base language has is a key of it, and every key is named in the code | `languages()` | `scannedDirectories()` (`src`, `views`), `translationDirectory()`, `baseLanguage()` |
| `SensitiveParametersGuard` | A parameter named for a password is `#[SensitiveParameter]`; a source with none passes | `sourceNamespace()` | `sensitiveNames()` (`password`: add `token`, a key), `sourceDirectory()` |
| `EntityConventionsGuard` | Every entity below `Doctrine/Entity` (a file `*Entity.php`, or any file that declares `#[ORM\Entity]` or `#[ORM\MappedSuperclass]`) extends `BaseEntity`, names a repository of ampf's kind and a table on `BaseEntity::TABLE_OPTIONS` (a mapped superclass has neither) | `sourceNamespace()` | `sourceDirectory()` (`src`), `entityDirectory()` (`Doctrine/Entity`) |
| `DoctrineSettingsGuard` | An application's own `doctrine.typeOverrides` or `mappingOverrides` keeps every entry of ampf's and the package's | | `transports()` (`http`, `cli`) |

The guards that boot the application are `ampf\Kit\Testing\IntegrationTestCase`s (`AbstractApplicationGuard`): they run in the
application's integration suite, over its `tests/Support/config/integration.php` and the disposable database.

| Guard | Holds | Hooks (default) |
| --- | --- | --- |
| `SchemaGuard` | MariaDB 10.10 or later, a connection in UTC, a mapping the schema tool accepts, every table InnoDB and every table and string column on the collation of `BaseEntity::TABLE_OPTIONS`, every `id` a UUID | `serverVersion()` |
| `MigrationsGuard` | Every migration says what it does; run on an empty database the migrations build exactly the mapped schema, on that collation (the migrations' table included); the way back undoes them all and a second run has nothing left | |

A guard is tested against an application that abides and applications that break each rule (`tests/Fixtures/<Guard>/`,
`AGENTS.md`); `tests/Unit/Testing/Guard/Abiding/` and `tests/Integration/Testing/Guard/Abiding/` run them as an application
does.

## Working on the package

PHP runs only in Docker; the host needs Docker with Compose and nothing else. The scripts are those of the applications
(`sh docker/tooling`, `sh docker/ci`, `sh docker/test-integration`, `sh docker/mutation`): a throwaway container of the
image `ampf-kit-tooling`, as the calling user, the checkout at `/app`, no network but for Composer. The disposable test
stack (`docker/compose.test.yml`, the Compose project `ampf-kit-test`) is a MariaDB in memory and a network with no way
out.

```sh
sh docker/tooling install                          # the dependencies from composer.lock
sh docker/ci [static|unit|integration|mutation]    # every gate, in order; the first failing step's exit status
AMPF_KIT_AMPF_CHECKOUT=$PWD/../ampf sh docker/ci    # against an unreleased checkout of ampf (docker/mounts.sh)
```

The gates are ampf's: PHPCS and PHP-CS-Fixer with ampf's own rules, PHPStan at the maximum level, both suites, and
mutation testing at 100 %. The code is `ampf\Kit\` in `src/`; the tests are `ampf\Kit\Tests\` in `tests/`, and the ones
that need a booted application run the fixture application in `tests/Fixtures/App/` (`AGENTS.md`).
