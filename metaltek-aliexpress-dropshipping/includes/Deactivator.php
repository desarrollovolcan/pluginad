<?php

namespace MAD;

use MAD\Sync\Scheduler;

class Deactivator {
    public static function deactivate(): void {
        Scheduler::clear();
    }
}
