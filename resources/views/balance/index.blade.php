@extends('adminlte::page')

@section('title', 'Баланс')

@section('content_header')
    <div class="row">
        <div class="col-md-2">
            <h1>Баланс</h1>
        </div>
        <div class="col-md-7">
            <vue-select :options="this.select_options" :value="this.selected_option" label="value" :reduce="option => option.id" v-bind:disabled="!searchEnabled" @input="searchItem">
            </vue-select>
        </div>
        <div class="col-md-3">
            @include ('siteselector', ["route" => "stats.select_ga_site"])
        </div>
    </div>

@endsection
@section('content')
    @include('notifications')
    <div id="app">
           <table class="table table-stripped" v-on:scroll="loadNext">
                <thead class="thead-dark">
                    <th>Заголовок</th>
                    <th>Дата публикации</th>
                    <th>Доход, USD</th>
                    <th>Просмотры</th>
                    <th>Доход в месяц</th>
                    <th>Просмотры в месяц</th>
                </thead>
                <tbody>
                    <tr v-for="item in items">
                        <td v-text="item.url"></td>
                        <td v-text="item.first_date"></td>
                        <td v-colorize-revenue v-text="parseFloat(item.revenue).toFixed(2)"></td>
                        <td v-colorize-revenue v-text="parseFloat(item.pageviews).toFixed(0)"></td>
                        <td v-colorize-pageviews v-text="parseFloat(item.avg_revenue).toFixed(2)">0</td>
                        <td v-colorize-pageviews v-text="parseFloat(item.avg_pageviews).toFixed(2)">0</td>
                    </tr>
                <tbody>
            </table>
        </div>
    </div>
@endsection
@section('js')
    <script>
        const page = new Vue({
            el: ".wrapper",
            data: function() {
                return {
                    urls: {!!json_encode($urls) !!},
                    select_options: [],
                    selected_option: null,
                    searchedUrl: '',
                    searchEnabled: false,
                    items: [],
                    index: 0,
                };
            },
            components: {
                'vue-select': VueSelect.VueSelect
            },
            methods: {
                loadNext: async function() {
                    if (this.index < this.urls.length) {
                        let url = this.urls[this.index].url;
                        let title = this.urls[this.index].title;
                        this.index++;
                        if(this.searchedUrl == "" || this.searchedUrl == url){

                            let response = await fetch("/balance/url_info?url=" + url);
                            let data = await response.json();
                            this.items.push(data);
                        }
                    }
                },
                init: async function(){
                    let index = 0;
                    this.searchEnabled = false;
                    do {
                        await this.loadNext();
                    } while (index++ < this.urls.length);
                    this.searchEnabled = true;
                },
                searchItem: function(index){
                    this.selected_option = this.select_options[index+1];
                    if(this.selected_option.id == -1){
                        this.searchedUrl = "";
                    }else{
                        this.searchedUrl = this.selected_option.value;
                    }

                    this.index = 0;
                    this.items = [];
                    this.init();
                },
            },
            computed: {

            },
            directives: {
                'colorize-pageviews': {
                    inserted: function(el) {
                        let value = parseFloat(el.textContent)
                        let color = getColor(value, 0, 600);
                        el.style.backgroundColor = color;
                    }
                },
                'colorize-revenue': {
                    inserted: function(el) {
                        let value = parseFloat(el.textContent)
                        let color = getColor(value, 0, 10);
                        el.style.backgroundColor = color;
                    }
                },
            },
            created: function() {
                this.init();
                this.select_options.push({
                    id: -1,
                    value: "Все данные",
                });
                for(let i = 0; i < this.urls.length; i++){
                    this.select_options.push({
                        id: i,
                        value: this.urls[i].url
                    });
                }
                this.selected_option = this.select_options[0];
                //window.addEventListener('scroll', this.scrollHandler);
            },
            destroyed() {
                //window.removeEventListener('scroll', this.scrollHandler);
            }
        });
        document.addEventListener('DOMContentLoaded', function(){
            $('#site_id').change(function(){
                $(this).closest("form").submit();
            });
        },true);
    </script>
@endsection
