<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        DB::statement('
            ALTER TABLE workshops
            ADD COLUMN IF NOT EXISTS location geography(Point, 4326)
        ');

        DB::statement('
            UPDATE workshops
            SET location = ST_SetSRID(ST_MakePoint(longitude::float8, latitude::float8), 4326)::geography
            WHERE latitude IS NOT NULL
              AND longitude IS NOT NULL
              AND location IS NULL
        ');

        DB::statement('
            CREATE INDEX IF NOT EXISTS workshops_location_gix
            ON workshops
            USING GIST (location)
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS workshops_location_gix');
        DB::statement('ALTER TABLE workshops DROP COLUMN IF EXISTS location');
    }
};
