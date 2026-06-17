<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle a login request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $login = $request->validated('login');
        $password = $request->validated('password');

        // Cari user by email atau username
        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();

        // User tidak ditemukan
        if (! $user) {
            return back()
                ->withInput($request->only('login'))
                ->withErrors([
                    'login' => 'No account found with that email or username.',
                ]);
        }

        // Password tidak cocok
        if (! Hash::check($password, $user->password)) {
            return back()
                ->withInput($request->only('login'))
                ->withErrors([
                    'password' => 'The password you entered is incorrect.',
                ]);
        }

        // Akun tidak aktif
        if (! $user->is_active) {
            return back()
                ->withInput($request->only('login'))
                ->withErrors([
                    'login' => 'Your account has been deactivated. Please contact your administrator.',
                ]);
        }

        // Login user
        Auth::login($user, $request->boolean('remember'));

        // Regenerate session
        $request->session()->regenerate();

        // Set customer scope
        session()->put('customer_scope', $user->customer_id);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
