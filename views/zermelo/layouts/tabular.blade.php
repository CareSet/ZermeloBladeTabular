<html>
<head>

    <title>{{ $presenter->getReport()->getReportName()  }}</title>

    <link href="//use.fontawesome.com/releases/v5.2.0/css/all.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href='{{ asset("vendor/CareSet/bootstrap/css/bootstrap.min.css") }}' />
    <link rel="stylesheet" type="text/css" href='{{ asset("vendor/CareSet/css/caresetreportengine.report.css") }}' />
    <link rel="stylesheet" type="text/css" href='{{ asset("vendor/CareSet/datatables/datatables.min.css") }}' />

</head>
<body>


@include('Zermelo::tabular')

</body>
</html>

