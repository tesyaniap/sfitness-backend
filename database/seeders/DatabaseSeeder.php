<?php

namespace Database\Seeders;

use App\Models\FitnessClass;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@sfitness.com',
            'password' => Hash::make('password'),
            'role'     => 'super_admin',
        ]);

        $admin = User::create([
            'name'     => 'Admin Staff',
            'email'    => 'admin@sfitness.com',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Member Demo',
            'email'    => 'member@sfitness.com',
            'password' => Hash::make('password'),
            'role'     => 'member',
        ]);

        $this->call(MemberPackageSeeder::class);

        $classes = [
            ['name' => 'Zumba Morning', 'type' => 'zumba', 'duration_minutes' => 60, 'quota' => 20, 'price' => 50000],
            ['name' => 'Yoga Relaxation', 'type' => 'yoga', 'duration_minutes' => 75, 'quota' => 15, 'price' => 60000],
            ['name' => 'Pilates Core', 'type' => 'pilates', 'duration_minutes' => 60, 'quota' => 12, 'price' => 65000],
            ['name' => 'Aerobic Fun', 'type' => 'aerobic', 'duration_minutes' => 45, 'quota' => 25, 'price' => 45000],
        ];

        foreach ($classes as $i => $class) {
            FitnessClass::create(array_merge($class, [
                'instructor_id' => $admin->id,
                'schedule_at'   => now()->addDays($i + 1)->setTime(8, 0),
                'location'      => 'Studio ' . chr(65 + $i),
                'description'   => 'Kelas ' . $class['name'] . ' untuk semua level.',
            ]));
        }

        $products = [
            ['name' => 'Air Mineral 600ml', 'category' => 'drink', 'price' => 5000, 'stock' => 100],
            ['name' => 'Jus Alpukat', 'category' => 'drink', 'price' => 18000, 'stock' => 30],
            ['name' => 'Salad Buah', 'category' => 'food', 'price' => 25000, 'stock' => 20],
            ['name' => 'Protein Bar', 'category' => 'supplement', 'price' => 35000, 'stock' => 50],
            ['name' => 'Roti Gandum', 'category' => 'food', 'price' => 12000, 'stock' => 40],
            ['name' => 'Whey Protein Sachet', 'category' => 'supplement', 'price' => 45000, 'stock' => 25],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
