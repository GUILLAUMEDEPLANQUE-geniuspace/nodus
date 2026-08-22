<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function form(): View
    {
        return view('auth');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if (! Auth::attempt($data, true)) {
            return back()->with('ok', 'Identifiants refusés.');
        }
        $request->session()->regenerate();
        return redirect('/')->with('ok', 'Connecté.');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'bio' => '',
            'avatar' => '/realms/luffy.jpg',
            'banner' => '/realms/sea-hero.jpg',
        ]);
        Auth::login($user);
        return redirect('/profil')->with('ok', 'Compte créé.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        return redirect('/')->with('ok', 'Déconnecté.');
    }
}
