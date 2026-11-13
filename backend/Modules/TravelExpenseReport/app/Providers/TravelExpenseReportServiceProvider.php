<?php

namespace Modules\TravelExpenseReport\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class TravelExpenseReportServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'TravelExpenseReport';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'travelexpensereport';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

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
