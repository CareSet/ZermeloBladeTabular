<html>
<head>

<title>{{ $presenter->getReport()->getReportName()  }}</title>


<link rel="stylesheet" type="text/css" href="/vendor/CareSet/bootstrap/css/bootstrap.min.css"/>
<link rel="stylesheet" type="text/css" href="/vendor/CareSet/css/dataTables.bootstrap4.min.css"/>
<link href="//use.fontawesome.com/releases/v5.2.0/css/all.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="/vendor/CareSet/css/caresetreportengine.report.css"/>
</head>
<body>


@include('Zermelo::tabular')

</body>
</html>

