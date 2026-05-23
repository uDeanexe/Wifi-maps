<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Link;
use App\Models\Node;
use App\Models\NodeType;
use App\Models\User;
use App\Models\WorkReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MappingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_nodes_links_and_complete_incident(): void
    {
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $odc = NodeType::create(['name' => 'odc', 'label' => 'ODC', 'icon' => 'odc.png']);
        $pon = NodeType::create(['name' => 'pon', 'label' => 'PON', 'icon' => 'pon.png']);

        $this->actingAs($user)
            ->post(route('nodes.store'), [
                'node_type_id' => $odc->id,
                'code' => 'ODC-TST-01',
                'name' => 'ODC Test',
                'latitude' => -6.2615,
                'longitude' => 107.1528,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('nodes.store'), [
                'node_type_id' => $pon->id,
                'code' => 'PON-TST-01',
                'name' => 'PON Test',
                'latitude' => -6.2625,
                'longitude' => 107.1538,
            ])
            ->assertRedirect();

        $source = Node::where('code', 'ODC-TST-01')->firstOrFail();
        $target = Node::where('code', 'PON-TST-01')->firstOrFail();

        $this->actingAs($user)
            ->post(route('links.store'), [
                'source_node_id' => $source->id,
                'target_node_id' => $target->id,
                'cable_type' => 'FO',
                'core_count' => 12,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('links', [
            'source_node_id' => $source->id,
            'target_node_id' => $target->id,
            'core_count' => 12,
        ]);

        $this->actingAs($user)
            ->post(route('incidents.store'), [
                'node_id' => $source->id,
                'category' => 'kerusakan',
                'title' => 'Kabel putus',
                'technician_name' => 'Teknisi A',
                'description' => 'Loss tinggi',
            ])
            ->assertRedirect();

        $incident = Incident::where('title', 'Kabel putus')->firstOrFail();
        $this->assertSame('assigned', $incident->status);
        $this->assertNotNull($incident->assigned_at);

        $this->actingAs($user)
            ->patch(route('incidents.confirm', $incident))
            ->assertRedirect();

        $incident->refresh();
        $this->assertSame('in_progress', $incident->status);

        $this->actingAs($user)
            ->get(route('incidents.surat-jalan.review', $incident))
            ->assertOk()
            ->assertSee('Review Surat Jalan Gangguan')
            ->assertSee('Download PDF');

        $this->actingAs($user)
            ->get(route('nodes.surat-jalan.review', $source))
            ->assertOk()
            ->assertSee('Review Surat Jalan Node');

        $this->actingAs($user)
            ->patch(route('incidents.complete', $incident), [
                'technician_report' => 'Kabel sudah disambung ulang',
                'status' => 'completed',
            ])
            ->assertRedirect();

        $incident->refresh();
        $this->assertSame('completed', $incident->status);
        $this->assertNotNull($incident->completed_at);
        $this->assertDatabaseHas('work_reports', [
            'incident_id' => $incident->id,
            'node_id' => $source->id,
            'technician_name' => 'Teknisi A',
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->put(route('links.update', Link::firstOrFail()), [
                'source_node_id' => $source->id,
                'target_node_id' => $target->id,
                'cable_type' => 'FO Aerial',
                'core_count' => 24,
            ])
            ->assertRedirect();

        $this->assertSame(1, WorkReport::count());
    }

    public function test_link_cannot_target_the_same_node(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $type = NodeType::create(['name' => 'odc', 'label' => 'ODC']);
        $node = Node::create(['node_type_id' => $type->id, 'code' => 'ODC-SELF']);

        $this->actingAs($user)
            ->from(route('links.index'))
            ->post(route('links.store'), [
                'source_node_id' => $node->id,
                'target_node_id' => $node->id,
            ])
            ->assertSessionHasErrors('target_node_id');

        $this->assertSame(0, Link::count());
    }

    public function test_reports_can_be_downloaded(): void
    {
        $user = User::create([
            'name' => 'Reporter',
            'email' => 'reporter@example.com',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $type = NodeType::create(['name' => 'odc', 'label' => 'ODC']);
        Node::create(['node_type_id' => $type->id, 'code' => 'ODC-RPT-01']);

        $this->actingAs($user)
            ->get(route('reports.links.csv'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $response = $this->actingAs($user)->get(route('reports.topology.pdf'));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->streamedContent());
    }
}
