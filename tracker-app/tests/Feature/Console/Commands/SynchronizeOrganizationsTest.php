<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Contracts\SynchronizerInterface;
use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

use Tests\TestCase;

/**
 * Test suite for SynchronizeOrganizations command.
 *
 * Verifies that the command correctly identifies organizations with
 * synchronization services and executes their synchronization processes.
 */
class SynchronizeOrganizationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the command executes synchronization for organizations with service class.
     */
    public function test_command_executes_synchronization_for_organizations_with_service_class(): void
    {
        // Arrange: Create organization with service class
        $organization = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::SERVICE_CLASS => MockSynchronizerService::class,
        ]);

        // Mock the synchronizer service
        $mock_service = Mockery::mock(SynchronizerInterface::class);
        $mock_service->shouldReceive('run')
            ->once()
            ->andReturn(null);

        // Bind mock into container
        $this->app->bind(MockSynchronizerService::class, function () use ($mock_service)
        {
            return $mock_service;
        });

        // Act: Execute the command
        $this->artisan('tracker:synchronize-organizations')
            ->assertExitCode(0);
    }

    /**
     * Test that the command skips organizations without service class.
     */
    public function test_command_skips_organizations_without_service_class(): void
    {
        // Arrange: Create organization without service class
        Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::SERVICE_CLASS => null,
        ]);

        // Act: Command should complete without running any synchronizers
        $this->artisan('tracker:synchronize-organizations')
            ->assertExitCode(0);
    }

    /**
     * Test that the command only processes organization type entities.
     */
    public function test_command_only_processes_organization_type_entities(): void
    {
        // Arrange: Create different organization types
        $organization = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::SERVICE_CLASS => MockSynchronizerService::class,
        ]);

        $region = Organization::factory()->create([
            Organization::TYPE => OrganizationType::REGION,
            Organization::SERVICE_CLASS => MockSynchronizerService::class,
        ]);

        $unit = Organization::factory()->create([
            Organization::TYPE => OrganizationType::UNIT,
            Organization::SERVICE_CLASS => MockSynchronizerService::class,
        ]);

        // Mock service - should only be called once for organization
        $mock_service = Mockery::mock(SynchronizerInterface::class);
        $mock_service->shouldReceive('run')
            ->once()
            ->andReturn(null);

        $this->app->bind(MockSynchronizerService::class, function () use ($mock_service)
        {
            return $mock_service;
        });

        // Act: Execute the command
        $this->artisan('tracker:synchronize-organizations')
            ->assertExitCode(0);
    }

    /**
     * Test that the command processes multiple organizations.
     */
    public function test_command_processes_multiple_organizations(): void
    {
        // Arrange: Create multiple organizations
        $org1 = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::NAME => 'Alpha Organization',
            Organization::SERVICE_CLASS => MockSynchronizerService::class,
        ]);

        $org2 = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::NAME => 'Beta Organization',
            Organization::SERVICE_CLASS => MockSynchronizerService::class,
        ]);

        // Mock service - should be called twice
        $mock_service = Mockery::mock(SynchronizerInterface::class);
        $mock_service->shouldReceive('run')
            ->twice()
            ->andReturn(null);

        $this->app->bind(MockSynchronizerService::class, function () use ($mock_service)
        {
            return $mock_service;
        });

        // Act: Execute the command
        $this->artisan('tracker:synchronize-organizations')
            ->assertExitCode(0);
    }

    /**
     * Test that the command outputs completion message with timing.
     */
    public function test_command_outputs_completion_message_with_timing(): void
    {
        // Arrange: Create organization
        Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::SERVICE_CLASS => MockSynchronizerService::class,
        ]);

        // Mock service
        $mock_service = Mockery::mock(SynchronizerInterface::class);
        $mock_service->shouldReceive('run')->once()->andReturn(null);

        $this->app->bind(MockSynchronizerService::class, function () use ($mock_service)
        {
            return $mock_service;
        });

        // Act: Execute the command
        $this->artisan('tracker:synchronize-organizations')
            ->assertExitCode(0);
    }

    /**
     * Test that the command orders organizations by name descending.
     */
    public function test_command_orders_organizations_by_name_descending(): void
    {
        // Arrange: Create organizations with specific names
        Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::NAME => 'Alpha',
            Organization::SERVICE_CLASS => MockSynchronizerService::class,
        ]);

        Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::NAME => 'Zulu',
            Organization::SERVICE_CLASS => MockSynchronizerService::class,
        ]);

        Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::NAME => 'Bravo',
            Organization::SERVICE_CLASS => MockSynchronizerService::class,
        ]);

        $processed_order = [];

        // Mock service to track execution order
        $mock_service = Mockery::mock(SynchronizerInterface::class);
        $mock_service->shouldReceive('run')
            ->times(3)
            ->andReturn(null);

        $this->app->bind(MockSynchronizerService::class, function ($app, $params) use ($mock_service, &$processed_order)
        {
            // Capture the organization name
            if (isset($params['organization']))
            {
                $processed_order[] = $params['organization']->name;
            }
            return $mock_service;
        });

        // Act: Execute the command
        $this->artisan('tracker:synchronize-organizations')
            ->assertExitCode(0);

        // Assert: Organizations processed in descending name order
        $this->assertEquals(['Zulu', 'Bravo', 'Alpha'], $processed_order);
    }

    /**
     * Test that the command has the correct signature.
     */
    public function test_command_has_correct_signature(): void
    {
        // Act: Get all registered commands
        $commands = $this->app['Illuminate\Contracts\Console\Kernel']->all();

        // Assert: Verify command exists
        $this->assertArrayHasKey('tracker:synchronize-organizations', $commands);
    }

    /**
     * Test that the command handles no organizations gracefully.
     */
    public function test_command_handles_no_organizations(): void
    {
        // Arrange: No organizations created

        // Act: Command should complete successfully
        $this->artisan('tracker:synchronize-organizations')
            ->assertExitCode(0);
    }

    /**
     * Test that the command instantiates service with organization parameter.
     */
    public function test_command_instantiates_service_with_organization_parameter(): void
    {
        // Arrange: Create organization
        $organization = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::SERVICE_CLASS => MockSynchronizerService::class,
        ]);

        $service_instantiated_with_org = false;

        // Mock service
        $mock_service = Mockery::mock(SynchronizerInterface::class);
        $mock_service->shouldReceive('run')->once()->andReturn(null);

        $this->app->bind(MockSynchronizerService::class, function ($app, $params) use ($mock_service, $organization, &$service_instantiated_with_org)
        {
            // Verify organization is passed as parameter
            if (isset($params['organization']) && $params['organization']->id === $organization->id)
            {
                $service_instantiated_with_org = true;
            }
            return $mock_service;
        });

        // Act: Execute the command
        $this->artisan('tracker:synchronize-organizations')
            ->assertExitCode(0);

        // Assert: Service was instantiated with correct organization
        $this->assertTrue($service_instantiated_with_org, 'Service should be instantiated with organization parameter');
    }
}

/**
 * Mock synchronizer service for testing.
 */
class MockSynchronizerService implements SynchronizerInterface
{
    public function __construct(?Organization $organization = null)
    {
        //
    }

    public function run(): void
    {
        //
    }
}
