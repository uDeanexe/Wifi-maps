<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\Node;
use App\Models\NodeType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MappingBasicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_nodes_and_links(): void
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

    public function test_node_coordinates_must_be_complete_and_core_count_positive(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $type = NodeType::create(['name' => 'odc', 'label' => 'ODC']);

        $this->actingAs($user)->post(route('nodes.store'), [
            'node_type_id' => $type->id,
            'code' => 'ODC-PARTIAL',
            'latitude' => -6.2,
        ])->assertSessionHasErrors('longitude');

        $source = Node::create(['node_type_id' => $type->id, 'code' => 'ODC-A']);
        $target = Node::create(['node_type_id' => $type->id, 'code' => 'ODC-B']);
        $this->actingAs($user)->post(route('links.store'), [
            'source_node_id' => $source->id,
            'target_node_id' => $target->id,
            'core_count' => 0,
        ])->assertSessionHasErrors('core_count');
    }

    public function test_admin_can_update_regular_user_but_cannot_promote_to_superadmin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $user = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);

        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Teknisi Baru',
            'email' => $user->email,
            'role' => 'supervisor_noc',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Teknisi Baru', 'role' => 'supervisor_noc']);

        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'superadmin',
            'is_active' => '1',
        ])->assertForbidden();
    }

    public function test_user_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'admin',
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_inactive_authenticated_user_is_logged_out(): void
    {
        $user = User::factory()->create(['role' => 'teknisi', 'is_active' => false]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
