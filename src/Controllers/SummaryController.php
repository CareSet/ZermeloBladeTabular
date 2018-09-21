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
        // Wrap the report in cache
        $cache = new DatabaseCache( $report );
        $generator = new ReportSummaryGenerator( $cache );
        return $generator->toJson();
    }

    public function prefix() : string
    {
        $prefix = api_prefix()."/".config('zermelobladetabular.SUMMARY_URI_PREFIX', "" );
        return $prefix;
    }
}
