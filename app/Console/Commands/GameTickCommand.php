<?php

namespace App\Console\Commands;

use App\Services\GameEngine;
use Illuminate\Console\Command;

class GameTickCommand extends Command
{
    protected $signature = 'game:tick';

    protected $description = 'Process arrived movements, finished building and recruit queues, and market transports.';

    public function handle(GameEngine $gameEngine): int
    {
        $results = $gameEngine->tickAll();
        $this->info("Game ticked successfully: {$results['movements']} movements processed, {$results['transports']} transports delivered.");

        return self::SUCCESS;
    }
}
