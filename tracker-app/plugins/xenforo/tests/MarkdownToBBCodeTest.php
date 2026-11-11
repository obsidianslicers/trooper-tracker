<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Support\MarkdownToBBCode;

class MarkdownToBBCodeTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function conversions(): array
    {
        return [
            'plain text is left alone' => ["Children's Hall & \"Hangar\" <3", "Children's Hall & \"Hangar\" <3"],
            'a bracket is shown as written' => ['Bring [b]armor[/b]', 'Bring [plain][[/plain]b]armor[plain][[/plain]/b]'],
            'emphasis' => ['**bold** *italic* ~~gone~~', '[b]bold[/b] [i]italic[/i] [s]gone[/s]'],
            'inline code keeps its brackets' => ['use `[b]` here', 'use [icode][b][/icode] here'],
            'a single line break joins the lines' => ["one\ntwo", 'one two'],
            'a hard line break stays' => ["one\\\ntwo", "one\ntwo"],
            'paragraphs' => ["one\n\ntwo", "one\n\ntwo"],
            'headings' => ["# Big\n\n## Less\n\n### Bold", "[size=6][b]Big[/b][/size]\n\n[size=5][b]Less[/b][/size]\n\n[b]Bold[/b]"],
            'quote' => ['> Quoted **text**', '[quote]Quoted [b]text[/b][/quote]'],
            'code block' => ["```\n[b]raw[/b]\n```", '[code][b]raw[/b][/code]'],
            'rule' => ["above\n\n---\n\nbelow", "above\n\n[hr][/hr]\n\nbelow"],
            'bullet list' => ["- one\n- two", "[list]\n[*]one\n[*]two\n[/list]"],
            'numbered list' => ["1. one\n2. two", "[list=1]\n[*]one\n[*]two\n[/list]"],
            'nested list' => ["- one\n  1. inner", "[list]\n[*]one\n[list=1]\n[*]inner\n[/list]\n[/list]"],
            'task list' => ['- [x] done', "[list]\n[*]☑ done\n[/list]"],
            'link' => ['[the lot](https://example.org/lot?a=1&b=2)', '[url="https://example.org/lot?a=1&b=2"]the lot[/url]'],
            'a quote in a link cannot end the tag' => ['[x](https://example.org/"q")', '[url="https://example.org/%22q%22"]x[/url]'],
            'bare address' => ['see https://example.org', 'see [url="https://example.org"]https://example.org[/url]'],
            'image' => ['![map](https://example.org/map.png)', '[img]https://example.org/map.png[/img]'],
            'table' => [
                "| Time | What |\n|---|:---:|\n| 9:30 | **Check in** |\n| 10:00 | Parade [route] |",
                "[table]\n[tr][th]Time[/th][th]What[/th][/tr]\n[tr][td]9:30[/td][td][b]Check in[/b][/td][/tr]\n"
                ."[tr][td]10:00[/td][td]Parade [plain][[/plain]route][/td][/tr]\n[/table]",
            ],
            //  TT1.0's BBCode import writes these
            'html underline and line break' => ['<u>under</u><br>next', "[u]under[/u]\nnext"],
            'html color and size' => [
                '<span style="color:red">Red <span style="font-size:22px">big</span></span> <span style="font-size:12pt">pt</span>',
                '[color=red]Red [size=22px]big[/size][/color] [size=16px]pt[/size]',
            ],
            'other html keeps its text' => ['<font face="x">Text</font> &amp; more', 'Text & more'],
            'html left open closes with its paragraph' => ["<b>bold\n\nnext", "[b]bold[/b]\n\nnext"],
            'a closing tag with nothing open is dropped' => ['text</u>', 'text'],
            'an html block' => ["<div>\nIn <i>a</i> block &amp; [x]\n</div>", 'In [i]a[/i] block & [plain][[/plain]x]'],
        ];
    }

    #[DataProvider('conversions')]
    public function test_markdown_becomes_bbcode(string $markdown, string $expected): void
    {
        $this->assertSame($expected, MarkdownToBBCode::convert($markdown));
    }

    public function test_nothing_converts_to_nothing(): void
    {
        $this->assertSame('', MarkdownToBBCode::convert(null));
        $this->assertSame('', MarkdownToBBCode::convert("  \n "));
        $this->assertSame('', MarkdownToBBCode::convert('<!-- a note -->'));
    }

    public function test_escape_only_touches_brackets(): void
    {
        $this->assertSame('A & B < "C"', MarkdownToBBCode::escape('A & B < "C"'));
        $this->assertSame('[plain][[/plain]1]', MarkdownToBBCode::escape('[1]'));
        $this->assertSame('', MarkdownToBBCode::escape(null));
    }
}
