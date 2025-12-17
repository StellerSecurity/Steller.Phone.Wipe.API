<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_wipe_users_v2', function (Blueprint $table) {
            // Use UUID/CHAR(36) to match your existing style
            $table->char('id', 36)->primary();

            // If you truly need a username login for this table
            $table->string('username', 255)->unique();

            // Never store plaintext passwords. Store a hash.
            $table->string('password_hash', 255);

            // NEVER store raw auth tokens. Store a hash (sha256 or similar).
            $table->string('auth_token_hash', 64)->nullable()->index();

            // Status + wiped_by like your enums
            $table->unsignedTinyInteger('status')->default(4)->index();     // UNKNOWN = 4
            $table->unsignedTinyInteger('wiped_by')->default(0)->index();   // UNKNOWN = 0

            // Metadata
            $table->string('key_helper', 255)->nullable();
            $table->timestamp('last_call')->nullable();

            // Business link
            $table->string('subscription_id', 255)->nullable()->index();

            $table->timestamps();

            // Useful compound index for dashboards / queries
            $table->index(['subscription_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_wipe_users_v2');
    }
};
