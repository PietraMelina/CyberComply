<?php

namespace App\Providers;

use App\Services\Cmd\CmdServiceInterface;
use App\Services\Cmd\MockCmdService;
use App\Services\Cmd\RealCmdService;
use Illuminate\Support\ServiceProvider;

class CmdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CmdServiceInterface::class, function () {
            if ((bool) config('services.cmd.mock_enabled', true)) {
                return new MockCmdService();
            }

            return new RealCmdService();
        });
    }
}

