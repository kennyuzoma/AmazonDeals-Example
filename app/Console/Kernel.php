<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // pull in amazon data
//        $schedule->exec('./node_modules/.bin/cypress run --browser chrome')->dailyAt('23:11');//->dailyAt('8:00');

        // import amazon data
        $schedule->command('amazon:import')->everyThirtyMinutes();//->dailyAt('9:00');

        // send out tweets
        $schedule->command('tweet:send imported')->everyTwoMinutes();
        $schedule->command('tweet:send scheduled')->everyTwoMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
