@extends('layout')
@section('title', 'Nasty')
@section('body')
<h1>Nasty values</h1>
<button type="button" @privatusEvent('Nasty value', ['nasty' => $nasty])>Nasty</button>
@endsection
