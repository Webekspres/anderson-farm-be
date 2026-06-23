<?php

use Illuminate\Support\Facades\Route;

it('does not register PUT routes under api v1', function () {
    $putRoutes = collect(Route::getRoutes())
        ->filter(fn ($route) => in_array('PUT', $route->methods(), true)
            && str_starts_with($route->uri(), 'api/v1'));

    expect($putRoutes)->toBeEmpty();
});
