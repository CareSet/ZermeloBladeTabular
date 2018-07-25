<?php

namespace CareSet\ZermeloBladeTabular\Controllers;

use CareSet\Zermelo\Interfaces\ControllerInterface;
use CareSet\Zermelo\Models\DatabaseCache;
use CareSet\Zermelo\Models\ZermeloReport;
use CareSet\ZermeloBladeTabular\Generators\ReportSummaryGenerator;

class SummaryController implements ControllerInterface
{
    public function show( ZermeloReport $report )
    {
        $cache = new DatabaseCache();
        $generator = new ReportSummaryGenerator( $cache );
        return $generator->toJson( $report );
    }

    public function prefix() : string
    {
        $prefix = api_prefix()."/".config('zermelobladetabular.SUMMARY_URI_PREFIX', "" );
        return $prefix;
    }
}
