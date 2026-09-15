<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Container #{{ $container->id }}</title>
    <style>
        @page { margin: 0; }
        body {
            padding-top: 10rem;
            margin: 0 auto;
            text-align: center;
            font-family: sans-serif;
        }
        svg { margin-top: 20px; }
    </style>
</head>
<body onload="window.print()">
    <img src="{{ $logo }}" alt="Logo" style="height:200px">
    <h1>Type: {{ $container->type }}</h1>
    <h1>Car Number: {{ $plate }}</h1>
    {!! $svg !!}
</body>
</html>
