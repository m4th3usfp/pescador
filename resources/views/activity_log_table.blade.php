@extends('layouts.app')
@section('content')

@if(Auth::check() && (Auth::user()->name === 'Matheus' || Auth::user()->name === 'Dabiane'))
<h1>teste</h1>
@endif
@endsection