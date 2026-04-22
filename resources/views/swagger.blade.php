<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Flight Manifest API Swagger</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>
        body {
            margin: 0;
            background: #f4f6fb;
            font-family: sans-serif;
        }

        .swagger-ui .topbar {
            display: none;
        }
    </style>
</head>
<body>
<div id="swagger-ui"></div>

<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
    window.addEventListener('load', function () {
        window.SwaggerUIBundle({
            url: '{{ route('swagger.spec') }}',
            dom_id: '#swagger-ui',
            deepLinking: true,
            displayRequestDuration: true,
            defaultModelsExpandDepth: 1,
        });
    });
</script>
</body>
</html>
