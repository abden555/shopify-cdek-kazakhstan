<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE failed_api_logs MODIFY request_headers LONGTEXT NULL');
        DB::statement('ALTER TABLE failed_api_logs MODIFY request_payload LONGTEXT NULL');
    }

    public function down(): void
    {
        // Existing encrypted ciphertext is not guaranteed to be valid JSON, so a safe reversal is not possible.
    }
};
