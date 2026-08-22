<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function add(Request $request): RedirectResponse
    {
        $id = $request->validate(['product_id' => 'required|string'])['product_id'];
        $p = Product::query()->findOrFail($id);
        $cart = $request->session()->get('cart', []);
        $cart[$p->id] = ['title' => $p->title, 'price' => $p->price];
        $request->session()->put('cart', $cart);
        return back()->with('ok', 'Ajouté au chaudron');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $request->session()->forget('cart');
        return back()->with('ok', 'Commande démo (prod = Stripe). VOD liée à débloquer via grant.');
    }
}
