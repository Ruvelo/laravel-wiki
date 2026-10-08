<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('wiki.table_prefix', 'wiki_');

        Schema::create($prefix.'pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->timestamps();
        });

        Schema::create($prefix.'revisions', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('page_id')->constrained($prefix.'pages')->cascadeOnDelete();
            $table->string('title');
            $table->longText('body');
            $table->string('summary')->nullable();
            // A string so UUID and ULID user keys work as well as integers.
            $table->string('user_id', 64)->nullable()->index();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create($prefix.'links', function (Blueprint $table) use ($prefix) {
            $table->foreignId('page_id')->constrained($prefix.'pages')->cascadeOnDelete();
            $table->string('target_slug')->index();
            $table->primary(['page_id', 'target_slug']);
        });
    }

    public function down(): void
    {
        $prefix = config('wiki.table_prefix', 'wiki_');

        Schema::dropIfExists($prefix.'links');
        Schema::dropIfExists($prefix.'revisions');
        Schema::dropIfExists($prefix.'pages');
    }
};
