<?php

namespace CareSet\ZermeloBladeTabular\Generators;

use CareSet\Zermelo\Interfaces\CacheInterface;
use CareSet\Zermelo\Interfaces\GeneratorInterface;
use CareSet\Zermelo\Models\AbstractGenerator;
use CareSet\Zermelo\Models\DatabaseCache;
use CareSet\Zermelo\Models\ZermeloDatabase;
use CareSet\Zermelo\Models\ZermeloReport;
use CareSet\Zermelo\Exceptions\InvalidDatabaseTableException;
use CareSet\Zermelo\Exceptions\InvalidHeaderFormatException;
use CareSet\Zermelo\Exceptions\InvalidHeaderTagException;
use CareSet\Zermelo\Exceptions\UnexpectedHeaderException;
use CareSet\Zermelo\Exceptions\UnexpectedMapRowException;

class ReportGenerator extends AbstractGenerator implements GeneratorInterface
{
    const MAX_PAGING_LIMIT = 99999999999999;

    protected $cache = null;

    public function __construct( DatabaseCache $cache )
    {
        $this->cache = $cache;
    }

    public function init( array $params = null )
    {
        parent::init( $params );
    }

    public function getHeader( bool $includeSummary = false )
    {
        $Table = clone $this->cache->getTable();
        $data_row = $Table->first();

        $headers = []; //this is used to store the headers before and after the column definition
        $mapped_header = []; //this is the result from the MapRow function
        $original_array_key = []; //this is the original field name from the table
        $fields = $this->cache->getColumns();

        //convert stdClass to array
        $data_row = json_decode(json_encode($data_row), true);
        $has_data = true;
        if(!is_array($data_row)) {
            $data_row = [];
            $has_data = false;
        }
        
        $original_array_key = array_keys($data_row);

        /*
        Run the MapRow once to get the proper column name from the Report
         */
	    $first_row_num = 0;
	    if ( $has_data ) {
            $data_row = $this->cache->MapRow( $data_row, $first_row_num ); //
            $mapped_header = array_keys( $data_row );
        }


        /*
        This makes sure no new columns were added or removed.
         */
        if (count($original_array_key) != count($mapped_header)) {
            throw new UnexpectedMapRowException();
        }


        /*
        Converts the header into an key/value pair. the key being the column name.
        Call the OverrideHeader function from the Report to override any kind of header data.
         */
        $header_format = array_combine($mapped_header, array_fill(0, count($mapped_header), null));
        $header_tags = array_combine($mapped_header, array_fill(0, count($mapped_header), null));


        /*
        Determine the header format based on the column title and type
         */
        $header_format = self::DefaultColumnFormat($this->cache->getReport(), $header_format, $fields);

        /*
        Override the default header with what the report gives back,
        then check to see if the format and tags are valid
         */
        $this->cache->OverrideHeader($header_format, $header_tags);

        foreach ($header_format as $name => $format) {
            if (!in_array($name, $mapped_header)) {
                throw new UnexpectedHeaderException("Column header not found: {$name}");
            }

            if ($format !== null && !in_array($format, $this->cache->getReport()->VALID_COLUMN_FORMAT)) {
                throw new InvalidHeaderFormatException("Invalid column header format: {$format}");
            }

        }

        foreach ($header_tags as $name => &$tags) {
            if (!in_array($name, $mapped_header)) {
                throw new UnexpectedHeaderException("Column header not found: {$name}");
            }

            if ($tags == null) {
                $tags = [];
            }

            if (!is_array($tags)) {
                $tags = [$tags];
            }

            if (config("zermelo.RESTRICT_TAGS")) {
                $valid_tags = config("zermelo.TAGS");

                foreach ($tags as $tag) {
                    if (!in_array($tag, $valid_tags)) {
                        throw new InvalidHeaderTagException("Invalid tag: {$tag}");
                    }
                }
            }
        }

        /*
        Calculate the distinct count, sum, avg, std, min, max for fields that are integer/date base
         */
        $summary_data = [];
        if ($includeSummary) {
            $target_fields = [];
            foreach ($fields as $field_name => $field) {
                if ($field['Type'] == 'string') {
                    $target_fields[] = "count(distinct(`{$field_name}`)) as `cnt_{$field_name}`";
                } else if ($field['Type'] == 'integer' || $field['Type'] == 'decimal') {
                    $target_fields[] = "sum(`{$field_name}`) as `sum_{$field_name}`";
                    $target_fields[] = "avg(`{$field_name}`) as `avg_{$field_name}`";
                    $target_fields[] = "std(`{$field_name}`) as `std_{$field_name}`";
                    $target_fields[] = "min(`{$field_name}`) as `min_{$field_name}`";
                    $target_fields[] = "max(`{$field_name}`) as `max_{$field_name}`";
                } else if ($field['Type'] == 'date') {
                    $target_fields[] = "FROM_UNIXTIME(avg(UNIX_TIMESTAMP(`{$field_name}`))) as `avg_{$field_name}`";
                    $target_fields[] = "min(`{$field_name}`) as `min_{$field_name}`";
                    $target_fields[] = "max(`{$field_name}`) as `max_{$field_name}`";
                }
            }
            $target_fields = implode(",", $target_fields);
            $ResultTable = clone $this->cache->getTable();
            $result = json_decode(json_encode($ResultTable->selectRaw($target_fields)->first()), true);

            /*
            Parse the result out into an associated array with the proper field name as the key
             */
            foreach ($result as $col => $value) {
                $reg = '/^(cnt|sum|avg|std|min|max)_(.*)$/i';
                if (preg_match($reg, $col, $matches)) {
                    $summary_type = $matches[1];
                    $column_name = $matches[2];
                    static $type_value = [
                        "cnt" => "count",
                        "sum" => "sum",
                        "avg" => "average",
                        "std" => "standard_deviation",
                        "min" => "minimum",
                        "max" => "maximum",
                    ];
                    $summary_data[$column_name][$type_value[$summary_type]] = $value;
                }
            }

            /*
            Check if any column are in the SUGGEST_NO_SUMMARY and add a flag
             */
            foreach ($summary_data as $name => $data) {
                if (ZermeloDatabase::isColumnInKeyArray($name, $this->cache->getReport()->SUGGEST_NO_SUMMARY)) {
                    $summary_data[$name]['NO_SUMMARY'] = true;
                }
            }
        }

        /*
        Merge format/tags/summary information together into 1 array
         */
        $header = [];
        foreach ($header_format as $name => $field) {
            $title = ucwords(str_replace('_', ' ', $name), "\t\r\n\f\v ");
            $column = [
                'field' => $name,
                'title' => $title,
                'format' => $header_format[$name] ?? 'TEXT',
                'tags' => $header_tags[$name] ?? [],
            ];

            if (key_exists($name, $summary_data)) {
                $column['summary'] = $summary_data[$name];
            }

            $header[] = $column;
        }

        return $header;
    }


    public function paginate($length)
    {
        $Pager = clone $this->cache->getTable();
        return $Pager->paginate($length);
    }


    /**
     * DefaultColumnFormat
     * Attempts to return the format of the column based on the column name and the predefine header configuration
     *
     * @param ZermeloReport $Report
     * @param array $format
     * @param array $fields
     * @return array
     */
    private static function DefaultColumnFormat(ZermeloReport $Report, array $format, array $fields): array
    {
        foreach ($format as $name => $value) {

            if (ZermeloDatabase::isColumnInKeyArray($name, $Report->DETAIL)) {
                $format[$name] = 'DETAIL';
            } else if (ZermeloDatabase::isColumnInKeyArray($name, $Report->URL) && in_array($fields[$name]["Type"], ["string"])) {
                $format[$name] = 'URL';
            } else if (ZermeloDatabase::isColumnInKeyArray($name, $Report->CURRENCY) /* && in_array($fields[$name]["Type"],["integer","decimal"])*/) {
                $format[$name] = 'CURRENCY';
            } else if (ZermeloDatabase::isColumnInKeyArray($name, $Report->NUMBER) /* && in_array($fields[$name]["Type"],["integer","decimal"])*/) {
                $format[$name] = 'NUMBER';
            } else if (ZermeloDatabase::isColumnInKeyArray($name, $Report->DECIMAL) /* && in_array($fields[$name]["Type"],["integer","decimal"])*/) {
                $format[$name] = 'DECIMAL';
            } else if (in_array($fields[$name]["Type"], ["date", "time", "datetime"])) {
                $format[$name] = strtoupper($fields[$name]["Type"]);
            } else if (ZermeloDatabase::isColumnInKeyArray($name, $Report->PERCENT) /* && in_array($fields[$name]["Type"],["integer","decimal"])*/) {
                $format[$name] = 'PERCENT';
            }

        }

        return $format;
    }

    /**
     * ReportModelJson
     * Return the ZermeloReport as a pagable model
     *
     * @param ZermeloReport $Report
     * @return Collection
     */
    public function toJson()
    {
        $Report = $this->cache->getReport();
        $input_bolt = $Report->getParameter('data-option' );
        $report_name = trim($Report->getClassName());
        $Code = $Report->getCode();
        $Parameters = $Report->getParameters();

        $paging_length = $Report->getInput("length") ?? 1000;

        if ($paging_length > 500000 && $paging_length > 0) {
            $paging_length = 500000;
        }

        if ($paging_length <= 0) {
            $paging_length = self::MAX_PAGING_LIMIT;
        }
        /* no limit*/

        /*
        If there is a filter, lets apply it to each column
         */
        $filter = $Report->getInput('filter');
        if ($filter && is_array($filter)) {
            $associated_filter = [];
            foreach($filter as $f=>$item)
            {
                $field = key($item);
                $value = $item[$field];
                $associated_filter[$field] = $value;
            }

            $this->addFilter($associated_filter);
        }

        $orderBy = $Report->getInput('order') ?? [];
        $associated_orderby = [];

        foreach ($orderBy as $order) {
            $orderKey = key($order);
            $direction = $order[$orderKey];
            $associated_orderby[$orderKey] = $direction;
        }
        $this->orderBy($associated_orderby);


        $paging = $this->paginate($paging_length);

        /*
        Transform each row using $Report->MapRow()
         */
        $paging->getCollection()->transform(function ($value, $key) use ($Report) {
            $value_array = json_decode(json_encode($value), true);
            return json_decode(json_encode($Report->MapRow($value_array, $key)));
        });

        /*
        Add in the report name/description/columns
         */
        $reportSummary = new ReportSummaryGenerator( $this->cache );
        $custom = collect($reportSummary->toJson($Report));

        $merge = $custom->merge($paging);

        /*
        This sets the per_page size to 0 so it does not show the MAX_PAGING_LIMIT number
         */
        if ($paging_length == self::MAX_PAGING_LIMIT) {
            $merge['per_page'] = 0;
        }

        return $merge;
    }

}
