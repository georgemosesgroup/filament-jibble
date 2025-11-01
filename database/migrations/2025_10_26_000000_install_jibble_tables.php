<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenantColumn = (string) config('filament-jibble.tenant_foreign_key', 'tenant_id');
        $tenantColumnType = (string) config('filament-jibble.tenant_foreign_key_type', 'uuid');

        $addTenantColumn = function (Blueprint $table) use ($tenantColumn, $tenantColumnType): void {
            $column = match ($tenantColumnType) {
                'ulid' => $table->ulid($tenantColumn),
                'string' => $table->string($tenantColumn, 191),
                'integer' => $table->integer($tenantColumn),
                'bigInteger' => $table->bigInteger($tenantColumn),
                'unsignedBigInteger' => $table->unsignedBigInteger($tenantColumn),
                default => $table->uuid($tenantColumn),
            };

            $column->nullable();
            $table->index($tenantColumn);
        };

        Schema::create('jibble_connections', function (Blueprint $table) use ($tenantColumn, $addTenantColumn): void {
            $table->uuid('id')->primary();
            $addTenantColumn($table);
            $table->text('user_id')->nullable();
            $table->string('name')->default('default');
            $table->string('organization_uuid')->nullable();
            $table->text('organization_name')->nullable();
            $table->text('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->text('api_token')->nullable();
            $table->text('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->unique([$tenantColumn, 'name']);
            $table->unique(['user_id', 'name']);
        });

        Schema::create('jibble_people', function (Blueprint $table) use ($tenantColumn, $addTenantColumn): void {
            $table->uuid('id')->primary();
            $addTenantColumn($table);
            $table->uuid('connection_id')->index();
            $table->string('jibble_id')->index();
            // Extended metadata synced from the Jibble API
            $table->string('organization_id')->nullable()->index();
            $table->json('overridden_properties')->nullable();
            $table->string('calendar_id')->nullable()->index();
            $table->string('schedule_id')->nullable()->index();
            $table->string('pay_period_definition_id')->nullable()->index();
            $table->string('group_id')->nullable()->index();
            $table->string('position_id')->nullable()->index();
            $table->string('employment_type_id')->nullable()->index();
            $table->string('user_id')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('phone_number')->nullable();
            $table->string('country_code', 16)->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('preferred_name')->nullable();
            $table->string('role')->nullable();
            $table->string('code')->nullable()->index();
            $table->string('pin_code')->nullable();
            $table->string('status')->nullable();
            $table->boolean('has_embeddings')->nullable();
            $table->text('nfc_token')->nullable();
            $table->date('work_start_date')->nullable();
            $table->timestampTz('join_date')->nullable();
            $table->timestampTz('latest_time_entry_time')->nullable();
            $table->timestampTz('invited_at')->nullable();
            $table->timestampTz('removed_at')->nullable();
            $table->timestampTz('jibble_created_at')->nullable();
            $table->timestampTz('jibble_updated_at')->nullable();
            $table->json('projects')->nullable();
            $table->json('work_types')->nullable();
            $table->json('managers')->nullable();
            $table->json('unit_time_off_policies')->nullable();
            $table->json('picture')->nullable();
            $table->json('managed_units')->nullable();
            $table->json('kiosks')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['connection_id', 'jibble_id']);
        });

        Schema::create('jibble_timesheet_summaries', function (Blueprint $table) use ($tenantColumn, $addTenantColumn): void {
            $table->uuid('id')->primary();
            $addTenantColumn($table);
            $table->uuid('connection_id')->index();
            $table->uuid('person_id')->nullable()->index();
            $table->string('jibble_person_id')->nullable()->index();
            $table->date('date');
            $table->string('period')->default('Day');
            $table->integer('tracked_seconds')->default(0);
            $table->integer('payroll_seconds')->default(0);
            $table->integer('regular_seconds')->default(0);
            $table->integer('overtime_seconds')->default(0);
            $table->json('daily_breakdown')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['connection_id', 'period', 'date', 'jibble_person_id'], 'timesheet_unique');
        });

        Schema::create('jibble_sync_logs', function (Blueprint $table) use ($tenantColumn, $addTenantColumn): void {
            $table->uuid('id')->primary();
            $addTenantColumn($table);
            $table->uuid('connection_id')->nullable()->index();
            $table->string('resource');
            $table->string('status')->default('queued');
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('jibble_time_entries', function (Blueprint $table) use ($tenantColumn, $addTenantColumn): void {
            $table->uuid('id')->primary();
            $addTenantColumn($table);
            $table->uuid('connection_id')->index();
            $table->uuid('person_id')->nullable()->index();
            $table->string('jibble_entry_id')->index();
            $table->string('jibble_person_id')->nullable()->index();
            $table->string('project_id')->nullable()->index();
            $table->string('activity_id')->nullable()->index();
            $table->string('location_id')->nullable()->index();
            $table->string('kiosk_id')->nullable()->index();
            $table->string('break_id')->nullable()->index();
            $table->string('client_type')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->string('note')->nullable();
            $table->string('offset')->nullable();
            $table->date('belongs_to_date')->nullable();
            $table->timestamp('time')->nullable();
            $table->timestamp('local_time')->nullable();
            $table->boolean('is_offline')->default(false);
            $table->boolean('is_face_recognized')->default(false);
            $table->boolean('is_automatic')->default(false);
            $table->boolean('is_manual')->default(false);
            $table->boolean('is_outside_geofence')->default(false);
            $table->boolean('is_manual_location')->default(false);
            $table->boolean('is_unusual')->default(false);
            $table->boolean('is_end_of_day')->default(false);
            $table->boolean('is_from_speed_kiosk')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->string('previous_entry_id')->nullable();
            $table->string('next_entry_id')->nullable();
            $table->json('coordinates')->nullable();
            $table->json('picture')->nullable();
            $table->string('picture_file_id')->nullable();
            $table->string('picture_file_name')->nullable();
            $table->unsignedBigInteger('picture_size')->nullable();
            $table->string('picture_hash')->nullable();
            $table->string('picture_public_url', 2048)->nullable();
            $table->json('platform')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['connection_id', 'jibble_entry_id']);
        });

        Schema::create('jibble_timesheets', function (Blueprint $table) use ($tenantColumn, $addTenantColumn): void {
            $table->uuid('id')->primary();
            $addTenantColumn($table);
            $table->uuid('connection_id')->index();
            $table->uuid('person_id')->nullable()->index();
            $table->string('jibble_timesheet_id')->index();
            $table->string('jibble_person_id')->nullable()->index();
            $table->date('date');
            $table->string('status')->nullable();
            $table->integer('tracked_seconds')->default(0);
            $table->integer('break_seconds')->default(0);
            $table->integer('billable_seconds')->default(0);
            $table->json('segments')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['connection_id', 'jibble_timesheet_id']);
        });

        Schema::create('jibble_locations', function (Blueprint $table) use ($tenantColumn, $addTenantColumn): void {
            $table->uuid('id')->primary();
            $addTenantColumn($table);
            $table->uuid('connection_id')->index();
            $table->string('jibble_location_id')->index();
            $table->string('name')->nullable();
            $table->string('code')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('status')->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('geofence_radius')->nullable();
            $table->string('geofence_units')->nullable();
            $table->json('geo_fence')->nullable();
            $table->json('coordinates')->nullable();
            $table->json('schedules')->nullable();
            $table->timestamp('jibble_created_at')->nullable();
            $table->timestamp('jibble_updated_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['connection_id', 'jibble_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jibble_locations');
        Schema::dropIfExists('jibble_time_entries');
        Schema::dropIfExists('jibble_timesheets');
        Schema::dropIfExists('jibble_timesheet_summaries');
        Schema::dropIfExists('jibble_people');
        Schema::dropIfExists('jibble_sync_logs');
        Schema::dropIfExists('jibble_connections');
    }
};
