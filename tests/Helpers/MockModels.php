<?php

namespace Fullstack\Inbounder\Tests\Helpers;

use Illuminate\Database\Eloquent\Model;

class MockUser extends Model
{
    protected $table = 'users';

    protected $fillable = ['id', 'email', 'tenant_id'];

    protected $guarded = [];

    public function hasPermissionTo($permission, $role = null)
    {
        return true; // Mock implementation
    }

    public function hasRole($roles)
    {
        return true; // Mock implementation
    }

    public static function where($column, $value)
    {
        return new class
        {
            public function first()
            {
                return new MockUser([
                    'id' => 1,
                    'email' => 'sender@example.com',
                    'tenant_id' => 1,
                ]);
            }
        };
    }

    public static function create(array $attributes = [])
    {
        $user = new MockUser($attributes);
        // Ensure id is set if not provided
        if (! isset($user->id)) {
            $user->id = 1;
        }
        if (! isset($user->tenant_id)) {
            $user->tenant_id = 1;
        }

        return $user;
    }
}

class MockTenant extends Model
{
    protected $table = 'tenants';

    protected $fillable = ['id', 'mail_domain', 'webhook_signing_string'];

    protected $guarded = [];

    public static function where($column, $value)
    {
        return new class
        {
            public function first()
            {
                return new MockTenant([
                    'id' => 1,
                    'mail_domain' => 'mg.example.com',
                    'webhook_signing_string' => 'test-signing-key',
                ]);
            }
        };
    }

    public static function create(array $attributes = [])
    {
        $tenant = new MockTenant($attributes);
        // Ensure id is set if not provided
        if (! isset($tenant->id)) {
            $tenant->id = 1;
        }

        return $tenant;
    }
}
