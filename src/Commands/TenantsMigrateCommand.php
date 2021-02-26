<?php

namespace LumenMicroservice\Commands;

use LumenMicroservice\Models\Domain;
use Illuminate\Console\Command;

class TenantsMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate {tenant?} {--fresh} {--seed} {--landlord}';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->argument('tenant')) {
            $this->migrate(
                Domain::find($this->argument('tenant'))
            );

        } else {
            Domain::all()->each(
                fn($tenant) => $this->migrate($tenant)
            );
        }
    }

    /**
     * @param \App\Domain $tenant
     * @return int
     */
    public function migrate($tenant)
    {
        $tenant->use();

        $this->line('');
        $this->line("-----------------------------------------");
        $this->info("Migrating Domain ({$tenant->domain}) -> schema: ({$tenant->database_schema})");
        $this->line("-----------------------------------------");

        $options = ['--force' => true];

        if ($this->option('seed')) {
            $options['--seed'] = true;
        }

        $this->call(
            $this->option('fresh') ? 'migrate:fresh' : 'migrate',
            $options
        );
    }
}