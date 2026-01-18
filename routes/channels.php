<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('board.{boardId}', function ($user, $boardId) {
    return $user->boards()->where('id', $boardId)->exists();
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});