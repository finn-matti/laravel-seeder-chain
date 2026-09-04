<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Fixtures\Seeders;

use FinnMatti\SeederChain\Concerns\HasSeederContext;
use FinnMatti\SeederChain\Contracts\ChainableSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\ExecutionLog;
use Illuminate\Database\Seeder;

final class ProjectSeeder extends Seeder implements ChainableSeeder
{
    use HasSeederContext;

    public static function dependencies(): array
    {
        return [UserSeeder::class];
    }

    public function run(): void
    {
        ExecutionLog::record(self::class);

        $users = $this->recall(UserSeeder::class, 'users');

        $this->remember('projects', [
            ['name' => 'Autumn Catalogue', 'owner' => $users[0]['name']],
        ]);
    }
}
