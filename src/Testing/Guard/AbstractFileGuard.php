<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use ampf\Testing\Guard\AbstractGuard;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * The base of the guards that read an application's files: ampf's AbstractGuard (the project root, which an
 * application names in one small class of its tests) with the files of the project — a directory's files by their
 * suffix, a file's contents —, the configuration as the entry points merge it when the package's configuration is
 * among the files, and an assertion that says every problem at once. Paths in a message are from the project's root.
 */
abstract class AbstractFileGuard extends AbstractGuard
{
    use ReportsProblems;

    /** The directory of the application's classes, relative to the project root. */
    protected static function sourceDirectory(): string
    {
        return 'src';
    }

    /**
     * The files an entry point lists before the application's own: the framework's default.php and the transport's file,
     * then the package's default.php (README, "How an application wires it").
     *
     * @return list<string>
     */
    protected static function packageConfigurationFiles(string $transport): array
    {
        return MergedConfiguration::packageFiles($transport);
    }

    /**
     * The configuration of the transport as the application's entry point boots it, without the machine's
     * config/local.php: the framework's files, the package's, then the application's default.php and the transport's
     * file. ampf's AbstractGuard leaves the package's out.
     *
     * @return array<string, mixed>
     */
    protected static function configuration(string $transport): array
    {
        return MergedConfiguration::of(static::projectRoot(), $transport);
    }

    /**
     * The files below a directory of the project whose names end in the suffix, at any depth, in the order of their
     * paths. Both paths of a file are given: the one below the directory (`partials/field.html.php`), which names a
     * data set, is the key, and the one from the project's root (`views/http/partials/field.html.php`), which a message
     * names, the value.
     *
     * @return array<string, string>
     */
    protected static function filesUnder(string $directory, string $suffix): array
    {
        $root = static::projectRoot() . '/' . $directory . '/';

        if (!is_dir($root)) {
            self::fail('The project has no directory ' . $directory . '.');
        }
        $files = [];
        $tree = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

        foreach ($tree as $file) {
            assert($file instanceof SplFileInfo);

            if (!str_ends_with($file->getFilename(), $suffix)) {
                continue;
            }
            $below = substr($file->getPathname(), strlen($root));
            $files[$below] = $directory . '/' . $below;
        }
        ksort($files);

        return $files;
    }

    /** The full path of a file of the project, which has to be there; the file's path is from the project's root. */
    protected static function existingFile(string $file): string
    {
        $path = static::projectRoot() . '/' . $file;

        if (!is_file($path)) {
            self::fail('The project has no file ' . $file . '.');
        }

        return $path;
    }

    /** What a file of the project holds; the path is from the project's root. */
    protected static function contentsOf(string $file): string
    {
        $contents = file_get_contents(static::existingFile($file));
        assert(is_string($contents));

        return $contents;
    }
}
