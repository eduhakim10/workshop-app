<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          // Create roles
          $admin = Role::create(['name' => 'Admin']);
          $manager = Role::create(['name' => 'Manager']);
          $staff = Role::create(['name' => 'Staff']);
          $tech = Role::create(['name' => 'Technician']);
          $head = Role::create(['name' => 'HeadOffice']);
  
          // Create permissions
          Permission::create(['name' => 'view users']);
          Permission::create(['name' => 'create users']);
          Permission::create(['name' => 'edit users']);
          Permission::create(['name' => 'delete users']);
          //  Permissions for Employees
          Permission::create(['name' => 'view employees']);
          Permission::create(['name' => 'create employees']);
          Permission::create(['name' => 'edit employees']);
          Permission::create(['name' => 'delete employees']);
  
          // Permissions for Items
          Permission::create(['name' => 'view items']);
          Permission::create(['name' => 'create items']);
          Permission::create(['name' => 'edit items']);
          Permission::create(['name' => 'delete items']);

          Permission::create(['name' => 'view vehicles']);
          Permission::create(['name' => 'create vehicles']);
          Permission::create(['name' => 'edit vehicles']);
          Permission::create(['name' => 'delete vehicles']);

          Permission::create(['name' => 'view workshop locations']);
          Permission::create(['name' => 'create workshop locations']);
          Permission::create(['name' => 'edit workshop locations']);
          Permission::create(['name' => 'delete workshop locations']);

          Permission::create(['name' => 'view customers']);
          Permission::create(['name' => 'create customers']);
          Permission::create(['name' => 'edit customers']);
          Permission::create(['name' => 'delete customers']);

          Permission::create(['name' => 'view services']);
          Permission::create(['name' => 'edit services']);

          Permission::create(['name' => 'view dashboard']);
          Permission::create(['name' => 'view reports']);
  
          // Assign permissions to roles
          $admin->givePermissionTo(Permission::all());
          $manager->givePermissionTo(['view users', 'create users', 'edit users']);
          $staff->givePermissionTo(['view users']);
          $head->givePermissionTo(['view services']);
          $head->givePermissionTo(['edit services']);

                
    }
}
