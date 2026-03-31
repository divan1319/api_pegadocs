<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <title>Laravel</title>
        @vite(['resources/js/app.ts'])
    </head>
    <body >
        <div id="app" class="isolate"></div>
    </body>
</html>
