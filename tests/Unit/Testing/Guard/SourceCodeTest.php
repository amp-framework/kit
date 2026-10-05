<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Unit\Testing\Guard;

use ampf\Kit\Testing\Guard\SourceCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * What the guards read in the code of a file by its tokens: the attributes that a class carries, and whether the code
 * hands a class out as a class-string, which is how an entity gets to the entity manager and into a query.
 */
#[CoversClass(SourceCode::class)]
final class SourceCodeTest extends TestCase
{
    /** @return iterable<string, array{string, list<string>}> code, and the names of the attributes in it */
    public static function attributes(): iterable
    {
        yield 'no attribute' => ['<?php final class A {}', []];
        yield 'one' => ['<?php #[ORM\Entity] final class A {}', ['ORM\Entity']];
        yield 'one without a namespace' => ['<?php #[Entity] final class A {}', ['Entity']];
        yield 'one with a full name' => ['<?php #[\Doctrine\ORM\Mapping\Entity] final class A {}', ['\Doctrine\ORM\Mapping\Entity']];
        yield 'one with arguments' => [
            '<?php #[ORM\Entity(repositoryClass: NoteRepo::class)] final class A {}',
            ['ORM\Entity'],
        ];
        yield 'two, one after the other' => [
            "<?php\n#[ORM\\Entity(repositoryClass: NoteRepo::class)]\n#[ORM\\Table(name: 'notes')]\nfinal class A {}",
            ['ORM\Entity', 'ORM\Table'],
        ];
        yield 'two in a group' => ['<?php #[ORM\Table(name: "a"), ORM\Entity] final class A {}', ['ORM\Table', 'ORM\Entity']];
        yield 'arguments with brackets and calls' => [
            '<?php #[ORM\Table(name: "a", indexes: [new ORM\Index(columns: ["b", "c"])]), ORM\Entity] final class A {}',
            ['ORM\Table', 'ORM\Entity'],
        ];
        yield 'on a property and a method' => [
            '<?php final class A { #[ORM\Column(type: "string")] private string $a; #[Override] public function b() {} }',
            ['ORM\Column', 'Override'],
        ];
        yield 'brackets of the code between attributes' => [
            '<?php $a = [1, [2]]; foo($a[0]); #[ORM\Entity] final class A {} $b = [3]; #[ORM\Table] final class B {}',
            ['ORM\Entity', 'ORM\Table'],
        ];
        yield 'a name in a comment, a string and the code' => [
            "<?php // #[ORM\\Entity]\n/* #[ORM\\Entity] */ \$a = '#[ORM\\Entity]'; \$b = ORM\\Entity::class;",
            [],
        ];
    }

    /** @return iterable<string, array{string, bool}> code that names the class `NoteEntity`, and whether it hands it out */
    public static function classStrings(): iterable
    {
        yield 'the class constant' => ['<?php $em->find(NoteEntity::class, $id);', true];
        yield 'the class constant with a namespace' => ['<?php $em->find(Doctrine\Entity\NoteEntity::class, $id);', true];
        yield 'the class constant with a full name' => ['<?php $em->find(\App\Doctrine\Entity\NoteEntity::class, $id);', true];
        yield 'the class constant of a relative name' => ['<?php $em->find(namespace\NoteEntity::class, $id);', true];
        yield 'the class constant, spaced out' => ["<?php \$em->find(NoteEntity\n    ::\n    class, \$id);", true];
        yield 'in a repository call' => ['<?php $em->getRepository(NoteEntity::class)->findBy([]);', true];
        yield 'in a query built from the class' => ["<?php \$q = 'SELECT n FROM ' . NoteEntity::class . ' n';", true];
        yield 'in a query that names the class' => ["<?php \$q = 'SELECT n FROM App\\Entity\\NoteEntity n';", true];
        yield 'in a query in double quotes' => ['<?php $q = "SELECT n FROM App\Entity\NoteEntity n WHERE n.user = :user";', true];
        yield 'in a query with a variable' => ['<?php $q = "SELECT n FROM App\Entity\NoteEntity n WHERE n.id = $id";', true];
        yield 'in a query in a heredoc' => ["<?php \$q = <<<DQL\nSELECT n FROM NoteEntity n\nDQL;", true];
        yield 'in a query in a nowdoc' => ["<?php \$q = <<<'DQL'\nSELECT n FROM NoteEntity n\nDQL;", true];
        yield 'as a type' => ['<?php function a(NoteEntity $note): NoteEntity { return $note; }', false];
        yield 'as a check' => ['<?php if ($a instanceof NoteEntity) { return new NoteEntity(); }', false];
        yield 'in an import' => ['<?php use App\Doctrine\Entity\NoteEntity; use App\Doctrine\Entity\NoteEntity as Note;', false];
        yield 'in a comment' => ["<?php // NoteEntity::class\n/* NoteEntity::class */ /** @param NoteEntity::class \$a */ \$b = 1;", false];
        yield 'another class whose name ends like it' => ['<?php $em->find(BaseNoteEntity::class, $id);', false];
        yield 'another class whose name starts like it' => ['<?php $em->find(NoteEntityFactory::class, $id);', false];
        yield 'a string with another name' => ['<?php $q = "SELECT n FROM App\Entity\BaseNoteEntity n";', false];
        yield 'the class of an object' => ['<?php $a = $note::class; $b = static::class;', false];
        yield 'a constant of the class' => ['<?php $a = NoteEntity::LIMIT; $b = NoteEntity::create();', false];
        yield 'in the attributes of the mapping' => [
            '<?php #[ORM\OneToMany(targetEntity: NoteEntity::class, mappedBy: "shelf")] private Collection $notes;',
            false,
        ];
        yield 'in an attribute with a string' => ['<?php #[Mapping(target: "App\Entity\NoteEntity")] private $a;', false];
        yield 'after the attributes of the mapping' => [
            '<?php #[ORM\OneToMany(targetEntity: NoteEntity::class, mappedBy: "shelf")] private Collection $a; '
            . 'public function b() { return $this->em->find(NoteEntity::class, 1); }',
            true,
        ];
        yield 'in the attributes, then the arguments of a call that look alike' => [
            '<?php #[ORM\Table(indexes: [new ORM\Index(columns: ["a"])])] private $a; $b = [NoteEntity::class];',
            true,
        ];
        yield 'a name at the very end of the code' => ['<?php return NoteEntity', false];
        yield 'a name and then the end of the code after the operator' => ['<?php return NoteEntity::', false];
        yield 'no code' => ['', false];
    }

    /** @return iterable<string, array{string, string}> a name as an attribute writes it, and its last part */
    public static function names(): iterable
    {
        yield 'a short name' => ['Entity', 'Entity'];
        yield 'a name with a namespace' => ['ORM\Entity', 'Entity'];
        yield 'a full name' => ['\Doctrine\ORM\Mapping\Entity', 'Entity'];
        yield 'a relative name' => ['namespace\Entity', 'Entity'];
    }

    /**
     * @param list<string> $names
     */
    #[DataProvider('attributes')]
    public function testTheAttributesOfTheCodeAreNamedAsTheyAreWritten(string $code, array $names): void
    {
        self::assertSame($names, SourceCode::attributeNames($code));
    }

    #[DataProvider('classStrings')]
    public function testACodeHandsAClassOutWhenItWritesItsClassStringOutsideTheAttributes(
        string $code,
        bool $handsOut,
    ): void {
        self::assertSame($handsOut, SourceCode::handsOut($code, 'NoteEntity'));
    }

    #[DataProvider('names')]
    public function testTheShortNameIsTheLastPartOfAName(string $name, string $short): void
    {
        self::assertSame($short, SourceCode::shortName($name));
    }

    public function testTheClassToHandOutIsGivenWithItsNamespaceOrWithout(): void
    {
        $code = '<?php $em->find(NoteEntity::class, $id);';

        self::assertTrue(SourceCode::handsOut($code, 'App\Doctrine\Entity\NoteEntity'));
        self::assertTrue(SourceCode::handsOut($code, '\App\Doctrine\Entity\NoteEntity'));
        self::assertFalse(SourceCode::handsOut($code, 'App\Doctrine\Entity\TagEntity'));
    }
}
