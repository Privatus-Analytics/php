<html>
<head>
<title>Home</title>
@privatusAnalytics
</head>
<body>
<button @privatusEvent('Export CSV', ['format' => 'csv'])>Export</button>
<a href="/pricing" @privatusClick('pricing.cta')>Pricing</a>
<a href="/bad" @privatusClick('not valid')>Bad</a>
</body>
</html>
