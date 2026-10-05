<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Doctrine\Entity;

use ampf\Kit\Doctrine\Entity\BaseEntity;
use ampf\Kit\Helper\Uuid;
use PHPUnit\Framework\TestCase;

/** What every entity has: a UUID that is assigned when the object is made. */
final class BaseEntityTest extends TestCase
{
    public function testTheIdIsAUuidAssignedAtCreationAndKept(): void
    {
        $entity = new class extends BaseEntity {
        };

        self::assertTrue(Uuid::isValid($entity->getId()));
        self::assertSame($entity->getId(), $entity->getId());
    }

    public function testEveryEntityHasAnIdOfItsOwn(): void
    {
        $first = new class extends BaseEntity {
        };
        $second = new class extends BaseEntity {
        };

        self::assertNotSame($first->getId(), $second->getId());
    }
}
