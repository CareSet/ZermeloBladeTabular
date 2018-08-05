<div class="container-fluid">
	<div>
		<h1> {{ $presenter->getReport()->getReportName()  }}</h1>
	</div>
	<div>
		{!! $presenter->getReport()->getReportDescription() !!}
	</div>

	<div style='display: none' id='json_error_message' class="alert alert-danger" role="alert">

	</div>

	<div class="row">
		<div class="col-xs-12">
			<table class="table table-bordered table-condensed table-striped table-hover" id="report_datatable" style="width:100%"></table>
		</div>
	</div>
</div>


<div id="bottom_locator" style="
    position: fixed;
    bottom: 10px;
"></div>


<script type="text/javascript" src="/vendor/CareSet/js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/datatables.min.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/jquery.dataTables.yadcf.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/moment.min.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/daterangepicker.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/d3.v4.min.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/datatables.fixedcolumns.destroy.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/jquery.doubleScroll.js"></script>

<script type="text/javascript">

    $(function() {

        var columnMap = [];
        var fixedColumns = null;

        $.getJSON('{{ $presenter->getSummaryUri() }}',
            {
                'token': '{{ $presenter->getToken() }}',
                'request-form-input': '{!! urlencode($presenter->getReport()->getRequestFormInput(true)) !!}',
            }).fail(function( jqxhr, textStatus, error) {

		console.log(jqxhr);
		console.log(textStatus);
		console.log(error);
		
		var is_admin = true; //this should be set via a call to the presenter

		if(is_admin){
			if(typeof jqxhr.responseJSON.message !== 'undefined'){
				$('#json_error_message').html("<h1> You had a error </h1> <p> " + jqxhr.responseJSON.message + "</p>");
			}else{
				$('#json_error_message').html("<h1> You had a error, bad enough that there was no JSON  </h1>");
			}
		}else{
			$('#json_error_message').html("<h1> There was an error generating this report</h1>");	
		}
		$('#json_error_message').show();	

	    }).done(function(data) {


            function resizeTable()
            {
                var current_top = $(".dataTables_scrollBody").offset().top;
                var fixed_bottom = $("#bottom_locator").offset().top;

                var new_height = fixed_bottom - current_top;
                $(".dataTables_scrollBody").css('height',new_height+'px');
                $("#report_datatable").dataTable().fnSettings().oScroll.sY = new_height+'px';

            }

            var formatHeader = function (data, columnIdx) {
                var jHtmlObject = jQuery('<p>' + data + '</p>');
                var parent = jQuery("<p>").append(jHtmlObject);
                parent.find(".yadcf-filter ").remove();
                var newHtml = parent.text();
                return newHtml;
            };

            var buttons = [
                {
                    name: 'dataview',
                    text: 'Current Data View',
                    className: 'text-icon',
                    action: function(e,dt,node,config) {
                        $('#data-option-collapse').collapse('toggle');
                    }
                },
                {
                    extend: 'colvis',
                    text: '&nbsp;<i class="fa fa-columns"></i>&nbsp;',
                    titleAttr: 'Column Visibility',
                    init: function ( dt, node, config ) { $(node).tooltip(); }
                },
                {
                    text: '&nbsp;<select id="report_table_freeze_selector"><option value="" disabled>Freeze Column</option><option value=0 selected>None</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option></select>',
                    titleAttr: 'Freeze Column',
                    init: function ( dt, node, config ) { $(node).tooltip(); }
                },
                {
                    name: 'Expand',
                    text: '&nbsp;<i class="fa fa-expand"></i>&nbsp;',
                    titleAttr: 'Maximize View',
                    init: function ( dt, node, config ) { $(node).tooltip(); },
                    action: function(e,dt,node,config) {
                        $(".report-table-wrapper").toggleClass('full_screen');
                        $(node).toggleClass('toggled');
                        resizeTable();
                    }
                },
                {
                    extend: 'csv',
                    text: '&nbsp;<i class="fa fa-download"></i>&nbsp;',
                    titleAttr: 'Download CSV',
                    init: function ( dt, node, config ) { $(node).tooltip(); },
                    exportOptions: {
                        format: {
                            header: formatHeader
                        }
                    }
                },
                {
                    extend: 'print',
                    text: '&nbsp;<i class="fa fa-print"></i>&nbsp;',
                    title:  function() {
                        var title = window.document.title;
                        return title;
                    },
                    titleAttr: 'Print',
                    init: function ( dt, node, config ) { $(node).tooltip(); }
                }
            ];


            var columnHeaders = []; /* for DataTables */
            var index = 0;



            var filter_array = [];
            data.columns.forEach(function(item) {

                /*
                    This is for yadcf column filter
                */
                filter_array.push({
                    column_number: index,
                    filter_type: "text",
                    filter_default_label: "Refine",
                    filter_reset_button_text: false,
                    filter_delay: 500
                });


                /*
                    Restructure the columnMap based on the field_name as the key
                    This will make looking up the field meta data easier
                */
                columnMap["_"+item.field] = {
                    index: index++,
                    title: item.title,
                    field: item.field,
                    format: item.format,
                    tags: item.tags
                };

                /*
                    If Column has summary, push the summary to the columnMap
                */
                if(item.hasOwnProperty('summary'))
                {
                    columnMap["_"+item.field]['summary'] = item.summary;
                }

                /*
                    Create the header to be used by DataTable.
                    Also add custom class based on the format and tags of the column
                */
                var header_element = {
                    data: item.field,  /* field it uses from the data */
                    title: item.title, /* the title to display */
                };


                /*
                    If the column is a numeric-type column
                    or if the tag 'RIGHT' exists,
                    automatically add the class text-right
                */
                if(
                    item.format=="NUMBER" ||
                    item.format=="DECIMAL" ||
                    item.format=="CURRENCY" ||
                    item.format =="PERCENT"	||
                    $.inArray("RIGHT",item.tags) >= 0
                )
                    header_element['className'] = 'text-right';



                /*
                    If the tag 'BOLD' exists, either append the className or set it
                    depending on if there is already an existing value
                */
                if($.inArray("BOLD",item.tags) >= 0)
                {
                    if(header_element.hasOwnProperty("className"))
                        header_element['className']+=' text-bold';
                    else
                        header_element['className']='text-bold';
                }

                /*
                    If the tag 'ITALIC' exists, either append the className or set it
                    depending on if there is already an existing value
                */
                if($.inArray("ITALIC",item.tags) >= 0)
                {
                    if(header_element.hasOwnProperty("className"))
                        header_element['className']+=' text-italic';
                    else
                        header_element['className']='text-italic';
                }

                /*
                    If tag 'HIDDEN' exists, set the visible flag to false to hide the column
                */
                if($.inArray("HIDDEN",item.tags) >= 0)
                {
                    header_element['visible'] = false;
                }


                columnHeaders.push(header_element);

            }); /* end forEach data.columns */


            var defaultPageLength = 50;
            if(localStorage.getItem("Zermelo_defaultPlageLength"))
            {
                defaultPageLength = localStorage.getItem("Zermelo_defaultPlageLength");
            }

            var detailRows = [];
            var ReportTable = $('#report_datatable').DataTable( {
                pageLength: defaultPageLength,

                dom: '<"report-table-wrapper"Z<"table-control"<"pull-left control-box"Blf<"after-menu-addition">><"pull-right"ip><"clearfix"><"#report_menu_wrapper">>tr>',
                stateSave: true,
                colReorder: true,
                scrollX: true,
                scrollY: '200px',

                /*
                    Define the length, first array is 'visible' text,
                    and the 2nd array is what gets sent back to the server
                */
                lengthMenu: [
                    [50,100,500,1000],
                    [50,100,500,1000]
                ],

                buttons: buttons,

                /*
                    Disable the search delay, use the Enter Key to trigger a search
                */
                searchDelay: 500,

                /*
                    This is the header decoration,
                    we need to define the header fetching the data
                */
                columns: columnHeaders,


                /*
                    Override every ajax call to the server.
                    Pass over the sort, filter, and what records to fetch
                */
                ajax: function (data, callback, settings) {

                    var columns = data.columns;
                    var order = data.order;
                    var searches = [];


                    columns.forEach(function(item)
                    {
                        if(item.search.value != "")
                        {
                            var pair = {};
                            pair[ item.data ] = item.search.value
                            searches.push(pair);
                        }
                    });

                    if(data.search.value!="")
                    {
                        searches.push({
                            "_": data.search.value
                        });
                    }

                    /*
                        Support multi column ordering
                    */
                    var callbackOrder = [];
                    order.forEach(function(item) {
                        var pair = {};
                        pair[ columns[item.column].data ] = item.dir;
                        callbackOrder.push(pair);
                    });

                    /*
                        Fetch the data via getJSON and pass it back using the 'callback' provided by DataTable
                    */

                    var passthrough_params = {!! $presenter->getReport()->getRequestFormInput( true ) !!};
                    var merge_get_params = {
                        'data-option': '{{ $presenter->getReport()->GetBoltId() }}',
                        'token': '{{ $presenter->getToken() }}',
                        'page': (data.start / data.length) + 1,
                        "order": callbackOrder,
                        "length": data.length,
                        "filter": searches,
                    };
                    var merge = $.extend({}, passthrough_params, merge_get_params)
                    localStorage.setItem("Zermelo_defaultPlageLength",data.length);

                    var merge_clone = $.extend({},merge);
                    delete merge_clone['token'];

                    var param = decodeURIComponent( $.param(merge) );
                    $.getJSON('{{ $presenter->getReportUri() }}', param
                    ).always(function(data) {
                        callback({
                            data: data.data,
                            recordsTotal: data.total,
                            recordsFiltered: data.total,
                        });
                    });
                },


                /*
                    Send all processing to server side
                */
                serverSide: true,
                processing: true,
                paging: true,


                initComplete: function(settings, json) {
                    resizeTable();
                },


                rowCallback: function( row, data, index ) {

                    /*
                        Map each row according to the format and tags
                    */
                    var col_index = 0;

                    var order_reverse = $("#report_datatable").DataTable().colReorder.order();
                    var order=[];
                    $.each(order_reverse, function(i, el) {
                        order[el]=i;
                    });

                    for(var field in columnMap)
                    {
                        var value = columnMap[field];
                        field = field.toString().substring(1); /* strip out the _ */
                        var format = value['format'];
                        var tags = value['tags'];
                        var index = value['index'];


                        if(ReportTable.column(index).visible())
                        {
                            var new_index = order[col_index];
                            if(format=="CURRENCY")
                            {
                                data[field] = "$ "+(data[field]*1).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                            }
                            else if(format=="NUMBER")
                            {
                                data[field] = data[field].toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                            }
                            else if(format=="DECIMAL")
                            {
                                data[field] = (data[field]*1).toFixed(4);
                            }
                            else if(format=="PERCENT")
                            {
                                data[field] = (data[field]*100).toFixed(2) + " %";
                            }
                            else if(format=="URL")
                            {
                                data[field] = "<a href='"+data[field]+"'>"+data[field]+"</a>";
                            }
                            else if(format=="DATE")
                            {

                            }
                            else if(format=="DATETIME")
                            {

                            }
                            else if(format=="TIME")
                            {

                            } else if(format=="DETAIL")
                            {
                                $("td:eq("+new_index+")",row).addClass('details-control').attr('detail-field',field);
                                $("td:eq("+new_index+")",row).html('').attr('title',data[field]);
                                data[field]=$("<span class='hide'></span>").html(data[field])[0].outerHTML;
                            }
                            if(format!="DETAIL")
                            {
                                $("td:eq("+new_index+")",row).html(data[field]);
                            }
                            col_index++;
                        }
                    }

                } /* end rowCallback */


            }); /* end DataTable */

            ReportTable.on( 'column-reorder', function () {
                if(fixedColumns !== null)
                {
                    $("#report_datatable").dataTable().api().fixedColumns().update();
                }
            } );

            $("body").on( 'click', '.dt-button', function() {
                if(fixedColumns !== null)
                {
                    $("#report_table_freeze_selector").trigger("click");
                }
            });

            yadcf.init(ReportTable,filter_array);


            $("body").on("change","#report_table_freeze_selector",function()
            {
                if(fixedColumns !== null)
                {
                    fixedColumns.destroy();
                    fixedColumns = null;
                }
                var val = $(this).val()*1;

                if(val > 0)
                {
                    fixedColumns = new $.fn.dataTable.FixedColumns( $("#report_datatable").dataTable() , {
                        leftColumns: val
                    } );

                }
            });



            $('#report_datatable tbody').on( 'click', 'tr td.details-control', function () {
                var tr = $(this).closest('tr');
                var row = ReportTable.row( tr );
                var idx = $.inArray( tr.attr('id'), detailRows );

                if ( row.child.isShown() ) {
                    tr.removeClass( 'details' );
                    row.child.hide();

                    // Remove from the 'open' array
                    detailRows.splice( idx, 1 );
                }
                else if($(this)[0].hasAttribute('detail-field')) {
                    tr.addClass( 'details' );

                    var field_name = $(this).attr('detail-field');
                    var row_data = row.data();

                    var padder = $("<div></div>").html( $(row_data[field_name]).html() ).addClass('row_detail');
                    row.child( padder ).show();

                    // Add to the 'open' array
                    if ( idx === -1 ) {
                        detailRows.push( tr.attr('id') );
                    }
                }
            });


            $(window).resize(function() { resizeTable(); });

        }); /* end always on get Summary */


    });
</script>
