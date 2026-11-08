<?php

namespace Modules\ExternalFormRequest\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class ExternalFormRequestServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ExternalFormRequest';

    protected string $nameLower = 'externalformrequest';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
