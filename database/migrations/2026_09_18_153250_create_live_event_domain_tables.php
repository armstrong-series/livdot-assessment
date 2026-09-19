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
        Schema::create('live_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('host_id')->index();
            $table->string('title');
            $table->unsignedInteger('ticket_price_kobo');
            $table->unsignedInteger('scheduled_duration_minutes');
            $table->timestamp('scheduled_starts_at');
            $table->string('status')->default('draft')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->foreign('host_id')->references('id')->on('users')->restrictOnDelete();
        });
        Schema::create('crew_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('live_event_id')->index();
            $table->uuid('crew_member_id')->index();
            $table->string('role');
            $table->string('status')->default('assigned')->index();
            $table->timestamps();
            $table->unique(['live_event_id', 'crew_member_id', 'role']);
            $table->foreign('live_event_id')->references('id')->on('live_events')->cascadeOnDelete();
            $table->foreign('crew_member_id')->references('id')->on('users')->restrictOnDelete();
        });
        Schema::create('tickets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('live_event_id')->index();
            $table->uuid('viewer_id')->index();
            $table->unsignedInteger('amount_kobo');
            $table->string('status')->default('pending')->index();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['live_event_id', 'viewer_id']);
            $table->foreign('live_event_id')->references('id')->on('live_events')->cascadeOnDelete();
            $table->foreign('viewer_id')->references('id')->on('users')->restrictOnDelete();
        });
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id')->unique();
            $table->string('provider');
            $table->string('provider_reference')->unique();
            $table->string('provider_event_id')->nullable()->unique();
            $table->unsignedInteger('amount_kobo');
            $table->string('status')->default('pending')->index();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
        });
        Schema::create('stream_incidents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('live_event_id')->index();
            $table->timestamp('failed_at');
            $table->boolean('automatic_refunds_eligible');
            $table->string('status')->default('reported');
            $table->timestamps();
            $table->foreign('live_event_id')->references('id')->on('live_events')->cascadeOnDelete();
        });
        Schema::create('refunds', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id')->unique();
            $table->uuid('stream_incident_id')->nullable()->index();
            $table->unsignedInteger('amount_kobo');
            $table->string('status')->default('pending')->index();
            $table->string('reason');
            $table->timestamps();
            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('stream_incident_id')->references('id')->on('stream_incidents')->nullOnDelete();
        });
        Schema::create('payouts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('live_event_id')->unique();
            $table->unsignedInteger('amount_kobo');
            $table->string('status')->default('pending')->index();
            $table->timestamps();
            $table->foreign('live_event_id')->references('id')->on('live_events')->restrictOnDelete();
        });
        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('live_event_id')->index();
            $table->string('entry_type');
            $table->integer('amount_kobo');
            $table->string('reference_type');
            $table->uuid('reference_id');
            $table->timestamps();
            $table->unique(['reference_type', 'reference_id', 'entry_type']);
            $table->foreign('live_event_id')->references('id')->on('live_events')->restrictOnDelete();
        });
        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('operation');
            $table->uuid('resource_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('stream_incidents');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('crew_assignments');
        Schema::dropIfExists('live_events');
    }
};
