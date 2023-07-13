<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use KodePandai\Indonesia\Models\District;
use Laravel\Passport\HasApiTokens;
use Modules\EmployeeSchedule\Entities\EmployeeSchedule;
use Modules\Outlet\Entities\Outlet;
use Modules\User\Database\factories\UserFactory;
use Spatie\Permission\Traits\HasRoles;
use Modules\User\Entities\Admin;

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
        'admin_id',
        'password',
        'district_code',
        'address',
        'gender',
        'level',
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

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class)->orderBy('date', 'desc');
    }

    public function scopeDoctor(Builder $query): Builder
    {
        return $query->where('type', 'salesman');
    }

    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('type', 'admin');
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

    public function scopeIsActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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
