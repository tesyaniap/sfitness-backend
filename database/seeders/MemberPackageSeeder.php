<?php
namespace Database\Seeders;
use App\Models\MemberPackage;
use Illuminate\Database\Seeder;

class MemberPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Single Visit',   'type' => 'single', 'visit_quota' => 1,  'active_days' => 0],
            ['name' => 'Member 4x Visit','type' => '4x',     'visit_quota' => 4,  'active_days' => 30],
            ['name' => 'Member 8x Visit','type' => '8x',     'visit_quota' => 8,  'active_days' => 30],
        ];

        foreach ($packages as $pkg) {
            MemberPackage::firstOrCreate(['type' => $pkg['type']], $pkg);
        }
    }
}
