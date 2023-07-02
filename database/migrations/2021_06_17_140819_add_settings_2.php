<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSettings2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        nova_set_setting_value('import_body_style', 'dynamic');
        nova_set_setting_value('intro_text_enabled', 0);
        nova_set_setting_value('intro_text_variations', 'Super Deal!!,Today Only!, STEAL!!, GREAT DEAL!');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
