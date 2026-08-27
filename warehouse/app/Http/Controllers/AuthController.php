<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard.index');
        }

        return view('auth.login');
    }

    /**
     * Handle authentication attempt with username or email.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required'    => 'Username atau Email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $loginInput = trim($credentials['login']);
        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($fieldType, $loginInput)->first();
        if (!$user && $fieldType === 'username') {
            $user = User::where('email', $loginInput)->first();
        }

        if ($user && Hash::check($password, $user->password)) {
            if (isset($user->status) && in_array(strtolower($user->status), ['nonaktif', 'inactive'])) {
                return back()->withErrors([
                    'login' => 'Akun Anda dinonaktifkan. Silakan hubungi Administrator.',
                ])->onlyInput('login');
            }

            Auth::login($user, $remember);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard.index'))
                ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        return back()->withErrors([
            'login' => 'Username/Email atau Password yang Anda masukkan tidak sesuai.',
        ])->onlyInput('login');
    }

    /**
     * Handle guest mode login (read-only access).
     */
    public function guestLogin(Request $request)
    {
        $guest = User::firstOrCreate(
            ['username' => 'guest'],
            [
                'name'       => 'Guest Viewer',
                'email'      => 'guest@stockmin.com',
                'department' => 'General Viewer',
                'role'       => 'Guest',
                'status'     => 'active',
                'password'   => Hash::make('guest'),
            ]
        );

        Auth::login($guest, false);
        $request->session()->regenerate();

        return redirect()->route('dashboard.index')
            ->with('info', 'Anda masuk dalam mode Tamu (Guest Mode) dengan akses terbatas.');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
