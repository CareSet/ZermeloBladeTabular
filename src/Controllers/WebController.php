<?php

namespace CareSet\ZermeloBladeTabular\Controllers;

use CareSet\Zermelo\Interfaces\ControllerInterface;
use CareSet\Zermelo\Models\ZermeloReport;
use CareSet\ZermeloBladeTabular\Models\TabularPresenter;
use DB;
use Illuminate\Support\Facades\Auth;

class WebController implements ControllerInterface
{
    public function show( ZermeloReport $report )
    {
        $presenter = new TabularPresenter( $report );

        $presenter->setApiPrefix( api_prefix() );
        $presenter->setReportPath( config('zermelobladetabular.TABULAR_URI_PREFIX', '') );
        $presenter->setSummaryPath( config('zermelobladetabular.SUMMARY_URI_PREFIX', '') );

        $user = Auth::guard()->user();
        if ( $user ) {
            $presenter->setToken( $user->last_token );
        }

        $view = $presenter->getReportView();
        if ( !$view ) {
            $view = config("zermelobladetabular.TABULAR_VIEW_TEMPLATE");
        }

        return view( $view, [ 'presenter' => $presenter ] );
    }

    public function prefix() : string
    {
        $prefix = config('zermelobladetabular.TABULAR_URI_PREFIX', "" );
        return $prefix;
    }
}
