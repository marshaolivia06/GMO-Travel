<?php

namespace Modules\UserManagement\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class UserManagementServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'UserManagement';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'usermanagement';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
