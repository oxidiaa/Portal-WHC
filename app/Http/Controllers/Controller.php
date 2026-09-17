<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Check if current user is guest (read-only mode).
     */
    protected function isGuest(): bool
    {
        return auth()->check() && (
            auth()->user()->username === 'guest' || 
            strtoupper(auth()->user()->role ?? '') === 'GUEST'
        );
    }

    /**
     * Abort if user is guest (for write operations).
     */
    protected function abortIfGuest(): void
    {
        if ($this->isGuest()) {
            abort(403, 'Akses ditolak: Mode Tamu (Guest Mode) hanya dapat melihat data dan tidak dapat melakukan perubahan.');
        }
    }
}
