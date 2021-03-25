<?php

namespace App\Models;

use App\Eloquent\Model;
use App\Eloquent\Scopes\OrderByScope;
use App\Enums\BlockReason;
use App\Enums\Language;
use App\Enums\License;
use BadMethodCallException;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Nova\Actions\Actionable;
use Throwable;

/**
 * App\Models\Repository.
 *
 * @property int $id
 * @property string $name
 * @property string $owner_type
 * @property int $owner_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $contributors
 * @property-read string $repository_name
 * @property-read string $vendor_name
 * @property-read \App\Models\User|\App\Models\Organization $owner
 * @property License $license
 * @property string|null $language
 * @property-read string $github_url
 * @property string $description
 * @property string|null $blocked_at
 * @property string|null $block_reason
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Repository newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Repository newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Repository query()
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Repository extends Model
{
    use Actionable;

    public $incrementing = false;

    protected $casts = [
        'license' => License::class,
        'language' => Language::class.':nullable',
        'blocked_at' => 'datetime',
        'block_reason' => BlockReason::class.':nullable',
    ];

    public static function fromName(string $name, bool $force = false): ?self
    {
        $repository = static::where(DB::raw('LOWER(name)'), Str::lower($name))->first();

        if ($repository !== null) {
            return $repository;
        }

        $response = Http::github()->get("/repos/{$name}")->json();

        return static::fromGithub($response, $force);
    }

    public static function fromGithub(array $data, bool $force = false): ?self
    {
        if (
            (
                $force === false
                && (
                    $data['private'] === true
                    || $data['fork'] === true
                    || $data['has_issues'] === false
                    || $data['archived'] === true
                    || $data['disabled'] === true
                    || Http::github()->get("/repos/{$data['full_name']}/releases")->collect()->isEmpty()
                )
            )
            || $data['language'] === null
            || $data['license'] === null
        ) {
            report(json_encode($data));
            return null;
        }

        if ($data['owner']['type'] === 'Organization') {
            $owner = Organization::fromGithub($data['owner']);
        } elseif ($data['owner']['type'] === 'User') {
            $owner = User::fromGithub($data['owner']);
        } else {
            throw new InvalidArgumentException("Unknown repository owner type [{$data['owner']['type']}]");
        }

        try {
            return $owner->repositories()->firstOrCreate([
                'id' => $data['id'],
            ], [
                'name' => $data['full_name'],
                'description' => $data['description'],
                'language' => $data['language'],
                'license' => $data['license']['spdx_id'],
            ]);
        } catch(Throwable $ex) {
            report(json_encode($data));
            report(new Exception("Failed to create [{$data['full_name']}] repository.", previous: $ex));

            return null;
        }
    }

    protected static function booted(): void
    {
        self::addGlobalScope(new OrderByScope('name', 'asc'));
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function contributors(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->as('repository_user');
    }

    public function getVendorNameAttribute(): string
    {
        return explode('/', $this->name, 2)[0];
    }

    public function getRepositoryNameAttribute(): string
    {
        return explode('/', $this->name, 2)[1];
    }

    public function getGithubUrlAttribute(): string
    {
        return "https://github.com/{$this->name}";
    }

    public function getIsBlockedAttribute(): bool
    {
        return $this->blocked_at !== null;
    }

    public function github(): PendingRequest
    {
        if ($this->owner instanceof User && $this->owner->github_access_token) {
            return $this->owner->github();
        }

        if ($this->owner instanceof Organization) {
            return $this->owner->github();
        }

        return Http::github();
    }
}
