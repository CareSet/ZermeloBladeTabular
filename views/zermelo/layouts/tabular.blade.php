<html>
<head>

<title>{{ $presenter->getReport()->getReportName()  }}</title>

<link rel="stylesheet" type="text/css" href="/vendor/CareSet/css/bootstrap.min.css"/>
<link rel="stylesheet" type="text/css" href="/vendor/CareSet/css/datatables.min.css"/>
<link href="//use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="/vendor/CareSet/css/caresetreportengine.report.css"/>
</head>
<body>


@include('Zermelo::tabular')

</body>
</html>

