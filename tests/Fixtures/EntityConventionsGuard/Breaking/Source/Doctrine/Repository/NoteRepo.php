<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Repository;

use ampf\Doctrine\Repository\AbstractRepo;

/**
 * @template-extends \ampf\Doctrine\Repository\AbstractRepo<\ampf\Doctrine\Entity\AbstractEntity>
 */
final class NoteRepo extends AbstractRepo
{
}
