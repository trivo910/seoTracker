<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Default API settings
        DB::table('settings')->insert([
            ['key' => 'serp_provider', 'value' => 'serper',  'created_at' => now(), 'updated_at' => now()],
            ['key' => 'serp_api_key',  'value' => '',        'created_at' => now(), 'updated_at' => now()],
            ['key' => 'serp_country',  'value' => 'in',      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'serp_language', 'value' => 'en',      'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
