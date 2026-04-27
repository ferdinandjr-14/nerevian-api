<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ApiException;
use App\Models\Oferta;
use App\Models\Usuari;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait AuthorizesApiRequests
{
    protected function currentUser(Request $request): Usuari
    {
        $user = $request->user();

        if (! $user instanceof Usuari) {
            throw ApiException::unauthorized();
        }

        $user->loadMissing('rol', 'client');

        return $user;
    }

    protected function requireRoles(Request $request, array $roles): Usuari
    {
        $user = $this->currentUser($request);

        if (! in_array($this->roleName($user), $roles, true)) {
            throw ApiException::forbidden();
        }

        return $user;
    }

    protected function roleName(Usuari $user): ?string
    {
        $user->loadMissing('rol', 'client');

        if ($user->rol === null) {
            return null;
        }

        return $user->rol->rol;
    }

    protected function hasRole(Usuari $user, string ...$roles): bool
    {
        return in_array($this->roleName($user), $roles, true);
    }

    protected function applyOfferVisibility(Builder $query, Usuari $user): void
    {
        if ($this->hasRole($user, 'admin', 'operator')) {
            return;
        }

        if ($this->hasRole($user, 'client')) {
            if ($user->client_id === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where('client_id', $user->client_id);

            return;
        }

        if ($this->hasRole($user, 'commercial')) {
            $query->where('agent_comercial_id', $user->id);

            return;
        }

        $query->whereRaw('1 = 0');
    }

    protected function authorizeOfferAccess(Usuari $user, Oferta $oferta): void
    {
        if ($this->hasRole($user, 'admin', 'operator')) {
            return;
        }

        if (
            $this->hasRole($user, 'client')
            && $user->client_id !== null
            && (int) $user->client_id === (int) $oferta->client_id
        ) {
            return;
        }

        if (
            $this->hasRole($user, 'commercial')
            && $oferta->agent_comercial_id !== null
            && (int) $oferta->agent_comercial_id === (int) $user->id
        ) {
            return;
        }

        throw ApiException::forbidden();
    }

    protected function authenticationDisabled(): bool
    {
        return false;
    }
}
