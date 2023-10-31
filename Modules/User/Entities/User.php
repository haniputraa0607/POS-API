<?php

namespace Modules\User\Entities;

use App\Http\Models\Feature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use KodePandai\Indonesia\Models\District;
use Laravel\Passport\HasApiTokens;
use Modules\EmployeeSchedule\Entities\EmployeeSchedule;
use Modules\Outlet\Entities\Outlet;
use Modules\Outlet\Entities\DoctorRoom;
use Modules\User\Database\factories\UserFactory;
use Spatie\Permission\Traits\HasRoles;
use Modules\User\Entities\Admin;
use Modules\Doctor\Entities\DoctorSchedule;
use Modules\Doctor\Entities\DoctorShift;
use Modules\Cashier\Entities\EmployeeAttendance;
use Modules\Order\Entities\OrderConsulatation;
use Modules\Doctor\Entities\DoctorSuggestion;
use Modules\Doctor\Entities\DoctorSuggestionProduct;
use Modules\Doctor\Entities\DoctorSuggestionPrescription;

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
        'id_number',
        'birthdate',
        'email_verified_at',
        'type',
        'consultation_price',
        'outlet_id',
        'admin_id',
        'password',
        'district_code',
        'address',
        'gender',
        'level',
        'image_url',
        'doctor_room_id'
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

    public function doctor_room(): BelongsTo
    {
        return $this->belongsTo(DoctorRoom::class);
    }

    public function admin(): HasOne
    {
        return $this->HasOne(Admin::class, 'id', 'id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    public function employee_schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function doctor_schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function doctor_shifts(): HasMany
    {
        return $this->hasMany(DoctorShift::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(DoctorShift::class, 'user_has_shift', 'user_id', 'doctor_shift_id');
    }

    public function order_consultation(): HasMany
    {
        return $this->hasMany(OrderConsultation::class);
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

    public function get_features(): mixed
    {
        return $this->level == 'Super Admin' ? Feature::all()->pluck('id') : $this->admin->admin_features->map(fn ($item) => $item->feature_id);
    }

    public function findForPassport(string $username): User
    {
        return $this->where('phone', $username)->first();
    }
}
