<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use ampf\Bootstrap\ApplicationContext;
use ReflectionClass;

/**
 * An application's configuration as its entry points merge it, without the machine's config/local.php: ampf's files, the
 * package's, then the application's own (README, "How an application wires it"). The guards read it without booting
 * anything, to find what an application has configured: its routes, its assets.
 */
class MergedConfiguration
{
    /**
     * The files an entry point lists before the application's own: the framework's default.php and the transport's file,
     * then the package's default.php.
     *
     * @return list<string>
     */
    public static function packageFiles(string $transport): array
    {
        $context = new ReflectionClass(ApplicationContext::class)->getFileName();
        assert(is_string($context));
        $framework = dirname($context, 3) . '/config/';

        return [
            $framework . 'default.php',
            $framework . $transport . '.php',
            dirname(__DIR__, 3) . '/config/default.php',
        ];
    }

    /**
     * The configuration of the transport (`http` or `cli`): the files of packageFiles(), then the application's
     * default.php and the transport's file in its config directory.
     *
     * @return array<string, mixed>
     */
    public static function of(string $projectRoot, string $transport): array
    {
        $application = $projectRoot . '/config/';

        return ApplicationContext::boot([
            ...self::packageFiles($transport),
            $application . 'default.php',
            $application . $transport . '.php',
        ]);
    }
}
