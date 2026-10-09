<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('courier.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id || $user->hasAnyRole(['dev', 'manager']);
});

Broadcast::channel('invoices', function ($user) {
    return $user->hasRole('dev') || $user->hasPermissionTo('faktur-view', 'web') || $user->can('faktur-view');
});

Broadcast::channel('deliveries', function ($user) {
    return $user->hasRole('dev') || $user->hasPermissionTo('pengantaran-view', 'web') || $user->can('pengantaran-view');
});
