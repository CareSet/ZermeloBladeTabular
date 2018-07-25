<?php

return [


    /**
     * The template the Report Engine will use to render the report
     * This will be called from ZermeloController@ReportDisplay
     */
    "TABULAR_VIEW_TEMPLATE"=>env("TABULAR_VIEW_TEMPLATE","Zermelo::layouts.tabular"),

    /**
     * Path where the Report display.
     * This path should be inside the web route and points to ZermeloController@ReportDisplay
     * Note: the API routes are auto generated with this same URI path with the api-prefixed to the url
     * /Zermelo/(ReportName)
     */
    'TABULAR_URI_PREFIX'=>env("TABULAR_URI_PREFIX","Zermelo"),

    /**
     * Path where the Summary API lives
     * This path should be inside the api route and points to Zermeloontroller@ReportModelSummaryJson
     * Note: the API routes are auto generated with this same URI path with the api-prefixed to the url
     * /ZermeloSummary/(ReportName)
     */
    'SUMMARY_URI_PREFIX'=>env("SUMMARY_URI_PREFIX","ZermeloSummary"),
];