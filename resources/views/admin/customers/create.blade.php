@extends('admin.layouts.app')
@section('title', 'Create user')
@section('breadcrumb', 'Users / Create')
@section('content')
<div class="mb-5"><h1 class="text-xl font-bold">Create user</h1><p class="mt-1 text-xs text-slate-500">Add a customer or administrator account.</p></div>
<form method="POST" action="{{ route('admin.customers.store') }}" class="border border-slate-200 bg-white p-5 shadow-sm">@csrf @include('admin.customers._form', ['customer' => null, 'submitLabel' => 'Create user'])</form>
@endsection
