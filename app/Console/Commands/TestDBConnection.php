<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestDBConnection extends Command
{
    protected $signature = 'db:test';
    protected $description = 'Test database connection';

    public function handle()
    {
        $this->info('Testing database connection...');
        
        try {
            $this->info('Database Config:');
            $this->info('DB_HOST: ' . config('database.connections.mysql.host'));
            $this->info('DB_PORT: ' . config('database.connections.mysql.port'));
            $this->info('DB_DATABASE: ' . config('database.connections.mysql.database'));
            
            $result = DB::select('SELECT COUNT(*) as count FROM Movimientos_Inventario');
            $this->info('Connection successful! Found ' . $result[0]->count . ' records.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Connection failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
