<?php

namespace Database\Seeders;

use App\Models\NodeType;
use App\Models\Incident;
use App\Models\Link;
use App\Models\Node;
use App\Models\WorkReport;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        collect([
            [1, 'odc', 'ODC', 'odc.png'],
            [2, 'pon', 'PON', 'pon.png'],
            [3, 'box', 'Box / ODP', 'box.png'],
            [4, 'pole', 'Tiang', 'pole.png'],
            [5, 'customer', 'Customer', 'customer.png'],
            [6, 'server', 'Server', 'server.png'],
            [7, 'olc', 'OLC', 'olc.png'],
        ])->each(function (array $type): void {
            NodeType::updateOrCreate(
                ['id' => $type[0]],
                ['name' => $type[1], 'label' => $type[2], 'icon' => $type[3]]
            );
        });

        User::firstOrCreate([
            'email' => env('DEFAULT_SUPERADMIN_EMAIL', 'jonusadeveloper@gmail.com'),
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make(env('DEFAULT_SUPERADMIN_PASSWORD', 'superadmin123')),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        if (env('AUTO_SEED_DEMO', false) && Node::count() === 0) {
            $odc = Node::create([
                'node_type_id' => 1,
                'code' => 'ODC-DEMO-01',
                'name' => 'ODC Demo',
                'latitude' => -6.2615,
                'longitude' => 107.1528,
                'address' => 'Bekasi Timur',
                'notes' => 'Node utama demo',
                'topology_x' => 140,
                'topology_y' => 120,
            ]);

            $pon = Node::create([
                'node_type_id' => 2,
                'code' => 'PON-DEMO-01',
                'name' => 'PON Demo',
                'latitude' => -6.2621,
                'longitude' => 107.1540,
                'address' => 'Bekasi Timur',
                'notes' => 'Distribusi PON demo',
                'topology_x' => 420,
                'topology_y' => 170,
            ]);

            $odp = Node::create([
                'node_type_id' => 3,
                'code' => 'ODP-DEMO-01',
                'name' => 'ODP Demo',
                'latitude' => -6.2630,
                'longitude' => 107.1552,
                'address' => 'Area pelanggan demo',
                'notes' => 'Box / ODP demo',
                'topology_x' => 700,
                'topology_y' => 260,
            ]);

            Link::create([
                'source_node_id' => $odc->id,
                'target_node_id' => $pon->id,
                'cable_type' => 'FO',
                'core_count' => 12,
                'core_number' => '1-12',
                'notes' => 'Link demo ODC ke PON',
            ]);

            Link::create([
                'source_node_id' => $pon->id,
                'target_node_id' => $odp->id,
                'cable_type' => 'FO',
                'core_count' => 8,
                'core_number' => '1-8',
                'notes' => 'Link demo PON ke ODP',
            ]);

            $incident = Incident::create([
                'node_id' => $odp->id,
                'category' => 'kerusakan',
                'title' => 'Redaman tinggi di ODP Demo',
                'description' => 'Sinyal pelanggan melemah dan perlu pengecekan lapangan.',
                'reporter_name' => 'NOC Demo',
                'reporter_contact' => '0800000000',
                'noc_admin_name' => 'Admin NOC',
                'technician_name' => 'Teknisi Demo',
                'technician_contact' => '081234567890',
                'work_order_notes' => 'Cek konektor dan patch cord.',
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            WorkReport::create([
                'incident_id' => $incident->id,
                'node_id' => $odp->id,
                'technician_name' => 'Teknisi Demo',
                'report_title' => 'Pengecekan ODP Demo',
                'description' => 'Data demo untuk memastikan report dan rekam kerja tampil.',
                'status' => 'completed',
            ]);
        }
    }
}
