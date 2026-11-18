<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Transfer extends Model{
    protected $guarded = [];
    public function from(): BelongsTo{
        return $this->belongsTo(Participant::class, 'from_id');
    }
    public function to(): BelongsTo{
        return $this->belongsTo(Participant::class, 'to_id');
    }
}