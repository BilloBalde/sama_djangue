<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('paiement.{parentId}', function ($user, $parentId) {
    // Autorise uniquement le parent concerné à écouter ce canal
    return (int) $user->id === (int) $parentId;
});
