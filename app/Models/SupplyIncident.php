<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyIncident extends Model
{
    protected $fillable = [
        'supply_id',
        'incident_type',
        'quantity',
        'actual_price',
        'reason',
        'reported_by'
    ];

    // Bảng này chỉ dùng created_at để ghi nhận thời điểm báo cáo, không có updated_at
    const UPDATED_AT = null;

    // Liên kết với Vật tư bị sự cố
    public function supply()
    {
        return $this->belongsTo(Supply::class, 'supply_id');
    }

    public function reporter()
    {
        return $this->belongsTo(Partner::class, 'reported_by');
    }
}
