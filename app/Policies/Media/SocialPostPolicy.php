<?php

declare(strict_types=1);

namespace App\Policies\Media;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Media\SocialPost;
use Illuminate\Auth\Access\HandlesAuthorization;

class SocialPostPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SocialPost');
    }

    public function view(AuthUser $authUser, SocialPost $socialPost): bool
    {
        return $authUser->can('View:SocialPost');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SocialPost');
    }

    public function update(AuthUser $authUser, SocialPost $socialPost): bool
    {
        return $authUser->can('Update:SocialPost');
    }

    public function delete(AuthUser $authUser, SocialPost $socialPost): bool
    {
        return $authUser->can('Delete:SocialPost');
    }

    public function restore(AuthUser $authUser, SocialPost $socialPost): bool
    {
        return $authUser->can('Restore:SocialPost');
    }

    public function forceDelete(AuthUser $authUser, SocialPost $socialPost): bool
    {
        return $authUser->can('ForceDelete:SocialPost');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SocialPost');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SocialPost');
    }

    public function replicate(AuthUser $authUser, SocialPost $socialPost): bool
    {
        return $authUser->can('Replicate:SocialPost');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SocialPost');
    }

}