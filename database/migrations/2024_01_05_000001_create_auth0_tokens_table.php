<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down(): void
    {
        Schema::dropIfExists(Config::string('filament-auth0.tokens.stores.database.table'));
    }

    public function up(): void
    {
        $tableName = Config::string('filament-auth0.tokens.stores.database.table');
        $userIdColumn = Config::string('filament-auth0.tokens.stores.database.user_id_column');

        Schema::create($tableName, static function (Blueprint $table) use ($userIdColumn) {
            $table->primary($userIdColumn);
            $table->foreignId($userIdColumn)->constrained()->cascadeOnDelete();
            $table->string('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('token_type', 50);
            $table->string('scope');
            $table->timestamp('expires_at');
            $table->timestamp('needs_refresh_at');
            $table->timestamps();

            $table->index([$userIdColumn, 'expires_at']);
            $table->index('expires_at');
            $table->index('needs_refresh_at');
        });
    }
};
