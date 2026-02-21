<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\WarehouseEntry;
use Illuminate\Auth\Access\HandlesAuthorization;

class WarehouseEntryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WarehouseEntry');
    }

    public function view(AuthUser $authUser, WarehouseEntry $warehouseEntry): bool
    {
        return $authUser->can('View:WarehouseEntry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WarehouseEntry');
    }

    public function update(AuthUser $authUser, WarehouseEntry $warehouseEntry): bool
    {
        return $authUser->can('Update:WarehouseEntry');
    }

    public function delete(AuthUser $authUser, WarehouseEntry $warehouseEntry): bool
    {
        return $authUser->can('Delete:WarehouseEntry');
    }

    public function restore(AuthUser $authUser, WarehouseEntry $warehouseEntry): bool
    {
        return $authUser->can('Restore:WarehouseEntry');
    }

    public function forceDelete(AuthUser $authUser, WarehouseEntry $warehouseEntry): bool
    {
        return $authUser->can('ForceDelete:WarehouseEntry');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:WarehouseEntry');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:WarehouseEntry');
    }

    public function replicate(AuthUser $authUser, WarehouseEntry $warehouseEntry): bool
    {
        return $authUser->can('Replicate:WarehouseEntry');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:WarehouseEntry');
    }

}