<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pages;

use App\Messages\Api\PageData\PublicPhotosPageData;
use App\Messages\Api\PageData\PublicRosterPageData;
use App\Messages\Api\PageData\PublicTrooperHistoryPageData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Provides a public API for use by external developers.
 *
 * @author Matthew Drennan
 */
class ShowPublicApiController
{
    public function __invoke(Request $request): JsonResponse|Response
    {
        if ($request->has('trooperid') || $request->has('tkid'))
        {
            return $this->trooperHistory($request);
        }

        if ($request->has('roster'))
        {
            return $this->roster($request);
        }

        if ($request->has('photos') && $request->has('amount'))
        {
            return $this->photos($request);
        }

        return response()->json([]);
    }

    private function trooperHistory(Request $request): JsonResponse
    {
        $data = PublicTrooperHistoryPageData::call(
            trooper_id: $request->has('trooperid') ? $request->integer('trooperid') : null,
            identifier: $request->input('tkid'),
            organization_id: $request->has('squad') ? $request->integer('squad') : null,
        );

        return response()->json($data);
    }

    private function photos(Request $request): JsonResponse|Response
    {
        $upload_array = PublicPhotosPageData::call(amount: $request->integer('amount'));

        if ($request->has('slideshow'))
        {
            return $this->slideshowResponse($upload_array);
        }

        // Double-wrapped intentionally: legacy API contract expects [[...items...]]
        return response()->json([$upload_array]);
    }

    private function roster(Request $request): Response
    {
        $data = PublicRosterPageData::call(organization_id: $request->integer('org', 0));

        return $this->rosterResponse($data['command_staff'], $data['members']);
    }

    private function rosterResponse(array $command_staff, array $members): Response
    {
        $html = '<style>
body{margin:0;padding:10px;font-family:Arial,sans-serif;background:#fff;color:#323f4e}
h1,h2{text-align:center;margin-bottom:16px}
h2{font-size:1em;border-bottom:1px solid #ccc;padding-bottom:6px;margin-top:20px}
.roster{display:flex;flex-wrap:wrap}
.member{width:110px;text-align:center;border:1px dashed #ccc;margin:10px;padding:8px;font-size:12px;word-break:break-word}
.member img{width:75px;height:101px;object-fit:cover;display:block;margin:0 auto 6px}
</style>';

        $html .= '<h1>Members</h1>';

        if ($command_staff !== [])
        {
            $html .= '<h2>Leadership, Liaisons, and Unit Staff</h2><div class="roster">';
            foreach ($command_staff as $member)
            {
                $html .= $this->memberCard($member);
            }
            $html .= '</div>';
        }

        $html .= '<h2>Members</h2><div class="roster">';

        foreach ($members as $member)
        {
            $html .= $this->memberCard($member);
        }

        if ($members === [] && $command_staff === [])
        {
            $html .= '<p style="width:100%;text-align:center">No members to display.</p>';
        }

        $html .= '</div>';

        return response($html);
    }

    /**
     * @param  array{name: string, identifier: string, photo: string|null}  $member
     */
    private function memberCard(array $member): string
    {
        $name = e($member['name']);
        $label = $member['identifier'] ? $name.' - '.e($member['identifier']) : $name;

        $img = $member['photo']
            ? '<img src="'.e($member['photo']).'" alt="">'
            : '<img src="'.e(url('images/tk_head.jpg')).'" alt="">';

        return '<div class="member">'.$img.$label.'</div>';
    }

    private function slideshowResponse(array $uploads): Response
    {
        $html = '<script src="https://www.w3schools.com/lib/w3.js"></script>';

        foreach ($uploads as $item)
        {
            $html .= '<img class="slideshow" src="'.e($item['imageUrl']).'" width="100%" height="100%">';
        }

        $html .= '<script>w3.slideshow(".slideshow", 3000);</script>';

        return response($html);
    }
}
