<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
                    <div class="row">
                        <div class="col-md-7">
                            <h3 class="text-2xl font-medium text-gray-900">Syndication details:</h3>
                            <table class="table syndication-table">
                                <tr>
                                    <th>Syndication Status</th>
                                    <td>Active</td>
                                </tr>
                                <tr>
                                    <th>Distributor ID</th>
                                    <td>{{$syndication->distributor_account}}</td>
                                </tr>
                                <tr>
                                    <th>Distributor Name</th>
                                    <td>{{$syndication->distributor_name}}</td>
                                </tr>
                                <tr>
                                    <th>Syndication Contact Name</th>
                                    <td>{{$user->name}}</td>
                                </tr>
                                <tr>
                                    <th>Syndication Contact Phone</th>
                                    <td>{{$user->phone_number}}</td>
                                </tr>
                                <tr>
                                    <th>Syndication Contact Email</th>
                                    <td>{{$user->email}}</td>
                                </tr>
                                <tr>
                                    <th>Syndication Method</th>
                                    <td>{{$syndication->syndication_type}}</td>
                                </tr>
                                <tr>
                                    <th>Mailing Address</th>
                                    <td>{{$syndication->mailing_address}}</td>
                                </tr>
                                <tr>
                                    <th>City</th>
                                    <td>{{$syndication->city}}</td>
                                </tr>
                                <tr>
                                    <th>State</th>
                                    <td>{{$syndication->state}}</td>
                                </tr>
                                <tr>
                                    <th>Zip Code</th>
                                    <td>{{$syndication->zip_code}}</td>
                                </tr>
                            </table>

                            <div class="download-link-wrap">
                                <a class="btn" target="_blank" href="/generate-csv?t=<?php echo date('YmdHis'); ?>">Download Latest CSV <br> {{ now()->format('Y-m-d H:i:s') }}</a>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <!-- Standard -->
                            <h3 class="text-2xl font-medium text-gray-900">Standard Products: {{$st_products_count}}</h3>
                            @if (!empty($standard_products))
                            <div class="products-list">
                                <ul class="list-group">
                                    @foreach ($standard_products as $val)
                                        <li class="list-group-item disabled">{{$val->sku}} - {{$val->name}}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            <!-- Contracted -->
                            <div class="row mt-8 mb-4">
                                <div class="col-md-12"><h3 class="text-2xl font-medium text-gray-900">Contracted Products: {{$ct_products_count}}</h3></div>
                            </div>
                            @if (!empty($contracted_products))
                            <div class="products-list">
                                <ul class="list-group">
                                    @foreach ($contracted_products as $val)
                                        <li class="list-group-item disabled">{{$val->sku}} - {{$val->name}}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
