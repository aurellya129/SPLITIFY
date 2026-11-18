<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Patungan extends Model{
    protected $guarded = [];
    public function participants(): HasMany{
        return $this->hasMany(Participant::class);
    }
    public function receipts(): HasMany{
        return $this->hasMany(Receipt::class);
    }
    public function transfers(): HasMany{
        return $this->hasMany(Transfer::class);
    }
}