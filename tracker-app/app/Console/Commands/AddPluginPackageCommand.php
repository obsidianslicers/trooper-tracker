<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Requests\Admin\Plugins\AddPackageRequest;
use App\Messages\Plugins\Commands\AddPluginPackage;
use App\Rules\Admin\Plugins\VersionConstraintRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class AddPluginPackageCommand extends Command
{
    protected $signature = 'plugins:add-package
        {name : The Composer package, such as acme/trooptracker-badges}
        {version? : A version constraint, such as ^1.0}';

    protected $description = 'Add a Composer-managed plugin for the next deploy to install.';

    public function handle(): int
    {
        $input = [
            'name' => strtolower((string) $this->argument('name')),
            'version' => $this->argument('version'),
        ];
        $validator = Validator::make($input, [
            'name' => ['required', 'max:128', 'regex:'.AddPackageRequest::NAME_PATTERN],
            'version' => ['nullable', 'max:64', new VersionConstraintRule],
        ]);

        if ($validator->fails())
        {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        AddPluginPackage::call(name: $input['name'], version_constraint: $input['version']);
        $this->info("{$input['name']} will be installed by the next deploy.");

        return self::SUCCESS;
    }
}
