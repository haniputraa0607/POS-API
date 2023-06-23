<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Modules\Outlet\Entities\Outlet;
use Modules\User\Database\factories\UserFactory;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'equal_id',
        'name',
        'username',
        'email',
        'phone',
        'idc',
        'birthdate',
        'email_verified_at',
        'type',
        'outlet_id',
        'password',
        'id_city',
        'address',
        'gender',
        'level'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function scopeDoctor(Builder $query): Builder
    {
        return $query->where('type', 'salesman');
    }

    public function scopeCashier(Builder $query): Builder
    {
        return $query->where('type', 'cashier');
    }

    public function scopeDisplay(Builder $query): Builder
    {
        return $query->with(['outlet.district'])
            ->select('id', 'name', 'idc', 'email', 'phone', 'birthdate', 'type', 'outlet_id');
    }
    protected static function newFactory()
    {
        return UserFactory::new();
    }

    public function features()
	{
		return $this->belongsToMany(\App\Http\Models\Feature::class, 'user_features', 'id_user', 'id_feature');
	}

    public function findForPassport(string $username): User
    {
        return $this->where('phone', $username)->first();
    }
}
