<?php

namespace Database\Seeders;

use App\Models\MasterCustomer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $roles = [
            ['code' => 'mr', 'name' => 'Medical Representative'],
            ['code' => 'dm', 'name' => 'District Manager'],
            ['code' => 'rsm', 'name' => 'Regional Sales Manager'],
            ['code' => 'mm', 'name' => 'Medical / Marketing Manager'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['code' => $role['code']], $role);
        }

        // 2. Synthetic Users (Hierarchy: MR -> DM -> RSM -> MM)
        $defaultPassword = Hash::make('password');

        $mm = User::updateOrCreate(
            ['email' => 'mm@mersi.com'],
            [
                'name' => 'Budi Santoso',
                'username' => 'mm',
                'district' => null,
                'password' => $defaultPassword,
                'role' => 'mm',
                'manager_id' => null,
            ]
        );

        $rsm = User::updateOrCreate(
            ['email' => 'rsm@mersi.com'],
            [
                'name' => 'Siti Rahma',
                'username' => 'rsm',
                'district' => null,
                'password' => $defaultPassword,
                'role' => 'rsm',
                'manager_id' => $mm->id,
            ]
        );

        $dm = User::updateOrCreate(
            ['email' => 'dm@mersi.com'],
            [
                'name' => 'Joko Widodo',
                'username' => 'dm',
                'district' => 'Jakarta Selatan',
                'password' => $defaultPassword,
                'role' => 'dm',
                'manager_id' => $rsm->id,
            ]
        );

        // DM 2 (other manager for negative auth test)
        $dm2 = User::updateOrCreate(
            ['email' => 'dm2@mersi.com'],
            [
                'name' => 'Hendra Gunawan',
                'username' => 'dm2',
                'district' => 'Jakarta Timur',
                'password' => $defaultPassword,
                'role' => 'dm',
                'manager_id' => $rsm->id,
            ]
        );

        // MR 1 (Medical Representative Jakarta Selatan under DM Joko)
        $mr = User::updateOrCreate(
            ['email' => 'mr@mersi.com'],
            [
                'name' => 'Rudi Hartono',
                'username' => 'mr',
                'district' => 'Jakarta Selatan',
                'password' => $defaultPassword,
                'role' => 'mr',
                'manager_id' => $dm->id,
            ]
        );

        // MR 2 (Medical Representative Jakarta Selatan 2 under DM Joko)
        $mr2 = User::updateOrCreate(
            ['email' => 'mr2@mersi.com'],
            [
                'name' => 'Ahmad Dahlan',
                'username' => 'mr2',
                'district' => 'Jakarta Selatan',
                'password' => $defaultPassword,
                'role' => 'mr',
                'manager_id' => $dm->id,
            ]
        );

        // MR 3 (Medical Representative Jakarta Timur under DM2 Hendra)
        $mr3 = User::updateOrCreate(
            ['email' => 'mr3@mersi.com'],
            [
                'name' => 'Nadia Putri',
                'username' => 'mr3',
                'district' => 'Jakarta Timur',
                'password' => $defaultPassword,
                'role' => 'mr',
                'manager_id' => $dm2->id,
            ]
        );

        // MR 4 (Medical Representative Jakarta Timur 2 under DM2 Hendra)
        $mr4 = User::updateOrCreate(
            ['email' => 'mr4@mersi.com'],
            [
                'name' => 'Bambang Irawan',
                'username' => 'mr4',
                'district' => 'Jakarta Timur',
                'password' => $defaultPassword,
                'role' => 'mr',
                'manager_id' => $dm2->id,
            ]
        );

        // 3. Master Customers (Doctors in MCL)
        $doctors = [
            [
                'id' => 1,
                'name' => 'dr. Andi Pratama, Sp.A.',
                'specialization' => 'Spesialis Anak',
                'district' => 'Jakarta Selatan',
                'address' => 'RS Anak Sehat, Jl. Wijaya II No. 10, Jakarta Selatan',
                'phone' => '081234567801',
            ],
            [
                'id' => 2,
                'name' => 'dr. Rina Maharani, Sp.A.',
                'specialization' => 'Spesialis Anak',
                'district' => 'Jakarta Selatan',
                'address' => 'Klinik Pediatri Melati, Jl. Melawai Raya No. 21, Jakarta Selatan',
                'phone' => '081234567802',
            ],
            [
                'id' => 3,
                'name' => 'dr. Maya Putri, Sp.A.',
                'specialization' => 'Spesialis Anak',
                'district' => 'Jakarta Selatan',
                'address' => 'RS Ibu dan Anak Permata, Jl. Radio Dalam No. 5, Jakarta Selatan',
                'phone' => '081234567803',
            ],
            [
                'id' => 4,
                'name' => 'dr. Fajar Nugroho, Sp.PD.',
                'specialization' => 'Penyakit Dalam',
                'district' => 'Jakarta Selatan',
                'address' => 'RS Medika Selatan, Jl. Fatmawati No. 12, Jakarta Selatan',
                'phone' => '081234567804',
            ],
            [
                'id' => 5,
                'name' => 'dr. Tania Kusuma',
                'specialization' => 'Dokter Umum',
                'district' => 'Jakarta Selatan',
                'address' => 'Klinik Keluarga Tania, Jl. Buncit Raya No. 88, Jakarta Selatan',
                'phone' => '081234567805',
            ],
            [
                'id' => 6,
                'name' => 'dr. Putri Lestari, Sp.A.',
                'specialization' => 'Spesialis Anak',
                'district' => 'Jakarta Timur',
                'address' => 'RS Harapan Timur, Jl. Pemuda No. 40, Jakarta Timur',
                'phone' => '081234567806',
            ],
            [
                'id' => 7,
                'name' => 'dr. Bima Saputra, Sp.A.',
                'specialization' => 'Spesialis Anak',
                'district' => 'Jakarta Timur',
                'address' => 'Klinik Anak Rawamangun, Jl. Balai Pustaka No. 9, Jakarta Timur',
                'phone' => '081234567807',
            ],
        ];

        foreach ($doctors as $doc) {
            MasterCustomer::updateOrCreate(['id' => $doc['id']], $doc);
        }
        // 4. Products
        $products = [
            ['id' => 1, 'name' => 'PediaTemp Sirup', 'category' => 'Pediatri / antipiretik'],
            ['id' => 2, 'name' => 'PediaHydra', 'category' => 'Pediatri / rehidrasi oral'],
            ['id' => 3, 'name' => 'PediaZinc', 'category' => 'Pediatri / suplementasi'],
            ['id' => 4, 'name' => 'VitaCare General', 'category' => 'Umum / suplemen'],
            ['id' => 5, 'name' => 'PediaCare Plus', 'category' => 'Pediatri / multivitamin'],
        ];

        foreach ($products as $prod) {
            Product::updateOrCreate(['id' => $prod['id']], $prod);
        }
        // 5. Demo Scenarios (Dapat dijalankan opsional dengan: php artisan db:seed --class=DemoScenarioSeeder)
        // $this->call(DemoScenarioSeeder::class);
    }
}
