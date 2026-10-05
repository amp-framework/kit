<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

/**
 * What every guard of the package ends in: the problems it found, said all at once.
 */
trait ReportsProblems
{
    /**
     * Passes when there is no problem and otherwise fails with all of them, a line for each: what a developer reads is
     * what is wrong and what it concerns, and nothing else.
     *
     * @param list<string> $problems
     */
    protected function assertNoProblems(array $problems): void
    {
        if ($problems !== []) {
            self::fail(implode(PHP_EOL, $problems));
        }

        $this->addToAssertionCount(1);
    }
}
