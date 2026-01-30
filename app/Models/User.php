<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\PointsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',
        'id_number',
        'gender',
        'email_google_id',
        'work_email',
        'work_email_google_id',
        'email_verified_at',
        'onboarding_completed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    public function points()
    {
        return $this->hasMany(Point::class);
    }

    public function audits()
    {
        return $this->hasMany(Audit::class);
    }

    public function ideas()
    {
        return $this->hasMany(Idea::class, 'author_id');
    }

    public function suggestions()
    {
        return $this->hasMany(Suggestion::class, 'author_id');
    }

    public function upvotes()
    {
        return $this->hasManyThrough(IdeaUpvote::class, Idea::class, 'author_id', 'idea_id');
    }

    public function ideaCollaborations()
    {
        return $this->belongsToMany(Idea::class, 'idea_collaborators')
            ->withPivot('joined_at', 'contribution_points')
            ->withTimestamps();
    }

    // Points and gamification methods
    public function getTotalPoints(): int
    {
        return $this->points()->sum('amount');
    }

    public function getPointsRank(): int
    {
        $userPoints = $this->getTotalPoints();

        return static::selectRaw('COUNT(*) + 1 as rank')
            ->fromRaw('(
                SELECT users.id, COALESCE(SUM(points.amount), 0) as total_points
                FROM users
                LEFT JOIN points ON users.id = points.user_id
                GROUP BY users.id
                HAVING total_points > ?
            ) as higher_ranked', [$userPoints])
            ->value('rank') ?? 1;
    }

    public function getAchievements(): array
    {
        $pointsService = app(PointsService::class);

        return $pointsService->getUserAchievements($this);
    }

    public function getPointsBreakdown()
    {
        $pointsService = app(PointsService::class);

        return $pointsService->getPointsBreakdown($this);
    }
}
