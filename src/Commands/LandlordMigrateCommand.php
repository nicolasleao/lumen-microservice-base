<?php

namespace LumenMicroservice\Commands;

use LumenMicroservice\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LandlordMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'landlord:migrate {--fresh} {--seed}';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->migrate();
    }

    public function migrate()
    {
        $this->line('');
        $this->line("-----------------------------------------");
        $this->info("Migrating landlord database");
        $this->line("-----------------------------------------");

        DB::statement('CREATE SCHEMA IF NOT EXISTS ' . env('LANDLORD_DB_SCHEMA', 'landlord') . ';');
        DB::statement('SET search_path TO ' . env('LANDLORD_DB_SCHEMA', 'landlord'));
        
        $options = ['--path' => "vendor/nicolasleao/lumen-microservice-base/Migrations", "--database" => "landlord", "--force" => true];

        if ($this->option('seed')) {
            $options['--seed'] = true;
        }

        $this->call(
            $this->option('fresh') ? 'migrate:fresh' : 'migrate',
            $options
        );
    }
}