<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Instrument Sans', system-ui, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
                color: #fff;
            }

            .container {
                max-width: 800px;
                width: 100%;
            }

            .header {
                text-align: center;
                margin-bottom: 3rem;
            }

            h1 {
                font-size: 3rem;
                font-weight: 700;
                margin-bottom: 1rem;
            }

            .subtitle {
                font-size: 1.25rem;
                opacity: 0.9;
            }

            .buttons {
                display: flex;
                gap: 1rem;
                justify-content: center;
                margin-bottom: 3rem;
                flex-wrap: wrap;
            }

            .btn {
                padding: 1rem 2rem;
                border-radius: 0.5rem;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }

            .btn-primary {
                background: #fff;
                color: #667eea;
            }

            .btn-secondary {
                background: rgba(255, 255, 255, 0.2);
                color: #fff;
                backdrop-filter: blur(10px);
            }

            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            }

            .card {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                border-radius: 1rem;
                padding: 2rem;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            }

            .card h2 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .endpoints {
                list-style: none;
            }

            .endpoint {
                display: flex;
                align-items: center;
                padding: 1rem;
                margin-bottom: 0.5rem;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 0.5rem;
                gap: 1rem;
            }

            .method {
                padding: 0.25rem 0.75rem;
                border-radius: 0.25rem;
                font-size: 0.875rem;
                font-weight: 600;
                min-width: 70px;
                text-align: center;
            }

            .method-get { background: #10b981; }
            .method-post { background: #3b82f6; }
            .method-put { background: #f59e0b; }
            .method-delete { background: #ef4444; }

            .path {
                font-family: 'Monaco', 'Courier New', monospace;
                flex: 1;
            }

            .description {
                font-size: 0.875rem;
                opacity: 0.8;
            }

            .footer {
                text-align: center;
                margin-top: 2rem;
                opacity: 0.8;
            }

            @media (max-width: 768px) {
                h1 {
                    font-size: 2rem;
                }

                .endpoint {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .method {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🚀 User Management API</h1>
                <p class="subtitle">RESTful API dla zarządzania użytkownikami i ich adresami email</p>
            </div>

            <div class="buttons">
                <a href="{{ url('/api/documentation') }}" class="btn btn-primary">
                    📚 Dokumentacja API
                </a>
                <a href="{{ url('/api/health') }}" class="btn btn-secondary">
                    ❤️ Health Check
                </a>
            </div>

            <div class="card">
                <h2>Dostępne endpointy:</h2>
                <ul class="endpoints">
                    <li class="endpoint">
                        <span class="method method-get">GET</span>
                        <span class="path">/api/users</span>
                        <span class="description">Lista użytkowników</span>
                    </li>
                    <li class="endpoint">
                        <span class="method method-post">POST</span>
                        <span class="path">/api/users</span>
                        <span class="description">Utwórz użytkownika</span>
                    </li>
                    <li class="endpoint">
                        <span class="method method-get">GET</span>
                        <span class="path">/api/users/{id}</span>
                        <span class="description">Pobierz użytkownika</span>
                    </li>
                    <li class="endpoint">
                        <span class="method method-put">PUT</span>
                        <span class="path">/api/users/{id}</span>
                        <span class="description">Aktualizuj użytkownika</span>
                    </li>
                    <li class="endpoint">
                        <span class="method method-delete">DELETE</span>
                        <span class="path">/api/users/{id}</span>
                        <span class="description">Usuń użytkownika</span>
                    </li>
                    <li class="endpoint">
                        <span class="method method-post">POST</span>
                        <span class="path">/api/users/{id}/welcome</span>
                        <span class="description">Wyślij email powitalny</span>
                    </li>
                </ul>
            </div>

            <div class="footer">
                <p>Version 1.0.0 | Built with Laravel {{ app()->version() }}</p>
            </div>
        </div>
    </body>
</html>

