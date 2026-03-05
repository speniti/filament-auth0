<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropUnique(['auth0_sub']);
            $table->dropColumn('auth0_sub');
        });
    }

    /** Run the migrations. */
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->string('auth0_sub')->nullable()->unique()->after('id');
        });
    }
};
