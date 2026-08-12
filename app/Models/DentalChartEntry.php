<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalChartEntry extends Model
{
    use HasFactory;

    public const PERMANENT_TEETH = [
        '18', '17', '16', '15', '14', '13', '12', '11',
        '21', '22', '23', '24', '25', '26', '27', '28',
        '48', '47', '46', '45', '44', '43', '42', '41',
        '31', '32', '33', '34', '35', '36', '37', '38',
    ];

    public const PRIMARY_TEETH = [
        '55', '54', '53', '52', '51',
        '61', '62', '63', '64', '65',
        '85', '84', '83', '82', '81',
        '71', '72', '73', '74', '75',
    ];

    public const SURFACES = ['all', 'O', 'M', 'D', 'B', 'L', 'I'];

    public const STATUSES = [
        'healthy' => 'سالم',
        'caries' => 'پوسیدگی',
        'restored' => 'ترمیم‌شده',
        'root_canal_needed' => 'نیازمند عصب‌کشی',
        'crown_needed' => 'نیازمند روکش',
        'missing' => 'مفقود',
        'implant' => 'ایمپلنت',
        'extracted' => 'کشیده‌شده',
        'monitor' => 'پیگیری',
    ];

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'tooth_code',
        'surface_code',
        'status_code',
        'note',
        'recorded_by',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public static function allToothCodes(): array
    {
        return [...self::PERMANENT_TEETH, ...self::PRIMARY_TEETH];
    }
}
