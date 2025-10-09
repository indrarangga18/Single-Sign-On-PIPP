<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = User::create([
            'username' => 'superadmin',
            'email' => 'superadmin@pipp.kkp.go.id',
            'password' => Hash::make('password123'),
            'first_name' => 'Super',
            'last_name' => 'Administrator',
            'phone' => '081234567890',
            'nip' => '199001012020011001',
            'position' => 'System Administrator',
            'department' => 'IT Department',
            'office_location' => 'Jakarta Pusat',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super-admin');

        // Create Admin
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin@pipp.kkp.go.id',
            'password' => Hash::make('password123'),
            'first_name' => 'Admin',
            'last_name' => 'PIPP',
            'phone' => '081234567891',
            'nip' => '199002022020022002',
            'position' => 'Administrator',
            'department' => 'Direktorat Kepelabuhanan Perikanan',
            'office_location' => 'Jakarta Pusat',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Create Sahbandar Officer
        $sahbandar = User::create([
            'username' => 'sahbandar01',
            'email' => 'sahbandar@pipp.kkp.go.id',
            'password' => Hash::make('password123'),
            'first_name' => 'Sahbandar',
            'last_name' => 'Officer',
            'phone' => '081234567892',
            'nip' => '199003032020033003',
            'position' => 'Sahbandar',
            'department' => 'Pelabuhan Perikanan',
            'office_location' => 'Pelabuhan Muara Baru',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $sahbandar->assignRole('sahbandar');

        // Create SPB Officer
        $spb = User::create([
            'username' => 'spb01',
            'email' => 'spb@pipp.kkp.go.id',
            'password' => Hash::make('password123'),
            'first_name' => 'SPB',
            'last_name' => 'Officer',
            'phone' => '081234567893',
            'nip' => '199004042020044004',
            'position' => 'Petugas SPB',
            'department' => 'Surat Persetujuan Berlayar',
            'office_location' => 'Pelabuhan Muara Baru',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $spb->assignRole('spb-officer');

        // Create SHTI Officer
        $shti = User::create([
            'username' => 'shti01',
            'email' => 'shti@pipp.kkp.go.id',
            'password' => Hash::make('password123'),
            'first_name' => 'SHTI',
            'last_name' => 'Officer',
            'phone' => '081234567894',
            'nip' => '199005052020055005',
            'position' => 'Petugas SHTI',
            'department' => 'Sertifikat Hasil Tangkap Ikan',
            'office_location' => 'Pelabuhan Muara Baru',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $shti->assignRole('shti-officer');

        // Create EPIT Officer
        $epit = User::create([
            'username' => 'epit01',
            'email' => 'epit@pipp.kkp.go.id',
            'password' => Hash::make('password123'),
            'first_name' => 'EPIT',
            'last_name' => 'Officer',
            'phone' => '081234567895',
            'nip' => '199006062020066006',
            'position' => 'Petugas EPIT',
            'department' => 'Electronic Permit Information Technology',
            'office_location' => 'Jakarta Pusat',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $epit->assignRole('epit-officer');

        // Create Regular User
        $user = User::create([
            'username' => 'user01',
            'email' => 'user@pipp.kkp.go.id',
            'password' => Hash::make('password123'),
            'first_name' => 'Regular',
            'last_name' => 'User',
            'phone' => '081234567896',
            'nip' => '199007072020077007',
            'position' => 'Staff',
            'department' => 'Direktorat Kepelabuhanan Perikanan',
            'office_location' => 'Jakarta Pusat',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('user');
    }
}