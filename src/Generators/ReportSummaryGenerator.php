<?php

namespace CareSet\ZermeloBladeTabular\Generators;

use CareSet\Zermelo\Interfaces\CacheInterface;
use CareSet\Zermelo\Interfaces\GeneratorInterface;
use CareSet\Zermelo\Models\ZermeloReport;
use CareSet\Zermelo\Exceptions\InvalidDatabaseTableException;
use CareSet\Zermelo\Exceptions\InvalidHeaderFormatException;
use CareSet\Zermelo\Exceptions\InvalidHeaderTagException;
use CareSet\Zermelo\Exceptions\UnexpectedHeaderException;
use CareSet\Zermelo\Exceptions\UnexpectedMapRowException;
use \DB;

class ReportSummaryGenerator extends ReportGenerator implements GeneratorInterface
{

    public function toJson( ZermeloReport $Report )
    {
        return [
            'Report_Name' => $Report->getReportName(),
            'Report_Description' => $Report->getReportDescription(),
            'selected-data-option' => $Report->getParameter( 'data-option' ),
            'columns' => $this->runSummary($Report)
        ];
    }

    public function runSummary( ZermeloReport $Report )
    {
        $this->cacheInterface->init( $Report );

        if (!$this->cacheInterface->exists() || !$this->cacheInterface->isCacheable()) {
            $this->cacheInterface->CacheReport($Report);
        } else if ($this->cacheInterface->exists() && $this->cacheInterface->CheckUpdateCacheForReport($Report)) {
            $this->cacheInterface->CacheReport($Report);
        }

        $this->setCache( $this->cacheInterface );

        // This is BAD
        $cache_db = $this->cacheInterface->getCacheDB();
        $cache_table_stub = $this->cacheInterface->getCacheTableStub();
        $this->init( ['database' => $cache_db, 'table' => $cache_table_stub ] );

        return $this->getHeader($Report,true);
    }
}
