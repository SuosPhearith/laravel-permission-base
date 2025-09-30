<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone_number')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            // :::::::::::::::::::::::::::::::::::::::::::::::: FIELD
            $table->string('avatar')->nullable();
            // :::::::::::::::::::::::::::::::::::::::::::::::: 2FA
            $table->boolean('enable_2fa')->default(false);
            $table->string("google2fa_secret")->nullable();
            $table->string("temp_2fa_secret")->nullable();
            $table->timestamp("two_factor_verified_at")->nullable();
            $table->string('two_factor_key')->nullable();
            // :::::::::::::::::::::::::::::::::::::::::::::::: 2FA
            $table->rememberToken();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamp("deleted_at")->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained("permissions")->onDelete('cascade');
            $table->foreignId('user_id')->constrained("users")->onDelete('cascade');
            $table->primary(['permission_id', 'user_id']);
        });

        Schema::create('user_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained("roles")->onDelete('cascade');
            $table->foreignId('user_id')->constrained("users")->onDelete('cascade');
            $table->primary(['role_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('user_permission');
    }
};
