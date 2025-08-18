<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('account');
            $table->unsignedBigInteger('profile_id');
            $table->string('profile');
            $table->unsignedBigInteger('lead_id')->unique();
            $table->string('lead_type');
            $table->string('lead_status');
            $table->timestamp('date_created');
            $table->string('quotable')->nullable();
            $table->decimal('quote_value', 10, 2)->nullable();
            $table->decimal('sales_value', 10, 2)->nullable();
            $table->string('lead_source')->nullable();
            $table->string('lead_medium')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
