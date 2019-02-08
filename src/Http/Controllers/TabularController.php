<?php

namespace CareSet\ZermeloBladeTabular\Http\Controllers;

use CareSet\Zermelo\Http\Requests\TabularReportRequest;
use CareSet\ZermeloBladeTabular\TabularPresenter;
use Illuminate\Support\Facades\Auth;

class TabularController
{
    public function show( TabularReportRequest $request )
    {
        $presenter = new TabularPresenter( $request->buildReport() );
	
        $presenter->setApiPrefix( api_prefix() );
        $presenter->setReportPath( tabular_api_prefix() );

        $user = Auth::guard()->user();
        if ( $user ) {
            $presenter->setToken( $user->last_token );
        }

        $view = config("zermelobladetabular.TABULAR_VIEW_TEMPLATE");

        return view( $view, [ 'presenter' => $presenter ] );
    }
}
