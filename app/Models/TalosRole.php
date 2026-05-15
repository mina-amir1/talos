<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TalosRole extends Model
{
    protected $table = 'talos_roles';

    protected $fillable = ['name', 'description', 'permissions'];

    protected $casts = ['permissions' => 'array'];

    public function users()
    {
        return $this->hasMany(TalosUser::class, 'role_id');
    }
}
