<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Fatal Database Error</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.gstatic.com">
        <link href="https://fonts.googleapis.com/css2?family=Nunito&display=swap" rel="stylesheet">

        <style>
            html, body {
                background-color: #1a202c;
                color: #a0aec0;
                font-family: 'Nunito', sans-serif;
                height: 100%;
                margin: 0;
            }

            .center {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100%;
            }

            .error {
                max-width: 410px;
                text-align: justify;
                font-size: 15px;
                line-height: 1.2;
            }

            .title {
                font-size: 20px;
                margin-bottom: 5px;
                padding-bottom: 5px;
                border-bottom: 1px solid #a0aec0;
                color: #b3becc;
            }
        </style>
    </head>
    <body>
        <div class="center">
            <div class="error">
                <div class="title">{{ $message }}</div>
                <div class="message">
                    A fatal error occured while trying to access the database. Please check your .env configuration and try again.
                </div>
            </div>
        </div>
    </body>
</html>
