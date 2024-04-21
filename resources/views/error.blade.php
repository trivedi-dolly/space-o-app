<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 h-screen flex flex-col justify-center items-center">
    <div class="bg-white p-8 rounded shadow-md">
        <h1 class="text-2xl font-semibold text-red-600 mb-4">Error</h1>
        <p class="text-gray-700">{{ $message }}</p>
    </div>
</body>

</html>