<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use ReflectionClass;

/**
 * The base of the guards that look at an application's classes: the namespace of its source directory (PSR-4), from
 * which the type each file's path names is known, and the reflection of such a type. An application names the namespace
 * in the small class of its tests:
 *
 *     protected static function sourceNamespace(): string { return 'acme\notes'; }
 */
abstract class AbstractSourceGuard extends AbstractFileGuard
{
    /**
     * The types that were looked up and are not there, though the autoloader ran the file that their path names: the type
     * and that file.
     *
     * @var array<string, string>
     */
    private static array $absent = [];

    /** The namespace of the source directory (PSR-4), without a backslash at either end. */
    abstract protected static function sourceNamespace(): string;

    /**
     * The type that the path of each PHP file below a directory of the source names, by the file's path from the project's
     * root: the namespace and the path below the source directory, as the autoloader reads it. The directory is the
     * source directory or one below it, the suffix `.php` or the end of a name that ends in it (`Entity.php`).
     *
     * @return array<string, string>
     */
    protected static function sourceTypes(string $directory, string $suffix): array
    {
        $types = [];

        foreach (static::filesUnder($directory, $suffix) as $file) {
            $path = substr($file, strlen(static::sourceDirectory()) + 1, -strlen('.php'));
            $types[$file] = static::sourceNamespace() . '\\' . str_replace('/', '\\', $path);
        }

        return $types;
    }

    /**
     * The reflection of a type that the path of a file names. A file that declares another type than its path names is
     * not run again by a second look for the type (the autoloader would run it, and declare its class twice): the tests
     * of a guard look the same type up each, and ClassLoadingGuard is the one that explains the defect.
     *
     * @return ReflectionClass<object>
     */
    protected static function reflect(string $type, string $file): ReflectionClass
    {
        $declared = !isset(self::$absent[$type])
            && (class_exists($type) || interface_exists($type, false) || trait_exists($type, false));

        if (!$declared) {
            self::$absent[$type] = $file;
            self::fail('The file ' . $file . ' does not declare ' . $type . ', the type its path names.');
        }

        return new ReflectionClass($type);
    }
}
