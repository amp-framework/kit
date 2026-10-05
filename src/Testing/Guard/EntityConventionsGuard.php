<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use ampf\Doctrine\Repository\AbstractRepo;
use ampf\Kit\Doctrine\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

/**
 * What every entity has in common, held for the ones to come: it extends the package's BaseEntity (a UUID of its own),
 * it names a repository of ampf's kind, and its table is named and on the one character set and collation of the
 * schema (`BaseEntity::TABLE_OPTIONS`); a mapped superclass (`#[ORM\MappedSuperclass]`) has neither repository nor
 * table.
 *
 * The entities are the classes below `Doctrine/Entity` of the source directory whose files end in `Entity.php`, and
 * those whose files declare `#[ORM\Entity]` or `#[ORM\MappedSuperclass]` under any name: the mapping takes every class
 * with such an attribute. An application extends the guard in one small class that names its project root and its
 * namespace.
 */
abstract class EntityConventionsGuard extends AbstractSourceGuard
{
    /**
     * Every entity but the package's own base: the files below the entities' directory that are named as an entity or
     * declare one.
     *
     * @return iterable<string, array{string, string}> the entity's type and the file that declares it, from the project's root
     */
    public static function entities(): iterable
    {
        foreach (static::sourceTypes(self::entityPath(), '.php') as $file => $type) {
            if ($type !== BaseEntity::class && (str_ends_with($file, 'Entity.php') || self::declaresMapping($file))) {
                yield $file => [$type, $file];
            }
        }
    }

    /** The directory of the entities, relative to the source directory. */
    protected static function entityDirectory(): string
    {
        return 'Doctrine/Entity';
    }

    /** The directory of the entities, from the project's root. */
    private static function entityPath(): string
    {
        return static::sourceDirectory() . '/' . static::entityDirectory();
    }

    /** Whether the file declares an entity or a mapped superclass, whatever it is named: the mapping takes it. */
    private static function declaresMapping(string $file): bool
    {
        return array_any(
            SourceCode::attributeNames(static::contentsOf($file)),
            static fn (string $name): bool => in_array(
                SourceCode::shortName($name),
                ['Entity', 'MappedSuperclass'],
                true,
            ),
        );
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private static function isMappedSuperclass(ReflectionClass $class): bool
    {
        return $class->getAttributes(ORM\MappedSuperclass::class) !== [];
    }

    public function testThereIsAnEntityToCheck(): void
    {
        $problems = [];

        if (iterator_to_array(static::entities()) === []) {
            $problems[] = 'No entity is in ' . self::entityPath() . ' (a file *Entity.php, or one that declares #[ORM\Entity] or'
                . ' #[ORM\MappedSuperclass]): the guard has nothing to check.';
        }

        $this->assertNoProblems($problems);
    }

    #[DataProvider('entities', true, true)]
    public function testTheEntityIsABaseEntity(string $type, string $file): void
    {
        $problems = [];

        if (!static::reflect($type, $file)->isSubclassOf(BaseEntity::class)) {
            $problems[] = $type . ' is no ' . BaseEntity::class . ': every entity extends it, for its UUID.';
        }

        $this->assertNoProblems($problems);
    }

    #[DataProvider('entities', true, true)]
    public function testTheEntityNamesAnAmpfRepository(string $type, string $file): void
    {
        $class = static::reflect($type, $file);
        $entity = ($class->getAttributes(ORM\Entity::class)[0] ?? null)?->newInstance();
        $problems = [];

        if (self::isMappedSuperclass($class)) {
            // A mapped superclass is no entity of its own
        } elseif ($entity === null) {
            $problems[] = $type . ' has no #[ORM\Entity] attribute.';
        } elseif ($entity->repositoryClass === null) {
            $problems[] = $type . ' names no repository.';
        } elseif (!is_subclass_of($entity->repositoryClass, AbstractRepo::class)) {
            $problems[] = $type . ' names the repository ' . $entity->repositoryClass . ', which is no '
                . AbstractRepo::class . '.';
        }

        $this->assertNoProblems($problems);
    }

    #[DataProvider('entities', true, true)]
    public function testTheTableIsNamedAndOnTheOneCharacterSetAndCollation(string $type, string $file): void
    {
        $class = static::reflect($type, $file);
        $table = ($class->getAttributes(ORM\Table::class)[0] ?? null)?->newInstance();
        $problems = [];

        if (self::isMappedSuperclass($class)) {
            // A mapped superclass has no table
        } elseif ($table === null) {
            $problems[] = $type . ' has no #[ORM\Table] attribute.';
        } else {
            if ($table->name === null) {
                $problems[] = $type . ' names no table.';
            }

            if ($table->options !== BaseEntity::TABLE_OPTIONS) {
                $problems[] = $type . ' is not on ' . BaseEntity::class . '::TABLE_OPTIONS, the one character set and'
                    . ' collation of every table.';
            }
        }

        $this->assertNoProblems($problems);
    }
}
