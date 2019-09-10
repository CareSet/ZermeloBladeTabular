<?php
/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 7/5/18
 * Time: 1:03 PM
 */
namespace CareSet\ZermeloBladeTabular;

use CareSet\Zermelo\Reports\Tabular\TabularPresenter as BasePresenter;

class TabularPresenter extends BasePresenter
{
    public function bootstrapCssLocation()
    {
        if ( config('zermelobladetabular.BOOTSTRAP_CSS_LOCATION') ) {
            return asset( config( 'zermelobladetabular.BOOTSTRAP_CSS_LOCATION' ) );
        } else {
            return asset('vendor/CareSet/bootstrap-4.3.1/css/bootstrap.min.css');
        }
    }
}
