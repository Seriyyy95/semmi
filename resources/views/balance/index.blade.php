@extends('adminlte::page')

@section('title', 'Баланс')

@section('content_header')
    <div class="row">
        <div class="col-md-3">
            <h1>Баланс</h1>
        </div>
        <div class="col-md-6">
            <vue-select :options="this.select_options" :value="this.selected_option" label="value" :reduce="option => option.id" @input="searchItem">
                <option value="-1" selected="selected">Все данные</option>
                <option v-for="(url,index) in this.urls" :value="index" v-text="url"></option>
            </vue-select>
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
                    <th>Окупаемость, лет</th>
                </thead>
                <tbody>
                    <tr v-for="item in items" v-if="isSearch == false">
                        <td v-text="item.url"></td>
                        <td v-text="item.title"></td>
                        <td v-text="item.price" :data-item-id="item.id" @click="editItem" @blur="saveItem"></td>
                        <td v-colorize-revenue v-text="parseFloat(item.revenue).toFixed(2)"></td>
                        <td v-colorize-revenue v-text="getRevenue(item).toFixed(2)"></td>
                        <td v-colorize-month v-text="parseFloat(item.avg_revenue).toFixed(4)"></td>
                        <td v-colorize-year v-text="getNumberOfYears(item)"></td>
                    </tr>
                    <tr v-else>
                        <td v-text="items[0].url"></td>
                        <td v-text="items[0].title"></td>
                        <td v-text="items[0].price" :data-item-id="items[0].id" @click="editItem" @blur="saveItem"></td>
                        <td v-colorize-revenue v-text="parseFloat(items[0].revenue).toFixed(2)"></td>
                        <td v-colorize-revenue v-text="getRevenue(items[0]).toFixed(2)"></td>
                        <td v-colorize-month v-text="parseFloat(items[0].avg_revenue).toFixed(4)"></td>
                        <td v-colorize-year v-text="getNumberOfYears(items[0])"></td>
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
                    items: [],
                    index: 0,
                    isSearch: false,
                };
            },
            components: {
                'vue-select': VueSelect.VueSelect
            },
            methods: {
                loadNext: async function() {
                    if (this.index < this.urls.length) {
                        let url = this.urls[this.index];
                        this.index++;
                        let response = await fetch("/balance/url_info?url=" + url);
                        let data = await response.json();
                        this.items.push(data);
                    }
                },
                init: function(){
                    let index = 0;
                    do {
                        this.loadNext();
                    } while (index++ < 10);
                },
                scrollHandler: function(){
                    if(this.isSearch == false){
                        this.loadNext();
                    }
                },
                searchItem: function(index){
                    this.isSearch = true;
                    this.index = 0;
                    this.items = [];
                    this.selected_option = this.select_options[index+1];

                    if(index == -1){
                        this.isSearch = false;
                        this.init();
                    }else{
                        this.index = index;
                        this.loadNext();
                    }

                },
                editItem: function(event) {
                    let element = event.target;
                    element.setAttribute("contenteditable", true);
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
                            console.log(data);
                        } else {
                            for (let i = 0; i < this.items.length; i++) {
                                if (this.items[i].id == id) {
                                    this.items[i].price = value;
                                }
                            }
                        }
                    }
                },
                getRevenue: function(item) {
                    return item.revenue - item.price;
                },
                getNumberOfYears: function(item) {
                    if (item.price > 0) {
                        let count = item.price / item.avg_revenue;
                        let years = count / 12;
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
                this.select_options.push({
                    id: -1,
                    value: "Все данные",
                });
                for(let i = 0; i < this.urls.length; i++){
                    this.select_options.push({
                        id: i,
                        value: this.urls[i]
                    });
                }
                this.selected_option = this.select_options[0];
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
