<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature', '');
        $secret = (string) config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $orderId = $session->metadata->order_id ?? null;

            if ($orderId) {
                $ordersBase = rtrim((string) config('services.orders.base_url'), '/'); // http://web/api/orders
                $serviceKey = (string) config('services.orders.service_key');

                $markPaidUrl = $ordersBase . '/internal/items/' . $orderId . '/mark-paid';

                Http::acceptJson()
                    ->withHeaders(['X-Service-Key' => $serviceKey])
                    ->post($markPaidUrl);
            }
        }

        return response()->json(['received' => true]);
    }
}