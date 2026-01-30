<?php

namespace Pterodactyl\Console;


trait RequiresDatabaseMigrations
{
    
    protected function hasCompletedMigrations(): bool
    {
        
        $migrator = $this->getLaravel()->make('migrator');

        $files = $migrator->getMigrationFiles(database_path('migrations'));

        if (!$migrator->repositoryExists()) {
            return false;
        }

        if (array_diff(array_keys($files), $migrator->getRepository()->getRan())) {
            return false;
        }

        return true;
    }

    
    protected function showMigrationWarning(): void
    {
        $this->getOutput()->writeln('<options=bold>
| @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ |
|                                                                              |
|               Your database has not been properly migrated!                  |
|                                                                              |
| @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ |</>

You must run the following command to finish migrating your database:

  <fg=green;options=bold>php artisan migrate --step --force</>

You will not be able to use Pterodactyl Panel as expected without fixing your
database state by running the command above.
');

        $this->getOutput()->error('You must correct the error above before continuing.');
    }
}
