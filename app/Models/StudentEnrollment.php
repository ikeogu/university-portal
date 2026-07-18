<?php

namespace App\Models;

use App\Enums\ModeOfStudy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'academic_session_id', 'level', 'mode_of_study'])]
class StudentEnrollment extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'mode_of_study' => ModeOfStudy::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }
}
