<?php

declare(strict_types=1);

namespace ampf\Kit\Testing\Guard;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;
use Dom\Text;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A template holds markup and PHP, never a text of its own: everything a page says is a key of the translation files,
 * so that a language is a file and nothing else. The text of an element, and the attributes that a person reads or
 * hears (`alt`, `title`, `label`, `aria-label`, `aria-description`, `aria-placeholder`, `aria-roledescription`,
 * `aria-valuetext`, `placeholder`, `value`, and a meta tag's `content` for the page's description), must come from PHP.
 * The content of a `<template>` element counts too, though the parser keeps it apart from the element's children. What a
 * template may say itself are the marks that mean the same in every language (`·`, `|`, `/`, `*`) and, among the
 * literals an application allows, the pieces of its wordmark:
 *
 *     protected static function allowedLiterals(): array { return [...parent::allowedLiterals(), 'Tank', 'weise']; }
 */
abstract class TemplateTextGuard extends AbstractTemplateGuard
{
    /** The attributes whose value a person reads or hears. */
    protected const array TEXT_ATTRIBUTES = [
        'alt',
        'title',
        'label',
        'aria-label',
        'aria-description',
        'aria-placeholder',
        'aria-roledescription',
        'aria-valuetext',
        'placeholder',
        'value',
    ];

    /** The names of the meta tags whose content a person reads (what a search engine shows of the page). */
    protected const array TEXT_META = ['description'];

    /** What stands in the markup the guard looks at where PHP printed something: no text of a template holds it. */
    private const string PRINTED = "\u{27E6}php\u{27E7}";

    /**
     * The literals a template may say itself, whole: the marks that mean the same in every language, and the pieces of
     * the application's wordmark in an override.
     *
     * @return list<string>
     */
    protected static function allowedLiterals(): array
    {
        return ['·', '|', '/', '*'];
    }

    /**
     * The texts that the template says itself, in the order of the document: the text nodes and the attributes of the
     * elements, trimmed, without what PHP prints and without the allowed literals.
     *
     * @return list<string>
     */
    protected static function ownTexts(string $template): array
    {
        // A template is often a part of a page: the parser makes the rest, and its complaints about that are not ours
        $document = HTMLDocument::createFromString(static::withoutPhp($template, self::PRINTED), LIBXML_NOERROR);

        return array_values(array_diff(self::literals($document), static::allowedLiterals()));
    }

    /**
     * The literal texts of the node and the nodes below it.
     *
     * @return array<string>
     */
    private static function literals(Node $node): array
    {
        $literals = [];

        if ($node instanceof Text) {
            $literals[] = self::literal($node->data);
        }

        if ($node instanceof Element) {
            foreach ($node->attributes as $attribute) {
                $isText = in_array($attribute->name, static::TEXT_ATTRIBUTES, true)
                    || (
                        $node->tagName === 'META'
                        && $attribute->name === 'content'
                        && in_array($node->getAttribute('name'), static::TEXT_META, true)
                    );

                if ($isText) {
                    $literals[] = self::literal($attribute->value);
                }
            }
        }

        if ($node instanceof Element && $node->tagName === 'TEMPLATE') {
            $literals = [...$literals, ...self::literals(self::contentOf($node))];
        }

        foreach ($node->childNodes as $child) {
            $literals = [...$literals, ...self::literals($child)];
        }

        return array_filter($literals, static fn (string $text): bool => $text !== '');
    }

    /**
     * The content of a template element, which the parser keeps apart from the element's children, parsed again: inside a
     * table (which the end of the markup closes), so that the rows and the cells of a row template keep their tags and
     * with them their attributes.
     */
    private static function contentOf(Element $template): HTMLDocument
    {
        return HTMLDocument::createFromString('<table>' . $template->innerHTML, LIBXML_NOERROR);
    }

    private static function literal(string $text): string
    {
        return trim(str_replace(self::PRINTED, '', $text));
    }

    #[DataProvider('templates', true, true)]
    public function testTheTemplateSaysNothingItself(string $file): void
    {
        $own = static::ownTexts(static::contentsOf($file));
        $problems = [];

        if ($own !== []) {
            $texts = implode('", "', $own);
            $problems[] = 'The template ' . $file . ' has texts of its own: "' . $texts . '". Make them keys of the'
                . ' translation files.';
        }

        $this->assertNoProblems($problems);
    }
}
