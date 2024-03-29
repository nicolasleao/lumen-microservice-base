<?php

namespace LumenMicroservice\Commands;

use LumenMicroservice\Models\Tenant;
use Illuminate\Console\Command;

class TenantsMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate {tenant?} {--seed} {--landlord}';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->argument('tenant')) {
            $this->migrate(
                Tenant::find($this->argument('tenant'))
            );

        } else {
            Tenant::all()->each(
                fn($tenant) => $this->migrate($tenant)
            );
        }
    }

    /**
     * @param \App\Tenant $tenant
     * @return int
     */
    public function migrate($tenant)
    {
    	if($tenant->database_host) {
        	$tenant->useConnection($tenant->toArray());
    	} else {
        	$tenant->useSchema($tenant->database_schema);
    	}

        $this->line('');
        $this->line("-----------------------------------------");
        $this->info("Migrating Tenant ({$tenant->name}) -> schema: ({$tenant->database_schema})");
        $this->line("-----------------------------------------");

        $options = ['--force' => true];

        if ($this->option('seed')) {
            $options['--seed'] = true;
        }

        $this->call('migrate', $options);
    }
}