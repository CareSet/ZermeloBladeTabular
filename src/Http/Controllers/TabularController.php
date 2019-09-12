<?php

namespace CareSet\ZermeloBladeTabular\Http\Controllers;

use CareSet\Zermelo\Http\Controllers\AbstractWebController;
use CareSet\Zermelo\Models\Presenter;
use CareSet\ZermeloBladeTabular\TabularPresenter;

class TabularController extends AbstractWebController
{
    /**
     * @return \Illuminate\Config\Repository|mixed
     *
     * Get the view template
     */
    public  function getViewTemplate()
    {
        return config("zermelobladetabular.TABULAR_VIEW_TEMPLATE");
    }

    /**
     * @return string
     *
     * Specify the path to this report's API
     * This is a tabular report, so we'll use the tabular api prefix
     */
    public function getReportApiPrefix()
    {
        return tabular_api_prefix();
    }

    /**
     * @param $report
     * @return TabularPresenter
     *
     * Override this method to do any custom configuration for this controller,
     * like pushing variables onto the view
     */
    public function buildPresenter($report)
    {
        $presenter = new Presenter($report);
        $bootstrap_css_location = asset(config('zermelobladetabular.BOOTSTRAP_CSS_LOCATION'));
        $presenter->pushViewVariable('bootstrap_css_location', $bootstrap_css_location);
        $presenter->pushViewVariable('download_uri', $this->getDownloadUri($report));
        $presenter->pushViewVariable('report_uri', $this->getReportUri($report));
        $presenter->pushViewVariable('summary_uri', $this->getSummaryUri($report));
        $presenter->pushViewVariable('page_length', $this->getPageLength($report));

        return $presenter;
    }

    protected function getDownloadUri($report)
    {
        $parameterString = implode("/", $report->getMergedParameters() );
        $report_api_uri = "/{$this->getApiPrefix()}/{$this->getReportApiPrefix()}/{$report->uriKey()}/Download/{$parameterString}";
        return $report_api_uri;
    }

    protected function getReportUri($report)
    {
        $parameterString = implode("/", $report->getMergedParameters() );
        $report_api_uri = "/{$this->getApiPrefix()}/{$this->getReportApiPrefix()}/{$report->uriKey()}/{$parameterString}";
        return $report_api_uri;
    }

    protected function getSummaryUri($report)
    {
        $parameterString = implode("/", $report->getMergedParameters() );
        $summary_api_uri = "/{$this->getApiPrefix()}/{$this->getReportApiPrefix()}/{$report->uriKey()}/Summary/{$parameterString}";
        return $summary_api_uri;
    }

    protected function getPageLength($report)
    {
        $page_length =  $report->getParameter("length") ?: 50;
        return $page_length;
    }
}
