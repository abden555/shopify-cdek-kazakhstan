<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->timestamp('installed_at')->nullable()->after('is_active');
            $table->timestamp('uninstalled_at')->nullable()->after('installed_at');
        });

        Schema::table('shop_sessions', function (Blueprint $table): void {
            $table->string('token_type', 20)->default('offline')->after('session_key');
            $table->string('shopify_user_id')->nullable()->after('user_id');
            $table->text('refresh_token')->nullable()->after('access_token');
            $table->timestamp('refresh_token_expires_at')->nullable()->after('expires_at');
            $table->json('associated_user')->nullable()->after('scopes');
            $table->index(['shop_id', 'token_type']);
            $table->index(['shop_id', 'shopify_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('shop_sessions', function (Blueprint $table): void {
            $table->dropIndex(['shop_id', 'token_type']);
            $table->dropIndex(['shop_id', 'shopify_user_id']);
            $table->dropColumn(['token_type', 'shopify_user_id', 'refresh_token', 'refresh_token_expires_at', 'associated_user']);
        });

        Schema::table('shops', function (Blueprint $table): void {
            $table->dropColumn(['installed_at', 'uninstalled_at']);
        });
    }
};
