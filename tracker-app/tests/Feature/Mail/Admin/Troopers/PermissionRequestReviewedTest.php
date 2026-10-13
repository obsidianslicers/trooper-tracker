<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Mail\Admin\Troopers\PermissionRequestReviewed;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionRequestReviewedTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_and_body_show_denial_with_reason(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $permission_request = PermissionRequest::factory()
            ->requestedBy(Trooper::factory()->asModerator()->create())
            ->withRole(MembershipRole::MODERATOR)
            ->create([
                PermissionRequest::STATUS => PermissionRequestStatus::DENIED,
                PermissionRequest::DENIAL_REASON => 'Not yet',
            ]);

        $subject = new PermissionRequestReviewed($permission_request);

        $this->assertSame('[TEST] Permission Request Denied', $subject->envelope()->subject);
        $html = $subject->render();
        $this->assertStringContainsString('denied', $html);
        $this->assertStringContainsString('Role: Moderator', $html);
        $this->assertStringContainsString('Reason: Not yet', $html);
    }
}
