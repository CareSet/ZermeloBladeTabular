<div id="app">
	<div class="container-fluid">
		<div>
			<h1> {{ $presenter->getReport()->getReportName()  }}</h1>
		</div>
		<div>
			{!! $presenter->getReport()->getReportDescription() !!}
		</div>

		<div id="user-variables" style="display:none">
			<input type="hidden" id="clear_cache" value=""/>
			<input type="hidden" id="cache_expires" value=""/>
		</div>

		<div style='display: none' id='json_error_message' class="alert alert-danger" role="alert">

		</div>

		<table class="display table table-bordered table-condensed table-striped table-hover" id="report_datatable" style="width:100%;"></table>
	</div>

	<div id="bottom_locator" style="
		position: fixed;
		bottom: 10px;
	"></div>


	<!-- Data View Modal -->
	<div class="modal fade" id="current_data_view" tabindex="-1" role="dialog" aria-labelledby="current_data_view" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<form id="sockets-form">
					<div class="modal-header">
						<h5 class="modal-title" id="exampleModalLabel">Data Options</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						@if ($presenter->getReport()->hasActiveWrenches())
						<div class="row">
							<div class="col-5">
								<div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
									@foreach ($presenter->getReport()->getActiveWrenches() as $wrench)
										<a class="nav-link {{ ($loop->first) ?  'active' : '' }}" id="v-pills-{{$wrench->id}}-tab" data-toggle="pill" href="#v-pills-{{$wrench->id}}" role="tab" aria-controls="v-pills-{{$wrench->id}}" aria-selected="true">{{ $wrench->wrench_label }}</a>
									@endforeach
								</div>
							</div>
							<div class="col-7">
								<div class="tab-content" id="v4-pills-tabContent">
									@foreach ($presenter->getReport()->getActiveWrenches() as $wrench )
										<div class="tab-pane fade show {{ ($loop->first) ?  'active' : '' }}" id="v-pills-{{$wrench->id}}" role="tabpanel" aria-labelledby="v-pills-{{$wrench->id}}-tab">
											@foreach ( $wrench->sockets as $socket )
												<div class="custom-control custom-radio">
													<input {{ $presenter->getReport()->isActiveSocket($socket->id) ? 'checked' : '' }} type="radio" data-wrench-id="{{$wrench->id}}" data-socket-id="{{$socket->id}}" id="wrench-{{$wrench->id}}-socket-{{$socket->id}}" name="wrench-{{$wrench->id}}-socket" data-wrench-label="{{ $wrench->wrench_label }}" data-socket-label="{{$socket->wrench_label}}" class="custom-control-input">
													<label class="custom-control-label" for="wrench-{{$wrench->id}}-socket-{{$socket->id}}">{{$socket->wrench_label}}</label>
												</div>
											@endforeach
										</div>
									@endforeach
								</div>
							</div>
						</div>
						@else
						<!-- we only get here if there are no active wrenches -->
						<div class="row">
							<div class='col-12'>
								No Data Options have been configured for this report
							</div>
						</div>
						@endif

					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
						<button type="button" id="save-sockets" class="btn btn-primary">Save changes</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Report Download Modal -->
	<div class="modal fade" id="report_download_modal" tabindex="-1" role="dialog" aria-labelledby="report_download_modal" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<form id="report-download-form" method="POST" action="{!! $presenter->getDownloadUri() !!}">
					<div class="modal-header">
						<h5 class="modal-title" id="exampleModalLabel">Download Options</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<div class="card">
							<div class="card-body">
								<h5 class="card-title">Data View Download Options</h5>
								<table class="table table-striped table-bordered" id="download-data-options-table">
								</table>
								<div class="form-check">
									<input type="checkbox" class="use_current_data_view" id="use_current_data_view" checked>
									<label class="use_current_data_view" for="use_current_data_view">Download with Current Data Options</label>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
						<button type="button" id="initiate-report-download" class="btn btn-primary">Download</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript" src="/vendor/CareSet/js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="/vendor/CareSet/datatables/datatables.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/popper.min.js"></script>
<script type="text/javascript" src="/vendor/CareSet/bootstrap/js/bootstrap.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/moment.min.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/daterangepicker.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/jquery.doubleScroll.js"></script>
<script type="text/javascript" src="/vendor/CareSet/js/jquery.dataTables.yadcf.js"></script>

<script type="text/javascript">

    $(document).ready(function() {

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var columnMap = [];
        var fixedColumns = null;

        // Socket API payload
        var sockets = [];
        var activeWrenchNames = [];

        // Refresh sockets on page reload, in case we had options set, and did a "refresh"
        refresh_sockets();

        function refresh_sockets() {

            let form_data = $("#sockets-form").serializeArray();
            // Empty sockets array before we refill it
            sockets = [];
            activeWrenchNames = [];

            jQuery.each( form_data, function( i, field ) {
                let name = field.name;
                let isOn = ( field.value == 'on');
                var id = $('input[name='+name+']:checked').attr('id');
                if (isOn) {
                    let wrenchId = $("#" + id).attr('data-wrench-id');
                    let socketId = $("#" + id).attr('data-socket-id');
                    sockets.push({
                        wrenchId: wrenchId,
                        socketId: socketId
                    });

                    // Now store the labels
                    let wrenchLabel = $('#'+id).attr('data-wrench-label');
                    let socketLabel = $('#'+id).attr('data-socket-label');
                    activeWrenchNames.push({
                        wrenchLabel: wrenchLabel,
                        socketLabel: socketLabel
                    });
                }
            });
		}

        $("#save-sockets").click( function(e) {
            // Get the sockets from the Data Options form
			refresh_sockets();
            $('#current_data_view').modal('toggle');
            $("#report_datatable").DataTable().ajax.reload();
        });

        // On the report download modal, click "download" event
        $("#initiate-report-download").click( function(e) {
            e.preventDefault();
            e.stopPropagation();
			let form = $("#report-download-form");
			// Clear the hidden data view options
			$("#data-view-options-form-download").empty();

			// If we do not want to dowwnload with data view options,
			// empty sockets array.
			let userCurrentDataView = ( $("#use_current_data_view").is(":checked") );
			if ( userCurrentDataView == false ) {
                sockets = [];
			}

			let post_data = {
			    "_token" : '{{csrf_token()}}',
                "filter": '',
                "clear_cache": $("#clear_cache").val(),
                "sockets": sockets // Pass sockets for "Data Options"
			};

            // Submit the form to download content
			let downloadURI = "{{ $presenter->getDownloadUri() }}";
            let param = decodeURIComponent( $.param(post_data) );
            $.get( downloadURI, param, function( data, textStatus, jqXHR ) {
                const a = document.createElement("a");
                document.body.appendChild(a);
                a.style = "display: none";
                const blob = new Blob([data], {type: "octet/stream"}),
                    url = window.URL.createObjectURL(blob);
                a.href = url;
                var header = jqXHR.getResponseHeader('Content-Disposition');
                var filename = header.match(/filename="(.+)"/)[1];
                a.download = filename;
                a.click();
                window.URL.revokeObjectURL(url);
            }).done( function() {
                $('#report_download_modal').modal('toggle');
			}, 'json');

        });

        function set_cache_timer()
		{
            setInterval(function(){
                // Check to see if cache is about to expire (<1 minute)
				var cacheExpires = Date.parse( $("#cache_expires").val() );
				var now = Date.now();
				if ( ( cacheExpires - now ) > 0 &&
					( cacheExpires - now ) < 10000 ) {
				    if ( $("#cache-icon").hasClass( "text-warning" ) ) {
                        $("#cache-icon").removeClass("text-danger");
                        $("#cache-icon").removeClass("text-warning");
                        $("#cache-icon").addClass("text-primary");
					} else {
                        $("#cache-icon").removeClass("text-primary");
                        $("#cache-icon").removeClass("text-danger");
                        $("#cache-icon").addClass("text-warning");
					}

				} else if ( ( now - cacheExpires ) > 0 ) {
                    $("#cache-icon").removeClass("text-primary");
                    $("#cache-icon").removeClass("text-danger");
                    $("#cache-icon").addClass("text-warning");
				}
            }, 1000 );
		}

        set_cache_timer();
        var passthrough_params = {!! $presenter->getReport()->getRequestFormInput( true ) !!};
        var param = decodeURIComponent( $.param(passthrough_params) );
        $.getJSON(
            '{{ $presenter->getSummaryUri()."/".urlencode($presenter->getReport()->getRequestFormInput(false)) }}',
			param
		).fail(function( jqxhr, textStatus, error) {

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

            var formatHeader = function (header_data, columnIdx) {
                var jHtmlObject = jQuery('<p>' + header_data + '</p>');
                var parent = jQuery("<p>").append(jHtmlObject);
                parent.find(".yadcf-filter ").remove();
                var newHtml = parent.text();
                return newHtml;
            };

            var buttons = [
                {
                    name: 'dataview',
                    text: 'Data Options',
                    className: 'text-icon',
                    action: function(e,dt,node,config) {
                        $('#current_data_view').modal('toggle');
                    }
                },
                {
                    extend: 'colvis',
                    text: '&nbsp;<span class="fa fa-columns"></span>&nbsp;',
                    titleAttr: 'Column Visibility',
                   // init: function ( dt, node, config ) { $(node).tooltip(); }
                },
                {
                    name: 'Expand',
                    text: '&nbsp;<span class="fa fa-expand"></span>&nbsp;',
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
                    text: '&nbsp;<span class="fa fa-download"></span>&nbsp;',
                    titleAttr: 'Download CSV',
                    init: function ( dt, node, config ) { $(node).tooltip(); },
					action: function(e,dt,node,config) {

                        // Set up the display for the download options before we show the modal

                        // Add currrently selected options to download form
                        refresh_sockets();

                        if ( sockets.length == 0 ) {
                            $("#download-data-options-table").html("No data view options in use");
                            $("#use_current_data_view").prop("disabled", true);
						} else {

                            // Now dynamically pupulate the download options table with our current sockets
                            $("#download-data-options-table").html("");
                            jQuery.each(activeWrenchNames, function (key, option) {
                                $("#download-data-options-table").append("<tr><td>" + option.wrenchLabel + "</td><td>" + option.socketLabel + "</td></tr>");
                            });
                        }

                        $('#report_download_modal').modal('toggle');
					}
                },
                {
                    extend: 'print',
                    text: '&nbsp;<span class="fa fa-print"></span>&nbsp;',
                    title:  function() {
                        var title = window.document.title;
                        return title;
                    },
                    titleAttr: 'Print',
                    init: function ( dt, node, config ) { $(node).tooltip(); }
                },
                {
                    extend: 'collection',
                    name: 'cache',
                    attr: {
                        id: 'cache-meta-button'
                    },
                    autoClose: true,
                    text: '&nbsp;<span id="cache-icon" class="fa fa-database"></span>&nbsp;',
                    className: 'cache-meta-info',
                    init: function (dt, node, config) {
                        var cache_enabled = data.cache_meta_cache_enabled;
                        var generated_this_request = data.cache_meta_generated_this_request;
                        if ( cache_enabled ) {
                            if ( !generated_this_request ) {
                                $(node).find(".fa-database").addClass("text-danger");
                            } else {
                                $(node).find(".fa-database").addClass("text-primary");
                            }
                        }
                        var info = "Last Generated: " + data.cache_meta_last_generated+"<br/>";
                        info += "Expires: " + data.cache_meta_expire_time;

                        $("#cache_expires").val( data.cache_meta_expire_time );

                        $(node).tooltip({
							html: true,
                            title: info,
                            template: '<div class="tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner large"></div></div>'
                        });
                    },
					buttons : [
                        {
                            text: 'Clear Cache',
                            action: function ( e, dt, node, config ) {

                                $("#clear_cache").val( true );
                                $("#report_datatable").DataTable().ajax.reload( function ( json ) {
                                    var cache_enabled = json.cache_meta_cache_enabled;
                                    var generated_this_request = json.cache_meta_generated_this_request;
                                    if ( cache_enabled ) {
                                        if ( !generated_this_request ) {
                                            $("#cache-icon").removeClass("text-primary");
                                            $("#cache-icon").addClass("text-danger");
                                        } else {
                                            $("#cache-icon").removeClass("text-danger");
                                            $("#cache-icon").addClass("text-primary");
                                        }
                                    }
                                    var info = "Last Generated: " + json.cache_meta_last_generated+"<br/>";
                                    info += "Expires: " + json.cache_meta_expire_time;

                                    $("#cache_expires").val( json.cache_meta_expire_time );
                                    $("#cache-meta-button").attr("data-original-title", info);
                                    $("#clear_cache").val( "" );
								});

                            }
                        }
					]
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


            var defaultPageLength = localStorage.getItem("Zermelo_defaultPlageLength");
            if ( defaultPageLength == "undefined" ) {
                defaultPageLength = 50;
            }

            var detailRows = [];
            var ReportTable = $('#report_datatable').DataTable( {

                dom: '<"report-table-wrapper"<"table-control"<"float-left control-box"Blf<"after-menu-addition">><"float-right"ip><"clearfix"><"#report_menu_wrapper">>tr>',
                stateSave: true,
                colReorder: true,
                scrollX: true,
                scrollY: '200px',

                /*
                    Define the length, first array is 'visible' text,
                    and the 2nd array is what gets sent back to the server
                */
                lengthMenu: [50,100,500,1000],
                pageLength: parseInt( defaultPageLength ),

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
                    var page = 1;
                    var length = defaultPageLength;
                    if ( data.length != "undefined" ) {
                        page = (data.start / data.length) + 1;
                        length = data.length;
					}

                    var passthrough_params = {!! $presenter->getReport()->getRequestFormInput( true ) !!};
                    var merge_get_params = {
                        'token': '{{ $presenter->getToken() }}',
                        'page': parseInt(page),
                        "order": callbackOrder,
                        "length": parseInt(length),
                        "filter": searches,
						"clear_cache": $("#clear_cache").val() ,
						"sockets": sockets // Pass sockets for "Data Options"
                    };
                    var merge = $.extend({}, passthrough_params, merge_get_params)
                    localStorage.setItem("Zermelo_defaultPlageLength",length);
                    $("[name='report_datatable_length']").val(length);

                    var merge_clone = $.extend({},merge);
                    delete merge_clone['token'];

                    var param = decodeURIComponent( $.param(merge) );
                    $.getJSON('{{ $presenter->getReportUri() }}', param
                    ).always(function(data) {
                        settings.json = data; // Make sure to set setting so callbacks have data
                        callback({
                            data: data.data,
                            recordsTotal: data.total,
                            recordsFiltered: data.total,
                        });
                        $("#clear_cache").val("");

                        var cache_enabled = data.cache_meta_cache_enabled;
                        var generated_this_request = data.cache_meta_generated_this_request;
                        if ( cache_enabled ) {
                            if ( !generated_this_request ) {
                                $("#cache-icon").removeClass("text-primary");
                                $("#cache-icon").removeClass("text-warning");
                                $("#cache-icon").addClass("text-danger");
                            } else {
                                $("#cache-icon").removeClass("text-danger");
                                $("#cache-icon").removeClass("text-warning");
                                $("#cache-icon").addClass("text-primary");
                            }
                        }
                        var info = "Last Generated: " + data.cache_meta_last_generated+"<br/>";
                        info += "Expires: " + data.cache_meta_expire_time;

                        $("#cache_expires").val( data.cache_meta_expire_time );
                        $("#cache-meta-button").attr("data-original-title", info);
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

//            $("body").on( 'click', '.dt-button', function() {
//                if(fixedColumns !== null)
//                {
//                    $("#report_table_freeze_selector").trigger("click");
//                }
//            });

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
