<?php

namespace CareSet\ZermeloBladeTabular;

use CareSet\Zermelo\Models\AbstractZermeloProvider;
use CareSet\ZermeloBladeTabular\Console\ZermeloBladeTabularInstallCommand;
use CareSet\ZermeloBladeTabular\Controllers\ApiController;
use CareSet\ZermeloBladeTabular\Controllers\SummaryController;
use CareSet\ZermeloBladeTabular\Controllers\WebController;


Class ServiceProvider extends AbstractZermeloProvider
{

    protected $controllers = [
        ApiController::class,
        SummaryController::class,
        WebController::class
    ];

    public function boot(\Illuminate\Routing\Router $router)
	{

        /*
         * Register our zermelo view make command which:
         *  - Copies views
         *  - Exports configuration
         *  - Exports Assets
         */
        $this->commands([
            ZermeloBladeTabularInstallCommand::class
        ]);

        /*
         * Merge with main config so parameters are accessable.
         * Try to load config from the app's config directory first,
         * then load from the package.
         */
        if ( file_exists( config_path( 'zermelobladetabular.php' ) ) ) {

            $this->mergeConfigFrom(
                config_path( 'zermelobladetabular.php' ), 'zermelobladetabular'
            );
        } else {
            $this->mergeConfigFrom(
                __DIR__.'/../config/zermelobladetabular.php', 'zermelobladetabular'
            );
        }

        $this->loadViewsFrom( resource_path( 'views/zermelo' ), 'Zermelo');
	}
}
