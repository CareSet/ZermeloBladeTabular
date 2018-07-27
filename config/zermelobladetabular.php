<?php

return [


    /**
     * The template the controller will use to render the report
     * This is used in WebController implementation of ControllerInterface@show method
     */
    "TABULAR_VIEW_TEMPLATE"=>env("TABULAR_VIEW_TEMPLATE","Zermelo::layouts.tabular"),

    /**
     * Path where the Report display.
     * This is used in implementations of ControllerInterface@show method
     * Note: the API routes are auto generated with this same URI path with the api-prefixed to the url
     * /Zermelo/(ReportName) (see config/zermelo.php for api prefix setting)
     */
    'TABULAR_URI_PREFIX'=>env("TABULAR_URI_PREFIX","Zermelo"),

    /**
     * Path where the Summary API lives
     * This is used in implementations of ControllerInterface@show method
     * Note: the API routes are auto generated with this same URI path with the api-prefixed to the url
     * /ZermeloSummary/(ReportName) (see config/zermelo.php for api prefix setting)
     */
    'SUMMARY_URI_PREFIX'=>env("SUMMARY_URI_PREFIX","ZermeloSummary"),
];
