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

    public function test_validation_passes_with_valid_leaf_node_assignment(): void
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
                        'assignment' => $unit->id,
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

    public function test_validation_passes_with_null_assignment(): void
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
                        'assignment' => null,
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

    public function test_validation_fails_when_assignment_has_children(): void
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
                        'assignment' => $region->id, // Region has children (units)
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
        $this->assertTrue($validator->errors()->has("organizations.{$organization->id}.assignment"));
    }

    public function test_validation_fails_when_assignment_not_descendant(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $unrelated_unit = Organization::factory()->unit()->create();

        $administrator = Trooper::factory()->asAdministrator()->create();
        $target_trooper = Trooper::factory()->create();

        $subject = MembershipRequest::create(
            route('admin.troopers.membership', $target_trooper),
            'POST',
            [
                'organizations' => [
                    $organization->id => [
                        'assignment' => $unrelated_unit->id, // Not a descendant of organization
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
        $this->assertTrue($validator->errors()->has("organizations.{$organization->id}.assignment"));
    }

    public function test_validation_requires_assignment_when_identifier_provided(): void
    {
        // Arrange
        $organization = Organization::factory()->create(['identifier_validation' => 'integer|between:1000,99999']);

        $administrator = Trooper::factory()->asAdministrator()->create();
        $target_trooper = Trooper::factory()->create();

        $subject = MembershipRequest::create(
            route('admin.troopers.membership', $target_trooper),
            'POST',
            [
                'organizations' => [
                    $organization->id => [
                        'identifier' => '12345',
                        // assignment is missing
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
        $this->assertTrue($validator->errors()->has("organizations.{$organization->id}.assignment"));
    }

    public function test_validation_passes_with_valid_identifier_and_assignment(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $organization = $region->parent;
        $organization->update(['identifier_validation' => 'integer|between:1000,99999']);

        $administrator = Trooper::factory()->asAdministrator()->create();
        $target_trooper = Trooper::factory()->create();

        $subject = MembershipRequest::create(
            route('admin.troopers.membership', $target_trooper),
            'POST',
            [
                'organizations' => [
                    $organization->id => [
                        'identifier' => '12345',
                        'assignment' => $unit->id,
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
}
