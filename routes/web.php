<?php

use App\Livewire\Form;
use Illuminate\Support\Facades\Route;
use App\Filament\Pages\Auth\AcceptInvitation;

Route::get('form', Form::class);

Route::redirect('login-redirect', 'login')->name('login');

Route::get('/admin/auth/invitation', AcceptInvitation::class)
    ->name('filament.admin.auth.invitation');
