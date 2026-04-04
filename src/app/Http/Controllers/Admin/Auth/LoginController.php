<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 設計: is_active=false のアカウントはログイン不可
        $ok = Auth::guard('admin')->attempt(
            [...$credentials, 'is_active' => true],
            false,
        );

        if (! $ok) {
            return back()
                ->withErrors(['email' => __('メールアドレスまたはパスワードが正しくありません。')])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::guard('admin')->user();
        if ($user !== null) {
            $user->forceFill(['last_login_at' => now()])->save();
        }

        return redirect()->intended(route('admin.properties.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
