<?php
namespace Astral\Models;

use JWTAuth;
use Illuminate\Database\Eloquent\Model;

class Star extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopeWithRepoId($query, $id)
    {
        return $query->where('user_id', auth()->id())->where('relay_id', $id);
    }

    public function syncTags($tags = [])
    {
        $ids = [];
        if (empty($tags)) {
            $this->tags()->sync([]);
        } else {
            foreach ($tags as $tag) {
                $name = strtolower($tag['name']);
                $userTag = Tag::whereName($name)->first();
                if (!$userTag) {
                    $userTag = Tag::create(['name' => $name]);
                }
                array_push($ids, $userTag->id);
                $this->tags()->sync($ids);
            }
        }
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($star) {
            $user = JWTAuth::parseToken()->authenticate();
            $star->user_id = auth()->id();
        });
        static::saving(function ($star) {
            $user = JWTAuth::parseToken()->authenticate();
            $star->user_id = auth()->id();
        });
        static::deleting(function ($star) {
            DB::table('star_tag')->where('star_id', $star->id)->delete();
        });
    }
}
