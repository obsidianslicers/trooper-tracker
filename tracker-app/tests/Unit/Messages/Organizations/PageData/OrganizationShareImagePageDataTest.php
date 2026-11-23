<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\PageData;

use App\Messages\Organizations\PageData\OrganizationShareImagePageData;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationShareImagePageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_falls_back_to_the_default_image_as_a_square_png(): void
    {
        $organization = Organization::factory()->create([Organization::IMAGE_PATH_LG => null]);

        $subject = new OrganizationShareImagePageData($organization);
        $png = $subject->handle();

        $this->assertNotNull($png);
        $size = getimagesizefromstring($png);
        $this->assertSame([OrganizationShareImagePageData::SIZE, OrganizationShareImagePageData::SIZE], [$size[0], $size[1]]);
        $this->assertSame('image/png', $size['mime']);
    }
}
