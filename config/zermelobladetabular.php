<?php

return [

    /**
     * Path where the Report display.
     * This is used in the route configuration in this module's ServiceProvider
     * /Zermelo/(ReportName)
     */
    'TABULAR_URI_PREFIX' => env("TABULAR_URI_PREFIX","Zermelo"),

    /**
     * Middleware on the tabular web routes
     */
    'TABULAR_MIDDLEWARE' => env("TABULAR_MIDDLEWARE", [ "web" ]),


    /**
     * The template the controller will use to render the report
     * This is used in WebController implementation of ControllerInterface@show method
     */
    "TABULAR_VIEW_TEMPLATE"=>env("TABULAR_VIEW_TEMPLATE","Zermelo::layouts.tabular"),

    /**
     * Bootstrap CSS file, you may overwrite the default here.
     */
    'BOOTSTRAP_CSS_LOCATION' => 'vendor/CareSet/zermelobladetabular/bootstrap-4.3.1/css/bootstrap.min.css',
];
