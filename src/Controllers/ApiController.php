<?php

namespace CareSet\ZermeloBladeTabular\Controllers;

use CareSet\Zermelo\Interfaces\ControllerInterface;
use CareSet\Zermelo\Interfaces\DownloadableInterface;
use CareSet\Zermelo\Models\CacheMeta;
use CareSet\ZermeloBladeTabular\Generators\ReportGenerator;
use CareSet\Zermelo\Models\DatabaseCache;
use CareSet\Zermelo\Models\ZermeloReport;
use CareSet\ZermeloBladeTabular\Generators\ReportSummaryGenerator;
use CareSet\ZermeloBladeTabular\Models\TabularPresenter;
use DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiController implements ControllerInterface, DownloadableInterface
{
    public function show( ZermeloReport $report )
    {
        $presenter = new TabularPresenter( $report );
	$api_prefix = trim( config("zermelo.URI_API_PREFIX"), "/ " );
        $presenter->setApiPrefix( $api_prefix );
        $presenter->setReportPath( config('zermelobladetabular.TABULAR_URI_PREFIX') );
        $presenter->setSummaryPath( config('zermelobladetabular.SUMMARY_URI_PREFIX') );
        $cache = new DatabaseCache( $report );
        $generator = new ReportGenerator( $cache );
        return $generator->toJson();
    }

    public function prefix() : string
    {
	$api_prefix = trim( config("zermelo.URI_API_PREFIX"), "/ " );
        $prefix = $api_prefix."/".config('zermelobladetabular.TABULAR_URI_PREFIX', "" );
        return $prefix;
    }

    /**
     * Generate the download for the targeted report. This relies on the cached version of the ReportJSON
     * @param ZermeloReport $report          Target Report
     * @return CSV download
     *
     */
    public function download( ZermeloReport $report )
    {
        $cache = new DatabaseCache( $report );
        $summaryGenerator = new ReportSummaryGenerator( $cache );
        $header = $summaryGenerator->runSummary();
        $header = array_map( function( $element ) {
            return $element['title'];
        }, $header );
        $reportGenerator = new ReportGenerator( $cache );
        $collection = $reportGenerator->getCollection();

        $response = new StreamedResponse( function() use ( $header, $collection ) {
            // Open output stream
            $handle = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv( $handle, $header );

            // Get all users
            foreach ( $collection as $value ) {
                // Add a new row with data
                fputcsv( $handle, json_decode(json_encode($value), true) );
            }

            // Close the output stream
            fclose($handle);
        }, 200, [
            'Content-Description' => 'File Transfer',
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$report->GetReportName().'.csv"',
            'Content-Type' => 'application/octet-stream',
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate',
            'Pragma' => 'public'
        ]);

        return $response;
    }
}
