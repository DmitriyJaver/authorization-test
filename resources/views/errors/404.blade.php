@extends('layouts.app')

@section('content')
    <h2>404 | Page not found</h2>
    <p>
        {{$exception->getMessage()}}
    </p>
    @endsection()
