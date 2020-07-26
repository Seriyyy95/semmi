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
            <label class="control-label">Файл данных аутентификации:</label>
            <div class="input-group">
                <div class="custom-file">
                    <input name="config_file" class="form-control custom-file-input" id="config_file" type="file">
                    <label class="custom-file-label" for="config_file">Выберите файл</label>
                </div>
                <div class="input-group-append">
                    <span class="input-group-text" id="">Загрузить</span>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group">
        <input type="submit" value="Сохранить" class="btn btn-primary" />
    </div>
</form>
@if($hasFile)
    <p>Файл конфигурации загружен</p>
@else 
    <p>Файл конфигурации не загружен </p>
@endif
@endsection