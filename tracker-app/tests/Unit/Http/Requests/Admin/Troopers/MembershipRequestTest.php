<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Http\Requests\Admin\Troopers\MembershipRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MembershipRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true_for_administrator(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();
        $target_trooper = Trooper::factory()->create();

        $subject = MembershipRequest::create(
            route('admin.troopers.membership', $target_trooper),
            'POST'
        );
        $subject->setUserResolver(fn() => $administrator);
        $subject->setRouteResolver(function () use ($target_trooper)
        {
            $route = new \Illuminate\Routing\Route('POST', 'troopers/{trooper}/membership', []);
            $route->bind($this->app['request']);
            $route->setParameter('trooper', $target_trooper);
            return $route;
        });

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_non_administrator(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $target_trooper = Trooper::factory()->create();

        $subject = MembershipRequest::create(
            route('admin.troopers.membership', $target_trooper),
            'POST'
        );
        $subject->setUserResolver(fn() => $member);
        $subject->setRouteResolver(function () use ($target_trooper)
        {
            $route = new \Illuminate\Routing\Route('POST', 'troopers/{trooper}/membership', []);
            $route->bind($this->app['request']);
            $route->setParameter('trooper', $target_trooper);
            return $route;
        });

        $this->assertFalse($subject->authorize());
    }

    public function test_authorize_throws_exception_when_trooper_not_found(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        $subject = MembershipRequest::create('/admin/troopers/999/membership', 'POST');
        $subject->setUserResolver(fn() => $administrator);
        $subject->setRouteResolver(function ()
        {
            $route = new \Illuminate\Routing\Route('POST', 'troopers/{trooper}/membership', []);
            $route->bind($this->app['request']);
            return $route;
        });

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Trooper not found or unauthorized.');

        $subject->authorize();
    }

    public function test_validation_passes_with_valid_region_id(): void
    {
        // Arrange
        $region = Organization::factory()->region()->create();
        $organization = $region->parent;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $target_trooper = Trooper::factory()->create();

        $subject = MembershipRequest::create(
            route('admin.troopers.membership', $target_trooper),
            'POST',
            [
                'organizations' => [
                    $organization->id => [
                        'region_id' => $region->id,
                    ],
                ],
            ]
        );
        $subject->setUserResolver(fn() => $administrator);
        $subject->setRouteResolver(function () use ($target_trooper)
        {
            $route = new \Illuminate\Routing\Route('POST', 'troopers/{trooper}/membership', []);
            $route->bind($this->app['request']);
            $route->setParameter('trooper', $target_trooper);
            return $route;
        });

        // Act
        $validator = Validator::make($subject->all(), $subject->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function test_validation_passes_with_null_region_id(): void
    {
        // Arrange
        $organization = Organization::factory()->create();

        $administrator = Trooper::factory()->asAdministrator()->create();
        $target_trooper = Trooper::factory()->create();

        $subject = MembershipRequest::create(
            route('admin.troopers.membership', $target_trooper),
            'POST',
            [
                'organizations' => [
                    $organization->id => [
                        'region_id' => null,
                    ],
                ],
            ]
        );
        $subject->setUserResolver(fn() => $administrator);
        $subject->setRouteResolver(function () use ($target_trooper)
        {
            $route = new \Illuminate\Routing\Route('POST', 'troopers/{trooper}/membership', []);
            $route->bind($this->app['request']);
            $route->setParameter('trooper', $target_trooper);
            return $route;
        });

        // Act
        $validator = Validator::make($subject->all(), $subject->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_with_invalid_region_id(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $region_from_other_org = Organization::factory()->region()->create();

        $administrator = Trooper::factory()->asAdministrator()->create();
        $target_trooper = Trooper::factory()->create();

        $subject = MembershipRequest::create(
            route('admin.troopers.membership', $target_trooper),
            'POST',
            [
                'organizations' => [
                    $organization->id => [
                        'region_id' => $region_from_other_org->id,
                    ],
                ],
            ]
        );
        $subject->setUserResolver(fn() => $administrator);
        $subject->setRouteResolver(function () use ($target_trooper)
        {
            $route = new \Illuminate\Routing\Route('POST', 'troopers/{trooper}/membership', []);
            $route->bind($this->app['request']);
            $route->setParameter('trooper', $target_trooper);
            return $route;
        });

        // Act
        $validator = Validator::make($subject->all(), $subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has("organizations.{$organization->id}.region_id"));
    }

    public function test_validation_requires_unit_when_region_with_units_is_selected(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $organization = $region->parent;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $target_trooper = Trooper::factory()->create();

        $subject = MembershipRequest::create(
            route('admin.troopers.membership', $target_trooper),
            'POST',
            [
                'organizations' => [
                    $organization->id => [
                        'region_id' => $region->id,
                        // unit_id is missing
                    ],
                ],
            ]
        );
        $subject->setUserResolver(fn() => $administrator);
        $subject->setRouteResolver(function () use ($target_trooper)
        {
            $route = new \Illuminate\Routing\Route('POST', 'troopers/{trooper}/membership', []);
            $route->bind($this->app['request']);
            $route->setParameter('trooper', $target_trooper);
            return $route;
        });

        // Act
        $validator = Validator::make($subject->all(), $subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has("organizations.{$organization->id}.unit_id"));
    }

    public function test_validation_passes_with_valid_unit_id(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $organization = $region->parent;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $target_trooper = Trooper::factory()->create();

        $subject = MembershipRequest::create(
            route('admin.troopers.membership', $target_trooper),
            'POST',
            [
                'organizations' => [
                    $organization->id => [
                        'region_id' => $region->id,
                        'unit_id' => $unit->id,
                    ],
                ],
            ]
        );
        $subject->setUserResolver(fn() => $administrator);
        $subject->setRouteResolver(function () use ($target_trooper)
        {
            $route = new \Illuminate\Routing\Route('POST', 'troopers/{trooper}/membership', []);
            $route->bind($this->app['request']);
            $route->setParameter('trooper', $target_trooper);
            return $route;
        });

        // Act
        $validator = Validator::make($subject->all(), $subject->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_with_invalid_unit_id(): void
    {
        // Arrange
        $region = Organization::factory()->region()->create();
        $organization = $region->parent;
        $unit_from_other_region = Organization::factory()->unit()->create();

        $administrator = Trooper::factory()->asAdministrator()->create();
        $target_trooper = Trooper::factory()->create();

        $subject = MembershipRequest::create(
            route('admin.troopers.membership', $target_trooper),
            'POST',
            [
                'organizations' => [
                    $organization->id => [
                        'region_id' => $region->id,
                        'unit_id' => $unit_from_other_region->id,
                    ],
                ],
            ]
        );
        $subject->setUserResolver(fn() => $administrator);
        $subject->setRouteResolver(function () use ($target_trooper)
        {
            $route = new \Illuminate\Routing\Route('POST', 'troopers/{trooper}/membership', []);
            $route->bind($this->app['request']);
            $route->setParameter('trooper', $target_trooper);
            return $route;
        });

        // Act
        $validator = Validator::make($subject->all(), $subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has("organizations.{$organization->id}.unit_id"));
    }
}
