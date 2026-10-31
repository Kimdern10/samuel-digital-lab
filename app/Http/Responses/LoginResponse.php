<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

/**
 * Sends the owner to the admin and clients to their portal after logging in.
 */
class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    /**
     * Create the HTTP response.
     *
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        $user = $request->user();
        $intended = (string) $request->session()->pull('url.intended');

        // Clients never get sent on to admin pages they cannot open.
        if ($intended !== '' && ($user?->is_admin || ! str_starts_with((string) parse_url($intended, PHP_URL_PATH), '/admin'))) {
            return redirect()->to($intended);
        }

        return redirect()->to(self::homeFor($user));
    }

    /**
     * The page a user starts on.
     */
    public static function homeFor(?User $user): string
    {
        return $user?->is_admin ? route('dashboard', absolute: false) : route('portal', absolute: false);
    }
}
