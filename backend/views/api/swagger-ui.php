<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apple API Documentation - Swagger UI</title>
    <link rel="stylesheet" href="<?= $swaggerUiDist ?>swagger-ui.css" />
    <style>
        body {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="<?= $swaggerUiDist ?>swagger-ui-bundle.js"></script>
    <script src="<?= $swaggerUiDist ?>swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "<?= $specUrl ?>",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                defaultModelsExpandDepth: 1,
                docExpansion: "list",
                filter: true,
                tryItOutEnabled: true
            });

            window.ui = ui;
        };
    </script>
</body>
</html>
