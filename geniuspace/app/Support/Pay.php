<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Paiement au prix tenu. La ligne Ghost (extra_cents / held_cents) est
 * la source, pas le tarif affiché. Stripe Checkout si STRIPE_SECRET,
 * sinon règlement démo (ledger) au même montant.
 */
class Pay
{
    /** @param  array<string, mixed>  $row */
    public static function cents(array $row, ?Product $p = null): int
    {
        if (isset($row['held_cents']) && is_numeric($row['held_cents'])) {
            $held = (int) $row['held_cents'];
        } else {
            $p = $p ?: Product::query()->find($row['product_id'] ?? null);
            $list = $p ? (int) round((float) $p->priceAmount() * 100) : 0;
            $extra = (int) ($row['extra_cents'] ?? 0);
            $held = $list + $extra;
        }
        $p = $p ?: Product::query()->find($row['product_id'] ?? null);

        return Invariants::heldCents($held, self::floorCents($p));
    }

    public static function floorCents(?Product $p): int
    {
        if (! $p) {
            return 0;
        }
        $fields = Engine::fields((string) $p->id, null);
        foreach (['prix', 'prix_plancher'] as $k) {
            if (isset($fields[$k]) && $fields[$k]->min_val !== null && $fields[$k]->min_val !== '') {
                return (int) round((float) $fields[$k]->min_val * 100);
            }
        }

        return 0;
    }

    public static function secret(): string
    {
        return (string) (config('services.stripe.secret') ?: env('STRIPE_SECRET', ''));
    }

    /**
     * @param  array<string, array<string, mixed>>  $cart
     * @return array{id: string, url: string}|null
     */
    public static function stripeSession(array $cart, string $success, string $cancel): ?array
    {
        $secret = self::secret();
        if ($secret === '') {
            return null;
        }
        $payload = [
            'mode' => 'payment',
            'success_url' => $success.(str_contains($success, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancel,
        ];
        $i = 0;
        foreach ($cart as $row) {
            $p = Product::query()->find($row['product_id'] ?? null);
            $cents = self::cents($row, $p);
            if ($cents < 50) {
                continue;
            }
            $payload['line_items'][$i] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $cents,
                    'product_data' => [
                        'name' => (string) ($row['title'] ?? $p?->title ?? 'Œuvre'),
                    ],
                ],
            ];
            $i++;
        }
        if ($i === 0) {
            return null;
        }
        try {
            $res = Http::withToken($secret)->asForm()->timeout(20)
                ->post('https://api.stripe.com/v1/checkout/sessions', $payload);
            if (! $res->successful()) {
                Log::warning('Stripe session: '.$res->body());

                return null;
            }
            $json = $res->json();
            if (empty($json['url']) || empty($json['id'])) {
                return null;
            }

            return ['id' => (string) $json['id'], 'url' => (string) $json['url']];
        } catch (\Throwable $e) {
            Log::debug('Stripe skip: '.$e->getMessage());

            return null;
        }
    }

    public static function stripePaid(string $sessionId): bool
    {
        $secret = self::secret();
        if ($secret === '' || $sessionId === '') {
            return false;
        }
        try {
            $res = Http::withToken($secret)->timeout(15)
                ->get('https://api.stripe.com/v1/checkout/sessions/'.$sessionId);
            $json = $res->json();

            return $res->successful() && (($json['payment_status'] ?? '') === 'paid' || ($json['status'] ?? '') === 'complete');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
