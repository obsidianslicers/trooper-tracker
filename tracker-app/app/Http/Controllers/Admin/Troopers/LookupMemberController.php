<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\Controller;
use App\Messages\Admin\PageData\Troopers\LookupMemberPageData;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\TrooperRequest;
use App\Services\MemberLookup\MemberLookupResolver;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class LookupMemberController extends Controller
{
    public function __construct(private readonly MemberLookupResolver $resolver)
    {
    }

    public function __invoke(Request $request, TrooperRequest $trooper_request): InertiaResponse|SymfonyResponse
    {
        $data = [
            'results' => LookupMemberPageData::call(trooper_request: $trooper_request)
        ];

        return Inertia::render('admin/troopers/ListApprovals', $data);
    }

}
