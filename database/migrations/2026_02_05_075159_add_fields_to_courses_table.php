<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('name')->after('id');

            $table->string('language_id')->nullable()->after('name');
            $table->string('lesson_type')->nullable()->after('language_id');

            $table->decimal('course_price', 10, 2)->default(0)->after('lesson_type');
            $table->decimal('enrollment_fee', 10, 2)->default(0)->after('course_price');
            $table->decimal('hours_purchased', 10, 2)->default(0)->after('enrollment_fee');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'language_id',
                'lesson_type',
                'course_price',
                'enrollment_fee',
                'hours_purchased',
            ]);
        });
    }
};
