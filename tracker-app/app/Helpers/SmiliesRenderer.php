<?php

declare(strict_types=1);

namespace App\Helpers;

final class SmiliesRenderer
{
    /**
     * Replace smiley text codes in rendered HTML with <img> tags.
     *
     * Only processes text nodes — skips content inside HTML tags and inside
     * <pre>/<code> blocks. Uses strtr() which automatically tries the longest
     * key first, so ":--)" won't be partially matched by ":-)".
     *
     * Smiley codes are HTML-escaped before use as search keys because the
     * input HTML has already been escaped (e.g. ":->" appears as ":-&gt;").
     *
     * @param string $html    Already-rendered HTML (post BBCode or Markdown)
     * @param array  $smilies Array of smiley data returned by XenforoService::get_smilies()
     */
    public static function render(string $html, array $smilies): string
    {
        if ($html === '' || empty($smilies))
        {
            return $html;
        }

        $map = self::buildMap($smilies);
        if (empty($map))
        {
            return $html;
        }

        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false)
        {
            return $html;
        }

        $result = [];
        $depth = 0;

        foreach ($parts as $part)
        {
            if (preg_match('/^<(pre|code)(\s|>)/i', $part))
            {
                $depth++;
                $result[] = $part;
            }
            elseif ($depth > 0 && preg_match('/^<\/(pre|code)>/i', $part))
            {
                $depth--;
                $result[] = $part;
            }
            elseif (str_starts_with($part, '<'))
            {
                $result[] = $part;
            }
            elseif ($depth === 0)
            {
                $result[] = strtr($part, $map);
            }
            else
            {
                $result[] = $part;
            }
        }

        return implode('', $result);
    }

    /**
     * @param array $smilies
     * @return array<string, string>  escaped_code => <img> tag
     */
    private static function buildMap(array $smilies): array
    {
        $map = [];

        foreach ($smilies as $smiley)
        {
            if (!is_array($smiley) || empty($smiley['image_url']))
            {
                continue;
            }

            $imgTag = self::buildImgTag($smiley);

            foreach ((array) ($smiley['smilie_text'] ?? []) as $code)
            {
                $code = (string) $code;
                if ($code === '')
                {
                    continue;
                }

                $map[e($code)] = $imgTag;
            }
        }

        return $map;
    }

    private static function buildImgTag(array $smiley): string
    {
        $src = e((string) ($smiley['image_url'] ?? ''));
        $title = e((string) ($smiley['title'] ?? ''));

        return '<img src="'.$src.'" alt="'.$title.'" title="'.$title.'" class="forum-smiley" loading="lazy">';
    }
}
