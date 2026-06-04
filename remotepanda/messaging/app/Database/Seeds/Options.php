<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Options extends Seeder
{
    public function run()
    {
        helper(["option"]);

        update_option(META_KEY_ENABLED, true);
        update_option("per-page", DEFAULT_PER_PAGE);
        update_option("site-name", "Hillpaul Health Scan Clinic");
        update_option("message", "Hello {{name}},\nHere at {{company}}, We wanted to say happy birthday on your {{ordinal}} birthday");
    }
}
