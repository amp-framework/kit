<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Doctrine\Repository\AbstractRepo;
use ampf\Kit\Doctrine\Entity\BaseEntity;
use ampf\Kit\Testing\Guard\AbstractFileGuard;
use ampf\Kit\Testing\Guard\AbstractSourceGuard;
use ampf\Kit\Testing\Guard\EntityConventionsGuard;
use ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Repository\PlainRepo;
use ampf\Kit\Tests\Support\GuardFailures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The guard over entities that extend the package's BaseEntity, name a repository of ampf's kind and a table on the one
 * character set and collation, a mapped superclass and a class that is no entity among them
 * (tests/Fixtures/EntityConventionsGuard/Abiding), and over entities that each break one rule, with a file that declares
 * another class than its path names (Breaking). A guard is a TestCase, which takes its name: PHP-CS-Fixer writes
 * `new class('name')`, PSR-12 `new class ('name')`.
 *
 * @phpcs:disable PSR12.Classes.AnonClassDeclaration.SpaceAfterKeyword
 */
#[CoversClass(AbstractFileGuard::class)]
#[CoversClass(AbstractSourceGuard::class)]
#[CoversClass(EntityConventionsGuard::class)]
final class EntityConventionsGuardTest extends TestCase
{
    private const string ABIDING = 'ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Abiding\Source\Doctrine\Entity';

    private const string BREAKING = 'ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source\Doctrine\Entity';

    private const string DIRECTORY = 'Source/Doctrine/Entity/';

    /** The guard over the entities that keep the rules. */
    private static function abiding(): EntityConventionsGuard
    {
        return new class('abiding') extends EntityConventionsGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/EntityConventionsGuard/Abiding';
            }

            protected static function sourceDirectory(): string
            {
                return 'Source';
            }

            protected static function sourceNamespace(): string
            {
                return 'ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Abiding\Source';
            }
        };
    }

    /** The guard over the entities that break them. */
    private static function breaking(): EntityConventionsGuard
    {
        return new class('breaking') extends EntityConventionsGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/EntityConventionsGuard/Breaking';
            }

            protected static function sourceDirectory(): string
            {
                return 'Source';
            }

            protected static function sourceNamespace(): string
            {
                return 'ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Breaking\Source';
            }
        };
    }

    public function testTheEntitiesAreTheClassesOfTheEntitiesDirectoryWhoseFilesAreNamedAsEntitiesOrDeclareOne(): void
    {
        $entities = self::DIRECTORY;

        self::assertSame(
            [
                $entities . 'AbstractOwnedEntity.php' => [self::ABIDING . '\AbstractOwnedEntity', $entities . 'AbstractOwnedEntity.php'],
                $entities . 'Archive/TagEntity.php' => [self::ABIDING . '\Archive\TagEntity', $entities . 'Archive/TagEntity.php'],
                $entities . 'NoteEntity.php' => [self::ABIDING . '\NoteEntity', $entities . 'NoteEntity.php'],
                $entities . 'Shelf.php' => [self::ABIDING . '\Shelf', $entities . 'Shelf.php'],
            ],
            iterator_to_array(self::abiding()::entities()),
            'Money.php, which declares no entity and is not named as one, is none.',
        );
    }

    public function testAFileThatIsNamedAsAnEntityIsOneWhetherItDeclaresOneOrNot(): void
    {
        $entities = array_keys(iterator_to_array(self::breaking()::entities()));

        self::assertContains(self::DIRECTORY . 'NoAttributeEntity.php', $entities);
        self::assertContains(
            self::DIRECTORY . 'Journal.php',
            $entities,
            'And one that declares an entity is one by that.',
        );
        self::assertContains(self::DIRECTORY . 'Ledger.php', $entities, 'Also a mapped superclass.');
    }

    public function testEntitiesThatKeepTheRulesPass(): void
    {
        $guard = self::abiding();
        $tests = [
            $guard->testTheEntityIsABaseEntity(...),
            $guard->testTheEntityNamesAnAmpfRepository(...),
            $guard->testTheTableIsNamedAndOnTheOneCharacterSetAndCollation(...),
        ];

        foreach ($tests as $test) {
            self::assertSame([], GuardFailures::of($test, $guard::entities()));
        }

        $guard->testThereIsAnEntityToCheck();

        self::assertSame(13, $guard->numberOfAssertionsPerformed());
    }

    public function testAnEntityThatIsNoBaseEntityFailsAndSoDoesAFileThatDeclaresAnotherClass(): void
    {
        $guard = self::breaking();
        $misnamed = 'The file ' . self::DIRECTORY . 'MisnamedEntity.php does not declare ' . self::BREAKING
            . '\MisnamedEntity, the type its path names.';

        self::assertSame(
            [
                self::DIRECTORY . 'MisnamedEntity.php' => $misnamed,
                self::DIRECTORY . 'NotBaseEntity.php' => self::BREAKING . '\NotBaseEntity is no ' . BaseEntity::class
                    . ': every entity extends it, for its UUID.',
            ],
            GuardFailures::of($guard->testTheEntityIsABaseEntity(...), $guard::entities()),
        );
    }

    public function testAnEntityWithoutARepositoryOfAmpfsKindFails(): void
    {
        $guard = self::breaking();
        $misnamed = 'The file ' . self::DIRECTORY . 'MisnamedEntity.php does not declare ' . self::BREAKING
            . '\MisnamedEntity, the type its path names.';

        self::assertSame(
            [
                self::DIRECTORY . 'MisnamedEntity.php' => $misnamed,
                self::DIRECTORY . 'NoAttributeEntity.php' => self::BREAKING . '\NoAttributeEntity has no #[ORM\Entity]'
                    . ' attribute.',
                self::DIRECTORY . 'NoRepositoryEntity.php' => self::BREAKING . '\NoRepositoryEntity names no repository.',
                self::DIRECTORY . 'WrongRepositoryEntity.php' => self::BREAKING . '\WrongRepositoryEntity names the'
                    . ' repository ' . PlainRepo::class . ', which is no ' . AbstractRepo::class . '.',
            ],
            GuardFailures::of($guard->testTheEntityNamesAnAmpfRepository(...), $guard::entities()),
        );
    }

    public function testAnEntityWithoutANamedTableOnTheOneCollationFails(): void
    {
        $guard = self::breaking();
        $misnamed = 'The file ' . self::DIRECTORY . 'MisnamedEntity.php does not declare ' . self::BREAKING
            . '\MisnamedEntity, the type its path names.';

        self::assertSame(
            [
                self::DIRECTORY . 'Journal.php' => self::BREAKING . '\Journal has no #[ORM\Table] attribute.',
                self::DIRECTORY . 'MisnamedEntity.php' => $misnamed,
                self::DIRECTORY . 'NamelessTableEntity.php' => self::BREAKING . '\NamelessTableEntity names no table.',
                self::DIRECTORY . 'NoAttributeEntity.php' => self::BREAKING . '\NoAttributeEntity has no #[ORM\Table]'
                    . ' attribute.',
                self::DIRECTORY . 'NoTableEntity.php' => self::BREAKING . '\NoTableEntity has no #[ORM\Table] attribute.',
                self::DIRECTORY . 'OtherCollationEntity.php' => self::BREAKING . '\OtherCollationEntity is not on '
                    . BaseEntity::class . '::TABLE_OPTIONS, the one character set and collation of every table.',
            ],
            GuardFailures::of($guard->testTheTableIsNamedAndOnTheOneCharacterSetAndCollation(...), $guard::entities()),
        );
    }

    public function testADirectoryWithoutAnEntityIsNothingToCheckAndSaysSo(): void
    {
        $guard = new class('none') extends EntityConventionsGuard {
            protected static function projectRoot(): string
            {
                return __DIR__ . '/../../../Fixtures/EntityConventionsGuard/Abiding';
            }

            protected static function sourceDirectory(): string
            {
                return 'Source';
            }

            protected static function sourceNamespace(): string
            {
                return 'ampf\Kit\Tests\Fixtures\EntityConventionsGuard\Abiding\Source';
            }

            protected static function entityDirectory(): string
            {
                return 'Doctrine/Repository';
            }
        };

        self::assertSame([], iterator_to_array($guard::entities()));
        self::assertSame(
            'No entity is in Source/Doctrine/Repository (a file *Entity.php, or one that declares #[ORM\Entity] or'
            . ' #[ORM\MappedSuperclass]): the guard has nothing to check.',
            GuardFailures::message($guard->testThereIsAnEntityToCheck(...)),
        );
    }

    public function testTheEntitiesAreInDoctrineEntityBelowTheSourceUnlessTold(): void
    {
        $guard = new class('default') extends EntityConventionsGuard {
            protected static function projectRoot(): string
            {
                return __DIR__;
            }

            protected static function sourceNamespace(): string
            {
                return 'App';
            }

            public static function directory(): string
            {
                return self::entityDirectory();
            }
        };

        self::assertSame('Doctrine/Entity', $guard::directory());
    }
}
