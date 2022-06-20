<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use App\Models\Movition;

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
        $schedule->call(function () {
            date_default_timezone_set('America/Sao_Paulo');
            
            $start = date(strtotime('-1 months', strtotime(date('Y-m-t'))));
            $end = date(strtotime('-1 months', strtotime(date('Y-m-01'))));
            $atual = date('Y-m-d');
            
            $sqlEntrada = 'SELECT SUM(valor) FROM `movitions` WHERE `tipo` = "entrada" AND `data` BETWEEN '.$start.' AND '.$end.' ';
            $entrada = DB::select($sqlEntrada);
            
            $sqlSaida = 'SELECT SUM(valor) FROM `movitions` WHERE `tipo` = "saida" AND `data` BETWEEN '.$start.' AND '.$end.' ';
            $saida = DB::select($sqlSaida);
            
            Movition::create([
                'data' => $atual,
                'valor' => $entrada - $saida,
                'descricao' => 'Virada de mês',
                'tipo' => 'entrada',
                'status' => 'geral'
            ]);
        })->monthly();
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
