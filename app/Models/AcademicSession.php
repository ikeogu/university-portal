<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'is_current'])]
class AcademicSession extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
        ];
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function courseAllocations(): HasMany
    {
        return $this->hasMany(CourseAllocation::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function resultPublications(): HasMany
    {
        return $this->hasMany(ResultPublication::class);
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }
}
