<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    private function userId(Request $request): int
    {
        return (int) $request->attributes->get('user_id');
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'min:1'],
        ]);

        $orderId = (int) $data['order_id'];

        // 1) Fetch order from order-service as the user (so ownership is enforced)
        $ordersBase = rtrim((string) config('services.orders.base_url'), '/'); // http://web/api/orders
        $orderUrl = $ordersBase . '/items/' . $orderId;

        $authHeader = (string) $request->header('Authorization'); // reuse same user token
        $orderResp = Http::acceptJson()
            ->withHeaders(['Authorization' => $authHeader])
            ->get($orderUrl);

        if (!$orderResp->successful()) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order = $orderResp->json('data');
        if (!$order || !isset($order['id'], $order['total_price'], $order['status'])) {
            return response()->json(['message' => 'Invalid order response'], 500);
        }

        if ((string) $order['status'] === 'paid') {
            return response()->json(['message' => 'Order already paid'], 422);
        }

        $amount = (int) $order['total_price']; // cents
        if ($amount <= 0) {
            return response()->json(['message' => 'Invalid order amount'], 422);
        }

        // 2) Create Stripe Checkout Session
        $stripe = new StripeClient((string) config('services.stripe.secret'));

        $frontend = rtrim((string) config('services.frontend.base_url'), '/'); // http://app.localhost
        $currency = (string) config('services.stripe.currency');

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => $frontend . '/checkout/success?order_id=' . $orderId . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $frontend . '/checkout/cancel?order_id=' . $orderId,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $amount,
                    'product_data' => [
                        'name' => 'Order #' . $orderId,
                    ],
                ],
            ]],
            'metadata' => [
                'order_id' => (string) $orderId,
                'user_id' => (string) $this->userId($request),
            ],
        ]);

        return response()->json([
            'url' => $session->url,
            'session_id' => $session->id,
        ]);
    }
}