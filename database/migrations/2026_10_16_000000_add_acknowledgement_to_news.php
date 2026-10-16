<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            // false = read-only news shown to everyone; true = only the chosen
            // recipients see it, and each must confirm they understood it.
            $table->boolean('requires_acknowledgement')->default(false)->after('template');
        });

        Schema::create('news_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Set once, when the recipient confirms; null = still pending.
            $table->timestamp('acknowledged_at')->nullable();
            // Set when the recipient was notified, so editing never re-notifies.
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['news_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_acknowledgements');

        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('requires_acknowledgement');
        });
    }
};
