<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Services\Forums\XenforoService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ShowForumAttachmentController
{
    private const INLINE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __invoke(int $attachment_id, XenforoService $xenforo): Response
    {
        $attachment = $xenforo->get_attachment_data($attachment_id);

        if ($attachment === null)
        {
            abort(404);
        }

        $content_type = strtolower(trim(explode(';', $attachment['content_type'])[0]));
        $is_inline = in_array($content_type, self::INLINE_TYPES, true);
        $filename = $attachment['filename'] ?? 'attachment-'.$attachment_id;
        $disposition = HeaderUtils::makeDisposition(
            $is_inline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename,
            str_replace(['%', '/', '\\'], '', Str::ascii($filename))
        );

        // Anything that isn't a plain raster image is downloaded and sandboxed so
        // forum uploads (HTML, SVG, ...) can't run script on the tracker's origin.
        return response($attachment['content'], 200, [
            'Content-Type' => $is_inline ? $content_type : 'application/octet-stream',
            'Content-Disposition' => $disposition,
            'Content-Security-Policy' => 'sandbox',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
