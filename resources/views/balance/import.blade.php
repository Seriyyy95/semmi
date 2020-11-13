@extends('adminlte::page')

@section('title', 'Импорт данных о расходах')

@section('content_header')
    <div class="row">
        <div class="col-md-12">
            <h1>Импорт данных о расходах</h1>
        </div>
    </div>

@endsection
@section('content')
    @include('notifications')
    <form action="{{route('balance.upload')}}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="form-group col-md-8">
                <label for="post_file" class="control-label">Файл</label>
                <div class="input-group">
                    <div class="custom-file">
                        <input name="post_file" class="form-control custom-file-input"
                            id="post_file" type="file" />
                        <label class="custom-file-label" for="import_file">Выберите файл</label>
                    </div>
                    <div class="input-group-append">
                        <span class="input-group-text" id="">Загрузить</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <input type="submit" value="Загрузить" class="btn btn-primary" />
            </div>
        </div>
    </form>
@endsection
