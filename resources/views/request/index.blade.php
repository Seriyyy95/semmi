@extends('adminlte::page')

@section('title', 'Выполнить запрос')

@section('content_header')
    <div class="row">
        <div class="col-md-12">
            <h1>Выполнить запрос к ClickHouse</h1>
        </div>
    </div>
@endsection
@section('content')
    @include('notifications')
    <div id="app">
        <div class="row">
            <div class="col-md-9">
                <textarea class="form-control" v-model="query"></textarea>
                <div class="form-group">
                    <button class="btn btn-success" @click="executeQuery">Выполнить</button>
                </div>
                <div v-if="error.length > 0">
                    <div class="alert alert-danger" v-text="error"></div>
                </div>
                <div v-if="data.length > 0">
                    <table class="table table-striped" style=" display: block; overflow-x: auto;">
                        <thead class="thead-dark">
                            <tr>
                                <th v-for="row in header" v-text="row"></th>
                            <tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in data">
                                <td v-for="cell in row" v-text="cell"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-3">
                <p><strong>Доступные сайты GA:</strong></p>
                <ul>
                    @foreach($gaSites as $site)
                        <li>{{$site->id}} - {{$site->domain}} ({{$site->profile_name}})</li>
                    @endforeach
                </ul>
                <p><strong>Доступные сайты GSC:</strong></p>
                <ul>
                    @foreach($gscSites as $site)
                        <li>{{$site->id}} - {{$site->domain}}</li>
                    @endforeach
                </ul>

            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        const page = new Vue({
            el: "#app",
            data: function() {
                return {
                    query: "",
                    header: [],
                    data: [],
                    error: "",
                };
            },
            methods: {
                executeQuery: async function(){
                    this.header = [];
                    this.data = [];
                    this.error = "";

                    let response = await fetch("/request/execute?query=" + encodeURI(this.query));
                    let data = await response.json();
                    console.log(data)
                    this.error = data.error;
                    this.data = data.data;
                    if(data.data.length > 0){
                        for (let key in data.data[0]) {
                            this.header.push(key);
                        }
                    }
                }
            }
        });
    </script>
@endsection
