<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlaced;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $currency;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.currency.source' => 'ZAR']);
        
        $this->user = User::factory()->create();
        $this->currency = Currency::create([
            'currency' => 'USD',
            'exchange_rate' => 0.0808279,
            'surcharge_percentage' => 7.5,
            'special_discount_percentage' => 0.0,
            'send_order_email' => false,
            'is_active' => true,
        ]);
    }

    public function test_can_list_all_orders()
    {
        Order::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'foreign_currency_id' => $this->currency->id,
        ]);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
                ->assertJsonCount(3);
    }

    public function test_can_show_specific_order()
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'foreign_currency_id' => $this->currency->id,
            'originating_currency' => 'ZAR',
            'exchange_rate' => 0.0808279,
            'foreign_amount' => 100.00,
            'originating_amount' => 1237.50,
            'surcharge_percentage' => 7.5,
            'surcharge_amount' => 92.81,
            'total_amount' => 1330.31,
            'special_discount_percentage' => 0.0,
            'special_discount_amount' => 0.0,
        ]);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $order->id,
                    'user_id' => $this->user->id,
                    'foreign_currency_id' => $this->currency->id,
                    'originating_currency' => 'ZAR',
                    'foreign_amount' => 100.00,
                    'originating_amount' => 1237.50,
                ]);
    }

    public function test_can_show_orders_for_specific_user()
    {
        $anotherUser = User::factory()->create();
        
        Order::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'foreign_currency_id' => $this->currency->id,
        ]);

        Order::factory()->create([
            'user_id' => $anotherUser->id,
            'foreign_currency_id' => $this->currency->id,
        ]);

        $response = $this->getJson("/api/orders/user/{$this->user->id}");

        $response->assertStatus(200)
                ->assertJsonCount(2);

        $orders = $response->json();
        foreach ($orders as $order) {
            $this->assertEquals($this->user->id, $order['user_id']);
        }
    }

    public function test_can_show_orders_for_specific_currency()
    {
        $anotherCurrency = Currency::create([
            'currency' => 'EUR',
            'exchange_rate' => 0.0718710,
            'surcharge_percentage' => 5.0,
            'special_discount_percentage' => 2.0,
            'send_order_email' => false,
            'is_active' => true,
        ]);
        
        Order::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'foreign_currency_id' => $this->currency->id,
        ]);

        Order::factory()->create([
            'user_id' => $this->user->id,
            'foreign_currency_id' => $anotherCurrency->id,
        ]);

        $response = $this->getJson("/api/orders/currency/{$this->currency->id}");

        $response->assertStatus(200)
                ->assertJsonCount(2);

        $orders = $response->json();
        foreach ($orders as $order) {
            $this->assertEquals($this->currency->id, $order['foreign_currency_id']);
        }
    }

    public function test_can_create_order_with_valid_data()
    {
        Mail::fake();

        $orderData = [
            'user_id' => $this->user->id,
            'foreign_currency_id' => $this->currency->id,
            'foreign_amount' => 100.00,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Order created successfully',
                ])
                ->assertJsonStructure([
                    'message',
                    'order' => [
                        'id',
                        'user_id',
                        'foreign_currency_id',
                        'originating_currency',
                        'exchange_rate',
                        'foreign_amount',
                        'originating_amount',
                        'surcharge_amount',
                        'total_amount',
                    ]
                ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'foreign_currency_id' => $this->currency->id,
            'originating_currency' => 'ZAR',
            'foreign_amount' => 100.00,
        ]);

        Mail::assertNotSent(OrderPlaced::class);
    }

    public function test_order_creation_fails_when_no_amount_provided()
    {
        $orderData = [
            'user_id' => $this->user->id,
            'foreign_currency_id' => $this->currency->id,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
                ->assertJson(['message' => 'Either foreign_amount or originating_amount must be provided']);
    }

    public function test_order_creation_fails_when_both_amounts_provided()
    {
        $orderData = [
            'user_id' => $this->user->id,
            'foreign_currency_id' => $this->currency->id,
            'foreign_amount' => 100.00,
            'originating_amount' => 1237.50,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
                ->assertJson(['message' => 'Provide either foreign_amount or originating_amount, not both']);
    }

    public function test_can_create_order_with_originating_amount_only()
    {
        $orderData = [
            'user_id' => $this->user->id,
            'foreign_currency_id' => $this->currency->id,
            'originating_amount' => 1237.50,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(201);
    }

    public function test_creates_order_with_email_notification_when_enabled()
    {
        Mail::fake();

        $this->currency->update(['send_order_email' => true]);

        config(['services.order_notifications.to' => 'admin@example.com']);

        $orderData = [
            'user_id' => $this->user->id,
            'foreign_currency_id' => $this->currency->id,
            'foreign_amount' => 100.00,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(201);

        Mail::assertSent(OrderPlaced::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }

    public function test_order_creation_fails_with_invalid_user()
    {
        $orderData = [
            'user_id' => 999,
            'foreign_currency_id' => $this->currency->id,
            'foreign_amount' => 100.00,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id']);
    }

    public function test_order_creation_fails_with_invalid_currency()
    {
        $orderData = [
            'user_id' => $this->user->id,
            'foreign_currency_id' => 999,
            'foreign_amount' => 100.00,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['foreign_currency_id']);
    }

    public function test_order_creation_fails_with_negative_amounts()
    {
        $orderData = [
            'user_id' => $this->user->id,
            'foreign_currency_id' => $this->currency->id,
            'foreign_amount' => -100.00,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['foreign_amount']);
    }

    public function test_order_calculations_are_performed_correctly()
    {
        $orderData = [
            'user_id' => $this->user->id,
            'foreign_currency_id' => $this->currency->id,
            'foreign_amount' => 100.00,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(201);

        $order = Order::latest()->first();
        
        $this->assertEquals(100.00, $order->foreign_amount);
        $this->assertEquals(0.0808279, $order->exchange_rate);
        $this->assertEquals(7.5, $order->surcharge_percentage);
        $this->assertEquals(1237.20, $order->originating_amount);
    }

    public function test_order_with_special_discount_calculations()
    {
        $specialCurrency = Currency::create([
            'currency' => 'EUR',
            'exchange_rate' => 0.0718710,
            'surcharge_percentage' => 5.0,
            'special_discount_percentage' => 2.0,
            'send_order_email' => false,
            'is_active' => true,
        ]);

        $orderData = [
            'user_id' => $this->user->id,
            'foreign_currency_id' => $specialCurrency->id,
            'foreign_amount' => 100.00,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(201);

        $order = Order::latest()->first();
        
        $this->assertEquals(2.0, $order->special_discount_percentage);
        $this->assertEquals(27.83, $order->special_discount_amount);
    }
}
