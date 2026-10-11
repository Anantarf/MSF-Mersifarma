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
                'name' => 'Budi Santoso (MM Nasional)',
                'username' => 'mm',
                'password' => $defaultPassword,
                'role' => 'mm',
                'manager_id' => null,
            ]
        );

        $rsm = User::updateOrCreate(
            ['email' => 'rsm@mersi.com'],
            [
                'name' => 'Siti Rahma (RSM DKI Jakarta)',
                'username' => 'rsm',
                'password' => $defaultPassword,
                'role' => 'rsm',
                'manager_id' => $mm->id,
            ]
        );

        $dm = User::updateOrCreate(
            ['email' => 'dm@mersi.com'],
            [
                'name' => 'Wisnu Santhika (DM Jakarta Selatan)',
                'username' => 'dm',
                'password' => $defaultPassword,
                'role' => 'dm',
                'manager_id' => $rsm->id,
            ]
        );

        // DM 2 (other manager for negative auth test)
        $dm2 = User::updateOrCreate(
            ['email' => 'dm2@mersi.com'],
            [
                'name' => 'Hendra Gunawan (DM Jakarta Timur)',
                'username' => 'dm2',
                'password' => $defaultPassword,
                'role' => 'dm',
                'manager_id' => $rsm->id,
            ]
        );

        // MR 1 (assigned to DM Wisnu)
        $mr = User::updateOrCreate(
            ['email' => 'mr@mersi.com'],
            [
                'name' => 'Rudi Hartono (MR Jakarta Selatan 1)',
                'username' => 'mr',
                'password' => $defaultPassword,
                'role' => 'mr',
                'manager_id' => $dm->id,
            ]
        );

        // MR 2 (peer MR for negative cross-MR test)
        $mr2 = User::updateOrCreate(
            ['email' => 'mr2@mersi.com'],
            [
                'name' => 'Ahmad Dahlan (MR Jakarta Selatan 2)',
                'username' => 'mr2',
                'password' => $defaultPassword,
                'role' => 'mr',
                'manager_id' => $dm->id,
            ]
        );

        // 3. Master Customers (Doctors in MCL)
        $doctors = [
            [
                'id' => 1,
                'name' => 'dr. Andi Wijaya, Sp.A',
                'specialization' => 'Spesialis Anak',
                'address' => 'RS Harapan Kita, Jl. S. Parman Kav. 87, Jakarta Barat',
                'phone' => '081234567801',
            ],
            [
                'id' => 2,
                'name' => 'dr. Budi Santoso, Sp.PD',
                'specialization' => 'Spesialis Penyakit Dalam',
                'address' => 'RS Cipto Mangunkusumo, Jl. Diponegoro No. 71, Jakarta Pusat',
                'phone' => '081234567802',
            ],
            [
                'id' => 3,
                'name' => 'dr. Citra Lestari, Sp.JP',
                'specialization' => 'Spesialis Jantung & Pembuluh Darah',
                'address' => 'RS Jantung Harapan Kita, Jl. Letjen S. Parman, Jakarta Barat',
                'phone' => '081234567803',
            ],
            [
                'id' => 4,
                'name' => 'dr. Dewi Anggraini, Sp.OG',
                'specialization' => 'Spesialis Obstetri & Ginekologi',
                'address' => 'RSIA Bunda, Jl. Teuku Cik Ditiro No. 28, Jakarta Pusat',
                'phone' => '081234567804',
            ],
            [
                'id' => 5,
                'name' => 'dr. Eko Prasetyo, Sp.B',
                'specialization' => 'Spesialis Bedah Umum',
                'address' => 'RS Siloam Kebon Jeruk, Jl. Raya Perjuangan No. 8, Jakarta Barat',
                'phone' => '081234567805',
            ],
            [
                'id' => 6,
                'name' => 'dr. Farhan Hakim, Sp.M',
                'specialization' => 'Spesialis Mata',
                'address' => 'Jakarta Eye Center, Jl. Teuku Cik Ditiro No. 46, Jakarta Pusat',
                'phone' => '081234567806',
            ],
        ];

        foreach ($doctors as $doc) {
            MasterCustomer::firstOrCreate(['id' => $doc['id']], $doc);
        }

        // 4. Products
        $products = [
            ['id' => 1, 'name' => 'Mersitropic 500mg', 'category' => 'Tablet Neurotropik'],
            ['id' => 2, 'name' => 'Mersidryl Expectorant 60ml', 'category' => 'Sirup Batuk & Flu'],
            ['id' => 3, 'name' => 'Mersiclav 625mg', 'category' => 'Antibiotik Amoxicillin-Clavulanate'],
            ['id' => 4, 'name' => 'Mersicort 1% 10g', 'category' => 'Krim Anti-inflamasi'],
            ['id' => 5, 'name' => 'Mersizol 20mg', 'category' => 'Kapsul Omeprazole'],
        ];

        foreach ($products as $prod) {
            Product::firstOrCreate(['id' => $prod['id']], $prod);
        }

        // 5. Demo Scenarios (Call List Approved, Audit Logs, Call Plans)
        $this->call(DemoScenarioSeeder::class);
    }
}
