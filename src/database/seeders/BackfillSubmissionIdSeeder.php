<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillSubmissionIdSeeder extends Seeder
{
    public function run(): void
    {
        $rows = DB::table('correction_requests')
            ->whereNull('submission_id')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            DB::table('correction_requests')
                ->where('id', $row->id)
                ->update(['submission_id' => (string) Str::uuid()]);
        }
    }
}
