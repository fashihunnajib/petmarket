<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = auth()->user();

        if ($user?->hasRole('tenant')) {
            return redirect()->route('tenant.orders.index');
        }

        if ($user?->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return redirect('/dashboard');
    }
}
