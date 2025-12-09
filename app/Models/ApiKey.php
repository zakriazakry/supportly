<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_instance_id',
        'name',
        'key',
        'permissions',
        'is_active',
        'last_used'
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'last_used' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $hidden = [];

    /**
     * Get the WhatsApp instance that owns the API key
     */
    public function whatsappInstance()
    {
        return $this->belongsTo(WhatsAppInstance::class, 'whatsapp_instance_id');
    }

    /**
     * Check if the API key has a specific permission
     */
    public function hasPermission($permission)
    {
        return in_array($permission, $this->permissions);
    }

    /**
     * Check if the API key has all of the given permissions
     */
    public function hasAllPermissions(array $permissions)
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if the API key has any of the given permissions
     */
    public function hasAnyPermission(array $permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Update the last_used timestamp
     */
    public function markAsUsed()
    {
        $this->last_used = now();
        $this->save();
    }

    /**
     * Verify if a plain text key matches this hashed key
     */
    public function verifyKey($plainKey)
    {
        return hash('sha256', $plainKey) === $this->key;
    }
}
