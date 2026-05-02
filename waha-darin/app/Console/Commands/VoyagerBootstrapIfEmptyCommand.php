<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use TCG\Voyager\Models\DataType;

class VoyagerBootstrapIfEmptyCommand extends Command
{
    protected $signature = 'voyager:bootstrap-if-empty';

    protected $description = 'Seed Voyager base UI once when empty; then catalog/app BREAD from fixtures if missing.';

    public function handle(): int
    {
        if (! DataType::query()->exists()) {
            $this->warn('No Voyager BREAD (data_types) found; seeding VoyagerDatabaseSeeder...');
            $this->call('db:seed', [
                '--class' => 'VoyagerDatabaseSeeder',
                '--force' => true,
            ]);
            $this->info('Voyager admin UI metadata seeded.');
        }

        // Base seed only wires users/menus/custom tools — catalog BREAD (books, etc.) ships in voyager_app_bread.json from dev.
        if (! DataType::query()->where('slug', 'books')->exists()) {
            $fixture = database_path('fixtures/voyager_app_bread.json');
            if (is_readable($fixture)) {
                $this->warn('Importing Voyager catalog/app BREAD from fixtures...');
                $this->call('db:seed', [
                    '--class' => 'VoyagerAppBreadFromFixtureSeeder',
                    '--force' => true,
                ]);
            }
        }

        return 0;
    }
}
