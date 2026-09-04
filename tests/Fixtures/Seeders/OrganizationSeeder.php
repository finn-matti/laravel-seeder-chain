<?php

declare(strict_types=1);

namespace FinnMatti\SeederChain\Tests\Fixtures\Seeders;

use FinnMatti\SeederChain\Concerns\HasSeederContext;
use FinnMatti\SeederChain\Contracts\ChainableSeeder;
use FinnMatti\SeederChain\Tests\Fixtures\ExecutionLog;
use Illuminate\Database\Seeder;

final class OrganizationSeeder extends Seeder implements ChainableSeeder
{
    use HasSeederContext;

    public static function dependencies(): array
    {
        return [];
    }

    public function run(): void
    {
        ExecutionLog::record(self::class);

        $this->remember('organization', ['name' => 'Acme Publishing']);
    }
}
