<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table): void {
                if (! Schema::hasColumn('tenants', 'pre_order_enabled')) {
                    $table->boolean('pre_order_enabled')->default(false)->after('status');
                }
                if (! Schema::hasColumn('tenants', 'pre_order_slot_capacity')) {
                    $table->unsignedSmallInteger('pre_order_slot_capacity')->default(5)->after('pre_order_enabled');
                }
            });
        }

        if (Schema::hasTable('tenant_operating_hours')) {
            Schema::table('tenant_operating_hours', function (Blueprint $table): void {
                if (! Schema::hasColumn('tenant_operating_hours', 'open_time')) {
                    $table->time('open_time')->nullable()->after('day_of_week');
                }
                if (! Schema::hasColumn('tenant_operating_hours', 'close_time')) {
                    $table->time('close_time')->nullable()->after('open_time');
                }
                if (! Schema::hasColumn('tenant_operating_hours', 'is_closed')) {
                    $table->boolean('is_closed')->default(false)->after('close_time');
                }
            });

            foreach (['opens_at', 'closes_at'] as $colName) {
                if (Schema::hasColumn('tenant_operating_hours', $colName)) {
                    DB::statement("ALTER TABLE `tenant_operating_hours` MODIFY `{$colName}` TIME NULL");
                }
            }
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (Schema::hasColumn('orders', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                }
                if (! Schema::hasColumn('orders', 'customer_session_id')) {
                    $table->foreignId('customer_session_id')->nullable()->after('order_number');
                }
                if (! Schema::hasColumn('orders', 'checkout_key')) {
                    $table->string('checkout_key', 64)->nullable()->unique()->after('customer_session_id');
                }
                if (! Schema::hasColumn('orders', 'tracking_token_hash')) {
                    $table->string('tracking_token_hash', 64)->nullable()->index()->after('checkout_key');
                }
                if (! Schema::hasColumn('orders', 'service_mode')) {
                    $table->string('service_mode', 20)->default('delivery')->after('status');
                }
                if (! Schema::hasColumn('orders', 'customer_snapshot')) {
                    $table->json('customer_snapshot')->nullable()->after('grand_total_amount');
                }
                if (! Schema::hasColumn('orders', 'placed_at')) {
                    $table->timestamp('placed_at')->nullable()->after('customer_snapshot');
                }
            });
        }

        if (Schema::hasTable('tenant_orders')) {
            Schema::table('tenant_orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('tenant_orders', 'scheduled_at')) {
                    $table->timestamp('scheduled_at')->nullable()->after('status');
                }
                if (! Schema::hasColumn('tenant_orders', 'release_at')) {
                    $table->timestamp('release_at')->nullable()->after('scheduled_at');
                }
            });

            $protected = ['id', 'public_id', 'order_number', 'tenant_id', 'status', 'created_at', 'updated_at'];
            $columns = DB::select('SHOW COLUMNS FROM `tenant_orders`');
            foreach ($columns as $col) {
                if ($col->Null === 'NO' && $col->Default === null && ! in_array($col->Field, $protected, true)) {
                    DB::statement("ALTER TABLE `tenant_orders` MODIFY `{$col->Field}` {$col->Type} NULL");
                }
            }
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('order_items', 'modifier_total')) {
                    $table->unsignedInteger('modifier_total')->default(0)->after('quantity');
                }
                if (! Schema::hasColumn('order_items', 'note')) {
                    $table->string('note', 255)->nullable()->after('line_total');
                }
            });
        }
    }

    public function down(): void
    {
        //
    }
};
