<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Support;

use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\Strikethrough\Strikethrough;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableCell;
use League\CommonMark\Extension\Table\TableRow;
use League\CommonMark\Extension\Table\TableSection;
use League\CommonMark\Extension\TaskList\TaskListItemMarker;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;

/**
 * Turns the Markdown that event comments and amenities are written in into XenForo BBCode,
 * so a forum post shows what the event page shows: tables, lists, bold, links and so on.
 *
 * It parses with the same GitHub-flavored rules as Str::markdown(), which renders the event
 * page. Text is escaped for BBCode, not HTML: XenForo escapes HTML itself, so e() would
 * show "&amp;" in the post. Inline HTML (TT1.0 imports carry <u>, <br> and color or size
 * spans) keeps the tags BBCode has and drops the rest, keeping their text.
 */
final class MarkdownToBBCode
{
    /**
     * HTML tags that have a BBCode twin.
     */
    private const HTML_TAGS = [
        'b' => 'b', 'strong' => 'b',
        'i' => 'i', 'em' => 'i',
        'u' => 'u', 'ins' => 'u',
        's' => 's', 'del' => 's', 'strike' => 's',
    ];

    /**
     * Closing BBCode for each HTML tag still open, innermost last.
     *
     * @var list<array{string, string}>
     */
    private array $open_html = [];

    public static function convert(?string $markdown): string
    {
        $markdown = trim($markdown ?? '');

        if ($markdown === '')
        {
            return '';
        }

        $document = (new MarkdownParser((new GithubFlavoredMarkdownConverter)->getEnvironment()))->parse($markdown);

        $converter = new self;

        return trim($converter->blocks($document).$converter->closeAllHtml());
    }

    /**
     * Plain text as BBCode: a "[" that could start a tag is shown as it is.
     */
    public static function escape(?string $text): string
    {
        return str_replace('[', '[plain][[/plain]', $text ?? '');
    }

    private function blocks(Node $parent, string $separator = "\n\n"): string
    {
        $parts = [];

        foreach ($parent->children() as $child)
        {
            $part = $child instanceof AbstractBlock ? $this->block($child) : $this->inline($child);

            if (trim($part) !== '')
            {
                $parts[] = $part;
            }
        }

        return implode($separator, $parts);
    }

    private function block(AbstractBlock $block): string
    {
        return match (true)
        {
            //  HTML left open closes with its paragraph, as in a browser
            $block instanceof Paragraph => $this->inlines($block).$this->closeAllHtml(),
            $block instanceof Heading => $this->heading($block),
            $block instanceof BlockQuote => '[quote]'.$this->blocks($block).'[/quote]',
            $block instanceof ListBlock => $this->list($block),
            $block instanceof ListItem => '[*]'.$this->blocks($block, "\n"),
            $block instanceof FencedCode, $block instanceof IndentedCode => '[code]'.rtrim($block->getLiteral(), "\n").'[/code]',
            $block instanceof ThematicBreak => '[hr][/hr]',
            $block instanceof HtmlBlock => trim($this->html($block->getLiteral())),
            $block instanceof Table => $this->table($block),
            default => $this->blocks($block),
        };
    }

    private function inlines(Node $parent): string
    {
        $bb = '';

        foreach ($parent->children() as $child)
        {
            $bb .= $this->inline($child);
        }

        return $bb;
    }

    private function inline(Node $node): string
    {
        return match (true)
        {
            $node instanceof Text => self::escape($node->getLiteral()),
            //  a single line break in Markdown joins the lines, as on the event page
            $node instanceof Newline => $node->getType() === Newline::HARDBREAK ? "\n" : ' ',
            $node instanceof Strong => '[b]'.$this->inlines($node).'[/b]',
            $node instanceof Emphasis => '[i]'.$this->inlines($node).'[/i]',
            $node instanceof Strikethrough => '[s]'.$this->inlines($node).'[/s]',
            $node instanceof Code => '[icode]'.$node->getLiteral().'[/icode]',
            $node instanceof Link => '[url="'.self::url($node->getUrl()).'"]'.$this->inlines($node).'[/url]',
            $node instanceof Image => '[img]'.self::url($node->getUrl()).'[/img]',
            $node instanceof HtmlInline => $this->html($node->getLiteral()),
            $node instanceof TaskListItemMarker => $node->isChecked() ? '☑' : '☐',
            default => $this->inlines($node),
        };
    }

    private function heading(Heading $heading): string
    {
        $text = '[b]'.$this->inlines($heading).$this->closeAllHtml().'[/b]';

        return match ($heading->getLevel())
        {
            1 => '[size=6]'.$text.'[/size]',
            2 => '[size=5]'.$text.'[/size]',
            default => $text,
        };
    }

    private function list(ListBlock $list): string
    {
        $tag = $list->getListData()->type === ListBlock::TYPE_ORDERED ? '[list=1]' : '[list]';

        return $tag."\n".$this->blocks($list, "\n")."\n[/list]";
    }

    private function table(Table $table): string
    {
        $rows = [];

        foreach ($table->children() as $section)
        {
            if (!$section instanceof TableSection)
            {
                continue;
            }

            foreach ($section->children() as $row)
            {
                if (!$row instanceof TableRow)
                {
                    continue;
                }

                $cells = '';

                foreach ($row->children() as $cell)
                {
                    if ($cell instanceof TableCell)
                    {
                        $tag = $cell->getType() === TableCell::TYPE_HEADER ? 'th' : 'td';
                        $cells .= '['.$tag.']'.$this->inlines($cell).$this->closeAllHtml().'[/'.$tag.']';
                    }
                }

                $rows[] = '[tr]'.$cells.'[/tr]';
            }
        }

        return "[table]\n".implode("\n", $rows)."\n[/table]";
    }

    /**
     * HTML written into the Markdown: tags with a BBCode twin become it, <br> a line break,
     * and other tags are dropped while their text stays.
     */
    private function html(string $html): string
    {
        $bb = '';

        foreach (preg_split('/(<!--.*?-->|<[^>]*>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part)
        {
            if (!str_starts_with($part, '<'))
            {
                $bb .= self::escape(html_entity_decode($part, ENT_QUOTES | ENT_HTML5));

                continue;
            }

            if (!preg_match('#^<(/?)([a-z][a-z0-9]*)\b([^>]*)>$#i', $part, $tag))
            {
                continue;
            }

            [, $slash, $name, $attributes] = $tag;
            $name = strtolower($name);

            if ($name === 'br')
            {
                $bb .= "\n";
            }
            elseif (in_array($name, ['p', 'div'], true) && $slash === '/')
            {
                $bb .= "\n";
            }
            elseif ($slash === '/')
            {
                $bb .= $this->closeHtml($name);
            }
            elseif (isset(self::HTML_TAGS[$name]))
            {
                $bb .= $this->openHtml($name, ['['.self::HTML_TAGS[$name].']'], ['[/'.self::HTML_TAGS[$name].']']);
            }
            elseif ($name === 'span')
            {
                $bb .= $this->openSpan($attributes);
            }
        }

        return $bb;
    }

    /**
     * A span's color and font size, the two styles TT1.0's BBCode import writes.
     */
    private function openSpan(string $attributes): string
    {
        $style = preg_match('/style\s*=\s*"([^"]*)"/i', $attributes, $m) ? $m[1] : '';
        $opening = [];
        $closing = [];

        if (preg_match('/(?:^|;)\s*color\s*:\s*(#?[a-z0-9]+)\s*(?:;|$)/i', $style, $color))
        {
            $opening[] = '[color='.$color[1].']';
            array_unshift($closing, '[/color]');
        }

        if (preg_match('/font-size\s*:\s*(\d+(?:\.\d+)?)\s*(px|pt)?/i', $style, $size))
        {
            $px = strtolower($size[2] ?? '') === 'pt' ? round((float) $size[1] * 4 / 3) : (float) $size[1];
            $opening[] = '[size='.(int) $px.'px]';
            array_unshift($closing, '[/size]');
        }

        return $this->openHtml('span', $opening, $closing);
    }

    /**
     * @param  list<string>  $opening
     * @param  list<string>  $closing
     */
    private function openHtml(string $name, array $opening, array $closing): string
    {
        $this->open_html[] = [$name, implode('', $closing)];

        return implode('', $opening);
    }

    /**
     * Closes the innermost open tag of that name; a closing tag with nothing open is dropped.
     */
    private function closeHtml(string $name): string
    {
        $name = isset(self::HTML_TAGS[$name]) ? $name : ($name === 'span' ? 'span' : null);

        if ($name === null)
        {
            return '';
        }

        for ($i = count($this->open_html) - 1; $i >= 0; $i--)
        {
            if ($this->open_html[$i][0] === $name)
            {
                $closing = '';

                //  anything opened inside it closes first, so the BBCode nests
                foreach (array_reverse(array_splice($this->open_html, $i)) as [, $inner])
                {
                    $closing .= $inner;
                }

                return $closing;
            }
        }

        return '';
    }

    /**
     * Closes every HTML tag still open, innermost first, or XenForo would show its BBCode as text.
     */
    private function closeAllHtml(): string
    {
        $closing = '';

        foreach (array_reverse($this->open_html) as [, $inner])
        {
            $closing .= $inner;
        }

        $this->open_html = [];

        return $closing;
    }

    /**
     * A URL as a BBCode option: a quote or a bracket would end the tag early.
     */
    private static function url(string $url): string
    {
        return str_replace(['"', '[', ']'], ['%22', '%5B', '%5D'], $url);
    }
}
