@extends('adminlte::page')

@section('title', 'Баланс')

@section('content_header')
    <div class="row">
        <div class="col-md-2">
            <h1>Баланс</h1>
        </div>
        <div class="col-md-2">
            <a href="{{route('balance.import')}}" class="btn btn-primary">Импорт</a>
        </div>
        <div class="col-md-5">
            <input class="form-control" v-model="searchText" @change="searchItem" />
        </div>
        <div class="col-md-3">
            @include ('siteselector', ["route" => "stats.select_wp_site"])
        </div>
    </div>

@endsection
@section('content')
    @include('notifications')
    <div id="app">
           <table class="table table-stripped" v-on:scroll="loadNext">
                <thead class="thead-dark">
                    <th>Ссылка</th>
                    <th>Заголовок</th>
                    <th>Потрачено, USD</th>
                    <th>Заработано, USD</th>
                    <th>Разница, USD</th>
                    <th>В месяц, USD</th>
                    <th>Окупаемость, месяцев</th>
                </thead>
                <tbody>
                    <tr v-for="item in items">
                        <td v-text="item.url"></td>
                        <td v-text="item.title"></td>
                        <td v-text="getPrice(item)" :data-item-id="item.id" @click="editItem" @blur="saveItem"></td>
                        <td v-colorize-revenue v-text="parseFloat(item.revenue).toFixed(2)"></td>
                        <td v-colorize-revenue v-text="getRevenue(item).toFixed(2)"></td>
                        <td v-colorize-month v-text="parseFloat(item.avg_revenue).toFixed(4)"></td>
                        <td v-colorize-year v-text="getNumberOfMonths(item)"></td>
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
                    searchItems: [],
                    searchText: '',
                    items: [],
                    index: 0,
                };
            },
            methods: {
                loadNext: async function() {
                    if (this.index < this.urls.length) {
                        let url = this.urls[this.index].url;
                        let title = this.urls[this.index].title;
                        this.index++;
                        if(this.searchText === "" || title.includes(this.searchText) ){
                            let response = await fetch("/balance/url_info?url=" + url);
                            let data = await response.json();
                            this.items.push(data);
                        }
                    }
                },
                init: async function(){
                    let index = 0;
                    do {
                        await this.loadNext();
                    } while (index++ < 10);
                },
                scrollHandler: function(){
                    this.loadNext();
                },
                searchItem: function(){
                    console.log(this.searchText);
                    this.index = 0;
                    this.items = [];
                    this.init();
                },
                editItem: function(event) {
                    let element = event.target;
                    let id = element.getAttribute('data-item-id');
                    if(id > 0){
                        element.setAttribute("contenteditable", true);
                    }
                },
                saveItem: async function(event) {
                    let element = event.target;
                    let id = element.getAttribute('data-item-id');
                    let value = parseFloat(element.textContent);
                    if (value > 0) {
                        let response = await fetch("/balance/update_item?id=" + id + "&price=" + value);
                        let data = await response.json();
                        if (data.error !== undefined) {
                            element.textContent = '';
                        } else {
                            for (let i = 0; i < this.items.length; i++) {
                                if (this.items[i].id == id) {
                                    this.items[i].price = value;
                                }
                            }
                        }
                    }
                },
                getPrice: function(item){
                    let price = item.price
                    if(price == null){
                        return price;
                    }else{
                        return price.toFixed(2);
                    }

                },
                getRevenue: function(item) {
                    return item.revenue - item.price;
                },
                getNumberOfMonths: function(item) {
                    if (item.price > 0 && (item.price - item.revenue) > 0) {
                        let count = Math.abs(item.price - item.revenue) / item.avg_revenue;
                        if (count > 30) {
                            return ">30";
                        } else {
                            return count.toFixed(0);
                        }
                    } else {
                        return 0;
                    }
                },
            },
            computed: {

            },
            directives: {
                'colorize-year': {
                    inserted: function(el) {
                        let value = parseFloat(el.textContent)
                        if (isNaN(value)) {
                            value = 30;
                        }
                        let color = getColor(value, 0, 30, 1);
                        el.style.backgroundColor = color;
                    }
                },
                'colorize-month': {
                    inserted: function(el) {
                        let value = parseFloat(el.textContent)
                        let color = getColor(value, 0, 0.3);
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
                window.addEventListener('scroll', this.scrollHandler);
            },
            destroyed() {
                window.removeEventListener('scroll', this.scrollHandler);
            }
        });
        document.addEventListener('DOMContentLoaded', function(){
            $('#site_id').change(function(){
                $(this).closest("form").submit();
            });
        },true);
    </script>
@endsection
