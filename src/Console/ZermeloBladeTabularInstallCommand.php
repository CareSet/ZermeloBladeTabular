<?php

namespace CareSet\ZermeloBladeTabular\Console;

use CareSet\Zermelo\Console\AbstractZermeloInstallCommand;

class ZermeloBladeTabularInstallCommand extends AbstractZermeloInstallCommand
{
    protected $view_path = __DIR__.'/../../views';

    protected $asset_path = __DIR__.'/../../assets';

    protected $config_file = __DIR__.'/../../config/zermelobladetabular.php';

    /**
     * The views that need to be exported.
     *
     * @var array
     */
    protected $views = [
        'zermelo/tabular.blade.php',
        'zermelo/layouts/tabular.blade.php',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zermelo:install_zermelobladetabular
                    {--force : Overwrite existing views by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Zermelo Blade Tabular report view';
}
