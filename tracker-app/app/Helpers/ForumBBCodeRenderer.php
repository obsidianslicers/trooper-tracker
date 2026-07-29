<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\OauthLogin;
use Illuminate\Support\Str;

final class ForumBBCodeRenderer
{
    /**
     * Render a conservative subset of BBCode to safe HTML.
     *
     * This intentionally escapes all non-tag content and only emits a small
     * allowlisted set of HTML elements to avoid XSS.
     */
    public static function toHtml(string $bbcode): string
    {
        $bbcode = str_replace(["\r\n", "\r"], "\n", $bbcode);

        // Strip unsupported attributes (e.g. unfurl="true") from [url] tags so the
        // core href/label matching below isn't broken by extra attributes.
        $bbcode = preg_replace(
            '~\[url(=[^\]]*?)?((?:\s+[a-zA-Z_][\w:-]*=(?:"[^"]*"|\'[^\']*\'))+)\]~i',
            '[url$1]',
            $bbcode
        ) ?? $bbcode;

        $codeBlocks = [];
        $bbcode = preg_replace_callback(
            '~\[code\](.*?)\[/code\]~si',
            static function (array $matches) use (&$codeBlocks): string {
                $token = '@@CODE_BLOCK_'.count($codeBlocks).'@@';

                $codeBlocks[$token] = '<pre class="mb-0"><code>'.e($matches[1]).'</code></pre>';

                return $token;
            },
            $bbcode
        ) ?? $bbcode;

        // Escape everything first. From this point onward we only add HTML we control.
        $html = e($bbcode);

        // Basic inline formatting.
        $html = self::replacePairedTag($html, 'b', 'strong');
        $html = self::replacePairedTag($html, 'i', 'em');
        $html = self::replacePairedTag($html, 'u', 'u');
        $html = self::replacePairedTag($html, 's', 's');

        // User mentions.
        $resolved = [];
        $html = preg_replace_callback(
            '~\[USER=(\d+)\](.*?)\[/USER\]~si',
            static function (array $matches) use (&$resolved): string {
                $xenforo_id = $matches[1];
                $display_name = $matches[2];

                if (! array_key_exists($xenforo_id, $resolved))
                {
                    $resolved[$xenforo_id] = OauthLogin::query()
                        ->where('provider', 'xenforo')
                        ->where('provider_id', $xenforo_id)
                        ->value('trooper_id');
                }

                $href = self::resolveUserMentionHref($resolved[$xenforo_id], $xenforo_id);

                return $href !== null
                    ? '<a href="'.$href.'" target="_blank" rel="noopener noreferrer">'.$display_name.'</a>'
                    : $display_name;
            },
            $html
        ) ?? $html;

        // Quotes.
        $html = preg_replace_callback(
            '~\[quote(?:=(?:"|&quot;)?([^\]]+?)(?:"|&quot;)?)?\]~i',
            static function (array $matches): string {
                $attribution = isset($matches[1])
                    ? self::quoteAttribution($matches[1])
                    : null;

                $header = $attribution !== null
                    ? '<div class="small text-muted mb-1">'.$attribution.' said:</div>'
                    : '';

                return '<blockquote class="border-start ps-3 my-2">'.$header;
            },
            $html
        ) ?? $html;
        $html = preg_replace('~\[/quote\]~i', '</blockquote>', $html) ?? $html;

        // URLs.
        $html = preg_replace_callback(
            '~\[url=(.*?)\](.*?)\[/url\]~si',
            static function (array $matches): string {
                $urlRaw = html_entity_decode($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $label = $matches[2];

                $href = self::sanitizeUrl($urlRaw);
                if ($href === null)
                {
                    return $label;
                }

                return '<a href="'.e($href).'" target="_blank" rel="noopener noreferrer">'.$label.'</a>';
            },
            $html
        ) ?? $html;

        $html = preg_replace_callback(
            '~\[url\](.*?)\[/url\]~si',
            static function (array $matches): string {
                $urlRaw = html_entity_decode($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $href = self::sanitizeUrl($urlRaw);
                if ($href === null)
                {
                    // Leave as-is (escaped) if not a safe URL.
                    return $matches[1];
                }

                $label = e($href);

                return '<a href="'.e($href).'" target="_blank" rel="noopener noreferrer">'.$label.'</a>';
            },
            $html
        ) ?? $html;

        // Email links.
        $html = preg_replace_callback(
            '~\[email(?:=(?:"|&quot;|\'|&\#039;)?([^\]]+?)(?:"|&quot;|\'|&\#039;)?)?\](.*?)\[/email\]~si',
            static function (array $matches): string {
                $addressRaw = $matches[1] !== '' ? $matches[1] : $matches[2];
                $address = self::sanitizeEmail(html_entity_decode($addressRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

                if ($address === null)
                {
                    return $matches[2];
                }

                return '<a href="mailto:'.e($address).'">'.$matches[2].'</a>';
            },
            $html
        ) ?? $html;

        // Color.
        $html = preg_replace_callback(
            '~\[color=(?:"|&quot;|\'|&\#039;)?([^\]]+?)(?:"|&quot;|\'|&\#039;)?\](.*?)\[/color\]~si',
            static function (array $matches): string {
                $color = self::sanitizeColor(html_entity_decode($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

                return $color === null
                    ? $matches[2]
                    : '<span style="color:'.e($color).'">'.$matches[2].'</span>';
            },
            $html
        ) ?? $html;

        // Size.
        $html = preg_replace_callback(
            '~\[size=(\d+)\](.*?)\[/size\]~si',
            static function (array $matches): string {
                $em = self::sizeToEm((int) $matches[1]);

                return '<span style="font-size:'.$em.'em">'.$matches[2].'</span>';
            },
            $html
        ) ?? $html;

        // Horizontal rule.
        $html = preg_replace('~\[hr\](?:\[/hr\])?~i', '<hr>', $html) ?? $html;

        // Images.
        $html = preg_replace_callback(
            '~\[img\](.*?)\[/img\]~si',
            static function (array $matches): string {
                $urlRaw = html_entity_decode($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $src = self::sanitizeUrl(trim($urlRaw));
                if ($src === null)
                {
                    return '';
                }

                return '<img src="'.e($src).'" alt="" class="img-fluid" style="max-width:100%;" loading="lazy">';
            },
            $html
        ) ?? $html;

        // Convert newlines after tag replacement.
        $html = nl2br($html, false);

        // Restore code blocks.
        if (! empty($codeBlocks))
        {
            $html = strtr($html, $codeBlocks);
        }

        // Very small nicety: collapse excessive <br>.
        $html = preg_replace('~(<br\s*/?>\s*){3,}~i', '<br><br>', $html) ?? $html;

        return $html;
    }

    private static function resolveUserMentionHref(?int $trooper_id, string $xenforo_id): ?string
    {
        if ($trooper_id !== null)
        {
            return e(route('service-records.trooper', $trooper_id));
        }

        $base = rtrim((string) config('services.xenforo.base_url', ''), '/');

        return $base !== '' ? e($base.'/index.php?members/'.$xenforo_id.'/') : null;
    }

    private static function replacePairedTag(string $html, string $bbTag, string $htmlTag): string
    {
        // Apply a few times to handle simple nesting.
        for ($i = 0; $i < 5; $i++)
        {
            $next = preg_replace(
                '~\['.preg_quote($bbTag, '~').'\](.*?)\[/'.preg_quote($bbTag, '~').'\]~si',
                '<'.$htmlTag.'>$1</'.$htmlTag.'>',
                $html
            );

            if ($next === null || $next === $html)
            {
                break;
            }

            $html = $next;
        }

        return $html;
    }

    private static function sanitizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '')
        {
            return null;
        }

        // XenForo often uses absolute URLs; only allow http(s).
        if (! Str::startsWith(Str::lower($url), ['http://', 'https://']))
        {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    private static function sanitizeColor(string $color): ?string
    {
        $color = trim($color);

        $isHex = (bool) preg_match('~^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$~', $color);
        $isRgb = (bool) preg_match('~^rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)$~i', $color);
        $isNamed = (bool) preg_match('~^[a-zA-Z]{3,20}$~', $color);

        return ($isHex || $isRgb || $isNamed) ? $color : null;
    }

    private static function sanitizeEmail(string $address): ?string
    {
        $address = trim($address);

        return filter_var($address, FILTER_VALIDATE_EMAIL) ?: null;
    }

    private static function sizeToEm(int $size): float
    {
        $scale = [1 => 0.7, 2 => 0.8, 3 => 0.9, 4 => 1.0, 5 => 1.2, 6 => 1.5, 7 => 2.0];

        return $scale[$size] ?? 1.0;
    }

    private static function quoteAttribution(string $attribution): ?string
    {
        $attribution = html_entity_decode($attribution, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $attribution = trim($attribution, " \t\n\r\0\x0B\"'");

        if ($attribution === '')
        {
            return null;
        }

        $name = trim(explode(',', $attribution, 2)[0]);

        return $name === '' ? null : e($name);
    }
}
