<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('notification_queue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->morphs('notifiable');                  // notifiable_type + notifiable_id
            $table->string('channel')->default('mail');    // mail, sms, etc.
            $table->json('data');                          // Notification payload
            $table->enum('status',['pending','processing','sent','failed'])
                ->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_queue');
    }
};
