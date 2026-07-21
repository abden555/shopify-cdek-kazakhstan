<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('platform')->default('shopify')->index();
            $table->string('external_id')->nullable();
            $table->string('currency', 3)->default('KZT');
            $table->string('timezone')->default('Asia/Almaty');
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['platform', 'external_id']);
        });

        Schema::create('shop_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_key')->unique();
            $table->text('access_token')->nullable();
            $table->json('scopes')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['shop_id', 'user_id']);
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->restrictOnDelete();
            $table->string('external_id');
            $table->string('order_number')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('currency', 3)->default('KZT');
            $table->string('financial_status')->nullable()->index();
            $table->string('fulfillment_status')->nullable()->index();
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('shipping_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('ordered_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['shop_id', 'external_id']);
            $table->index(['shop_id', 'order_number']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->string('sku')->nullable()->index();
            $table->string('title');
            $table->string('variant_title')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('fulfilled_quantity')->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->unsignedInteger('weight_grams')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['order_id', 'external_id']);
        });

        Schema::create('shipments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id')->nullable();
            $table->string('provider')->default('cdek')->index();
            $table->string('tracking_number')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->string('service_code')->nullable();
            $table->decimal('shipping_cost', 15, 2)->nullable();
            $table->string('currency', 3)->default('KZT');
            $table->json('recipient')->nullable();
            $table->json('origin_address')->nullable();
            $table->json('destination_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('shipped_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['provider', 'external_id']);
            $table->index(['shop_id', 'status']);
            $table->index(['order_id', 'status']);
        });

        Schema::create('shipment_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('sku')->nullable()->index();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('weight_grams')->nullable();
            $table->decimal('declared_value', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['shipment_id', 'order_item_id']);
        });

        Schema::create('tracking', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->string('status')->index();
            $table->string('description')->nullable();
            $table->string('location')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['shipment_id', 'external_id']);
            $table->index(['shipment_id', 'occurred_at']);
        });

        Schema::create('labels', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('format', 10)->default('pdf');
            $table->string('disk')->default('private');
            $table->string('path')->unique();
            $table->string('checksum', 64)->nullable()->index();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('generated_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('setting_key');
            $table->json('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['shop_id', 'setting_key']);
            $table->index('setting_key');
        });

        Schema::create('webhook_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('shopify')->index();
            $table->string('event_id')->nullable();
            $table->string('topic')->index();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default('received')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['provider', 'event_id']);
            $table->index(['shop_id', 'topic', 'created_at']);
        });

        Schema::create('failed_api_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service')->index();
            $table->string('operation')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->text('request_url')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_payload')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->text('error_message');
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->timestamp('last_retried_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['service', 'created_at']);
            $table->index(['shop_id', 'service']);
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('causer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event')->index();
            $table->string('description');
            $table->string('subject_type')->nullable();
            $table->uuid('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['shop_id', 'event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('failed_api_logs');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('labels');
        Schema::dropIfExists('tracking');
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('shop_sessions');
        Schema::dropIfExists('shops');
    }
};
