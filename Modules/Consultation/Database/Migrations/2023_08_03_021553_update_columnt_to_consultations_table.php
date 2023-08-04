<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign('consultations_customer_id_foreign');
            $table->dropColumn('customer_id');
            $table->dropForeign('consultations_queue_id_foreign');
            $table->dropColumn('queue_id');
            $table->dropForeign('consultations_employee_schedule_id_foreign');
            $table->dropColumn('employee_schedule_id');
            $table->dropColumn('consultation_date');

            if (Schema::hasColumn('consultations', 'order_consultation_id')) {
                $table->dropForeign('consultations_order_consultation_id_foreign');
                $table->dropColumn('order_consultation_id');
            }
            
            $table->after('id', function (Blueprint $table) {
                $table->foreignId('order_consultation_id')->constrained('order_consultations');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consultations', function (Blueprint $table) {
            // $table->after('id', function (Blueprint $table) {
            //     $table->foreignId('customer_id')->constrained('customers');
            //     $table->foreignId('queue_id')->constrained('queues');
            //     $table->foreignId('employee_schedule_id')->constrained('employee_schedules');
            //     $table->date('consultation_date');
            // });
            $table->dropForeign('consultations_order_consultation_id_foreign');
            $table->dropColumn('order_consultation_id');
        });
    }
};
