<?php

declare(strict_types=1);

namespace App\Policies\Media;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Media\AiImage;
use Illuminate\Auth\Access\HandlesAuthorization;

class AiImagePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AiImage');
    }

    public function view(AuthUser $authUser, AiImage $aiImage): bool
    {
        return $authUser->can('View:AiImage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AiImage');
    }

    public function update(AuthUser $authUser, AiImage $aiImage): bool
    {
        return $authUser->can('Update:AiImage');
    }

    public function delete(AuthUser $authUser, AiImage $aiImage): bool
    {
        return $authUser->can('Delete:AiImage');
    }

    public function restore(AuthUser $authUser, AiImage $aiImage): bool
    {
        return $authUser->can('Restore:AiImage');
    }

    public function forceDelete(AuthUser $authUser, AiImage $aiImage): bool
    {
        return $authUser->can('ForceDelete:AiImage');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AiImage');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AiImage');
    }

    public function replicate(AuthUser $authUser, AiImage $aiImage): bool
    {
        return $authUser->can('Replicate:AiImage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AiImage');
    }

}