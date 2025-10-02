<?php

namespace Litepie\Roles\Tests;

use Litepie\Roles\Models\Role;

class RoleTest extends TestCase
{
    /** @test */
    public function it_can_create_a_role()
    {
        $role = Role::create(['name' => 'admin']);
        
        $this->assertEquals('admin', $role->name);
        $this->assertNotNull($role->guard_name);
    }

    /** @test */
    public function it_can_assign_role_to_user()
    {
        $this->testUser->assignRole($this->testRole);
        
        $this->assertTrue($this->testUser->hasRole('testRole'));
    }

    /** @test */
    public function it_can_remove_role_from_user()
    {
        $this->testUser->assignRole($this->testRole);
        $this->testUser->removeRole($this->testRole);
        
        $this->assertFalse($this->testUser->hasRole('testRole'));
    }

    /** @test */
    public function it_can_check_multiple_roles()
    {
        $role2 = Role::create(['name' => 'editor']);
        
        $this->testUser->assignRole([$this->testRole, $role2]);
        
        $this->assertTrue($this->testUser->hasAnyRole(['testRole', 'editor']));
        $this->assertTrue($this->testUser->hasAllRoles(['testRole', 'editor']));
    }

    /** @test */
    public function it_can_sync_roles()
    {
        $role2 = Role::create(['name' => 'editor']);
        $role3 = Role::create(['name' => 'moderator']);
        
        $this->testUser->assignRole([$this->testRole, $role2]);
        $this->testUser->syncRoles([$role3]);
        
        $this->assertFalse($this->testUser->hasRole('testRole'));
        $this->assertFalse($this->testUser->hasRole('editor'));
        $this->assertTrue($this->testUser->hasRole('moderator'));
    }
}