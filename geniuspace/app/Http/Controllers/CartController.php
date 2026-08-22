<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Support\Grantor;
use App\Support\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = $request->session()->get('cart', []);
        $node = null;
        $chrome = null;
        if ($cart) {
            $first = collect($cart)->first();
            $pid = $first['product_id'] ?? array_key_first($cart);
            $p = Product::query()->find($pid);
            if ($p) {
                $node = \App\Models\GpNode::query()->find($p->node_id);
            }
        }
        if (! $node) {
            $node = \App\Models\GpNode::query()->where('slug', 'lumen')->first();
        }
        if ($node) {
            $chrome = \App\Support\Chrome::bag($node);
        }

        return view('cart', compact('cart', 'node', 'chrome'));
    }

    public function add(Request $request): RedirectResponse
    {
        $id = $request->validate(['product_id' => 'required|string'])['product_id'];
        $p = Product::query()->findOrFail($id);
        $cap = Order::capture($request, $p->id);
        $line = $p->id;
        if ($cap['options'] || $cap['files']) {
            $line = $p->id.'|'.substr(md5(json_encode($cap['options'] + $cap['files'])), 0, 8);
        }
        $cart = $request->session()->get('cart', []);
        $cart[$line] = [
            'product_id' => $p->id,
            'title' => $p->title,
            'price' => Order::priceLabel($p, $cap['extra_cents']),
            'extra_cents' => $cap['extra_cents'],
            'options' => $cap['options'],
            'files' => $cap['files'],
        ];
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
        foreach ($cart as $row) {
            $pid = $row['product_id'] ?? null;
            if (! $pid) {
                continue;
            }
            $p = Product::query()->find($pid);
            if (! $p) {
                continue;
            }
            $extra = (int) ($row['extra_cents'] ?? 0);
            $cents = (int) round((float) $p->priceAmount() * 100) + $extra;
            $note = 'reste auteur';
            if (! empty($row['options'])) {
                $note .= ' · '.collect($row['options'])->map(fn ($v, $k) => $k.'='.$v)->implode(', ');
            }
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
                'amount_cents' => max(0, $cents - $used), 'kind' => 'sale', 'note' => $note,
            ]);
            if ($buyer) {
                DB::table('inventory')->insert([
                    'user_id' => $buyer, 'kind' => 'relic', 'node_id' => $p->node_id,
                    'label' => $p->title,
                    'meta' => json_encode([
                        'product_id' => $p->id,
                        'options' => $row['options'] ?? [],
                        'files' => $row['files'] ?? [],
                    ], JSON_UNESCAPED_UNICODE),
                ]);
            }
            Grantor::grantProduct((string) $pid, (string) $p->node_id, $p->title);
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

        return back()->with('ok', 'Payé. Les fichiers mérités s’ouvrent. Stripe Connect en prod.');
    }
}
