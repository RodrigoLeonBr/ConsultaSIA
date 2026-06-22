<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expand description from varchar(63) to varchar(255) — TU/SIH descriptions reach 249 chars
        DB::statement("ALTER TABLE procedimento MODIFY COLUMN `procedimento` varchar(255) NOT NULL DEFAULT ''");

        Schema::table('procedimento', function (Blueprint $table) {
            $table->decimal('VL_SP', 12, 2)->default(0.00)->after('PA_TOTAL');
            $table->decimal('VL_SH', 12, 2)->default(0.00)->after('VL_SP');
        });
    }

    public function down(): void
    {
        Schema::table('procedimento', function (Blueprint $table) {
            $table->dropColumn(['VL_SP', 'VL_SH']);
        });

        DB::statement("ALTER TABLE procedimento MODIFY COLUMN `procedimento` varchar(63) NOT NULL DEFAULT ''");
    }
};
