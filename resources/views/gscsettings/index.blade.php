@extends('adminlte::page')

@section('title', 'Настройка подключения к Google')

@section('content_header')
<h1 class="m-0 text-dark">Настройка подключения к Google</h1>
@stop

@section('content')
@include('notifications')
<form method="POST" action="{{route('gscsettings.apply')}}" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="form-group col-md-8">
            <label class="control-label">Конфигурация сервисного аккаунта для доступа к Google:</label>
            <textarea name="config_data" cols="60" rows="10" class="form-control"></textarea>
        </div>
    </div>
    <div class="form-group">
        <input type="submit" value="Сохранить" class="btn btn-primary" />
        <a href="https://semmi.ru/instruktsii/nastrojka-dostupa-k-google-dlya-semmi-analytics/" target="_blank" class="btn btn-warning">Где взять?</a>
    </div>
</form>
@if($hasConfig)
    <p>Конфигурация загружена, вы используете аккаунт: <b>{{$accountMail}}</b></p>

    <a href="{{route('gscsettings.delete')}}" class="btn btn-danger">Удалить</a>
@else
    <p>Конфигурация не загружена</p>
@endif
@endsection
