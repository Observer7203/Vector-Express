<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$routes = $app->make('router')->getRoutes();
echo "<pre>";
echo "Total routes: " . count($routes) . "\n\n";

foreach ($routes as $route) {
    $uri = $route->uri();
    if (str_starts_with($uri, 'api')) {
        echo $route->methods()[0] . " " . $uri . "\n";
    }
}
echo "</pre>";
