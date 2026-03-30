<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['super-admin'])->group(function () {
    Volt::route('super-admin/dashboard', 'admin.superadmin.dashboard')->name('admin.super-admin.dashboard');
    Volt::route('super-admin', 'admin.superadmin.dashboard');
    //----------------------------------------------------------------------------------
    //               CONCLAVE SECTION
    //----------------------------------------------------------------------------------
    Volt::route('/super-admin/conclaves', 'admin.dashboard.conclave.index')->name('super-admin.conclaves');
    Volt::route('/super-admin/create-conclaves', 'admin.dashboard.conclave.create')->name('super-admin.conclaves.create');
    Volt::route('/super-admin/edit-conclaves/{conclave}/edit', 'admin.dashboard.conclave.edit')->name('super-admin.conclaves.edit');
    Volt::route('/super-admin/conclave/add-admin', 'admin.dashboard.conclave.admin')->name('super-admin.conclaves.add-admin');
});
