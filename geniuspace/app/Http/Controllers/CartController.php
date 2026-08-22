<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function add(Request $request): RedirectResponse
    {
        $id = $request->validate(['product_id' => 'required|string'])['product_id'];
        $p = Product::query()->findOrFail($id);
        $cart = $request->session()->get('cart', []);
        $cart[$p->id] = ['title' => $p->title, 'price' => $p->price];
        $request->session()->put('cart', $cart);
        if ($request->integer('koc')) {
            $request->session()->put('koc', $request->integer('koc'));
            $request->session()->put('koc_product', $p->id);
        }
        return back()->with('ok', 'Ajouté au chaudron');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        $buyer = Auth::id();
        foreach ($cart as $pid => $row) {
            $p = Product::query()->find($pid);
            if (! $p) {
                continue;
            }
            $cents = (int) round((float) $p->priceAmount() * 100);
            $splits = DB::table('product_splits')->where('product_id', $pid)->get();
            $used = 0;
            foreach ($splits as $s) {
                $share = (int) floor($cents * $s->percent / 100);
                $used += $share;
                DB::table('ledger')->insert([
                    'product_id' => $pid, 'user_id' => $s->user_id,
                    'amount_cents' => $share, 'kind' => 'split', 'note' => $s->percent.'%',
                ]);
            }
            DB::table('ledger')->insert([
                'product_id' => $pid, 'user_id' => $buyer,
                'amount_cents' => max(0, $cents - $used), 'kind' => 'sale', 'note' => 'reste auteur',
            ]);
            if ($buyer) {
                DB::table('inventory')->insert([
                    'user_id' => $buyer, 'kind' => 'relic', 'node_id' => $p->node_id,
                    'label' => $p->title, 'meta' => $p->id,
                ]);
            }
            $koc = (int) $request->session()->get('koc');
            if ($koc && $request->session()->get('koc_product') === $pid && $koc !== $buyer) {
                $cut = max(1, (int) floor($cents * 0.05));
                User::query()->where('id', $koc)->increment('nodecoins', (int) ceil($cut / 10));
                DB::table('ledger')->insert([
                    'product_id' => $pid, 'user_id' => $koc,
                    'amount_cents' => $cut, 'kind' => 'koc', 'note' => 'citation forum',
                ]);
            }
        }
        $request->session()->forget(['cart', 'koc', 'koc_product']);
        return back()->with('ok', 'Payé (ledger + split + sac à dos). Stripe Connect en prod.');
    }
}
