<?php

Route::get( '/{report_key}/{parameters?}', 'TabularController@show' )->where(['parameters' => '.*']);
