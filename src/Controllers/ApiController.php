<?php

namespace CareSet\ZermeloBladeTabular\Controllers;

use CareSet\Zermelo\Interfaces\ControllerInterface;
use CareSet\ZermeloBladeTabular\Generators\ReportGenerator;
use CareSet\Zermelo\Models\DatabaseCache;
use CareSet\Zermelo\Models\ZermeloReport;
use CareSet\ZermeloBladeTabular\Models\TabularPresenter;
use DB;

class ApiController implements ControllerInterface
{
    public function show( ZermeloReport $report )
    {
        $presenter = new TabularPresenter( $report );
        $presenter->setApiPrefix( api_prefix() );
        $presenter->setReportPath( config('zermelobladetabular.TABULAR_URI_PREFIX') );
        $presenter->setSummaryPath( config('zermelobladetabular.SUMMARY_URI_PREFIX') );
        $cache = new DatabaseCache();
        $generator = new ReportGenerator( $cache );
        return $generator->toJson( $report );
    }

    public function prefix() : string
    {
        $prefix = api_prefix()."/".config('zermelobladetabular.TABULAR_URI_PREFIX', "" );
        return $prefix;
    }
}
