<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubmissionIdToCorrectionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('correction_requests', function (Blueprint $table) {
            $table->uuid('submission_id')->after('id')->index();

        });
    }

    public function down()
    {
        Schema::table('correction_requests', function (Blueprint $table) {
            $table->dropColumn('submission_id');
        });
    }
}
