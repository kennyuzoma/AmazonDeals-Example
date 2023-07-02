<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreSiteSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        nova_set_setting_value('twitter_hashtags', '#discounts #deal #deals');
        nova_set_setting_value('new_line_after_title', 0);
        nova_set_setting_value('price_prefix', 'for');
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
