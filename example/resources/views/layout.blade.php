<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>@yield('title', 'Privatus conformance')</title>
@privatusAnalytics(['props' => $props, 'queue_stub' => true])
</head>
<body>
@yield('body')
</body>
</html>
