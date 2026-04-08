<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Oferta;
use App\Models\Usuari;
use Illuminate\Http\Request;

trait AuthorizesApiRequests
{
    protected function currentUser(Request $request): Usuari
    {
        $user = $request->user();

        abort_if(! $user instanceof Usuari, 401, 'Unauthenticated.');

        $user->loadMissing('rol', 'client');

        return $user;
    }

    protected function requireRoles(Request $request, array $roles): Usuari
    {
        $user = $this->currentUser($request);

        abort_if(
            ! in_array($this->roleName($user), $roles, true),
            403,
            'You are not allowed to perform this action.'
        );

        return $user;
    }

    protected function roleName(Usuari $user): ?string
    {
        $user->loadMissing('rol');

        return $user->rol?->rol;
    }

    protected function hasRole(Usuari $user, string ...$roles): bool
    {
        return in_array($this->roleName($user), $roles, true);
    }

    protected function authorizeOfferAccess(Usuari $user, Oferta $oferta): void
    {
        if ($this->hasRole($user, 'admin', 'operator', 'commercial')) {
            return;
        }

        if (
            $this->hasRole($user, 'client')
            && $user->client_id !== null
        ) {
            abort_unless(
                Oferta::query()
                    ->whereKey($oferta->getKey())
                    ->where('client_id', $user->client_id)
                    ->exists(),
                403,
                'You are not allowed to access this offer.'
            );

            return;
        }

        abort(403, 'You are not allowed to access this offer.');
    }
}
