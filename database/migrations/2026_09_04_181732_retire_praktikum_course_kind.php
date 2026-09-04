<?php

use App\Enums\CourseKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * „Praktikum" ist keine Kursart mehr. Was so eingetragen war, wird zu
 * „Sonstiges" — sonst stürzte der Enum-Cast beim ersten Laden ab.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('courses')
            ->where('kind', 'praktikum')
            ->update(['kind' => CourseKind::Sonstiges->value]);
    }

    public function down(): void
    {
        // Nicht umkehrbar: Welche „Sonstiges" vorher „Praktikum" waren, weiß niemand mehr.
    }
};
