<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PackageUpdatePolicy;
use App\Http\Requests\Admin\Plugins\AddPackageRequest;
use App\Messages\Plugins\Commands\AddPluginPackage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class AddPluginPackageCommand extends Command
{
    protected $signature = 'plugins:add-package
        {name : The Composer package, such as acme/trooptracker-badges}
        {version? : A version constraint, such as ^1.0}
        {--repository= : An https URL of the package repository, if not on Packagist}
        {--compatible : Take compatible (same major version) updates on each deploy}';

    protected $description = 'Add a Composer-managed plugin for the next deploy to install.';

    public function handle(): int
    {
        $input = [
            'name' => strtolower((string) $this->argument('name')),
            'version_constraint' => $this->argument('version'),
            'repository_url' => $this->option('repository'),
        ];
        $validator = Validator::make($input, AddPackageRequest::fieldRules());

        if ($validator->fails())
        {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        AddPluginPackage::call(
            name: $input['name'],
            version_constraint: $input['version_constraint'],
            repository_url: $input['repository_url'],
            update_policy: $this->option('compatible')
                ? PackageUpdatePolicy::COMPATIBLE
                : PackageUpdatePolicy::PINNED,
        );
        $this->info("{$input['name']} will be installed by the next deploy.");

        return self::SUCCESS;
    }
}
