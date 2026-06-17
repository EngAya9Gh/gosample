@extends('layouts.master')
@section('title')
    @lang('translation.containers')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.containers')
        @endslot
    @endcomponent

    <div class="card">
        <div class="card-header">
            {{ trans('translation.show') }} {{ trans('translation.container.title') }}
        </div>

        <div class="card-body">
            <div class="form-group">
                <div class="form-group">
                    <a class="btn btn-default" href="{{ route('admin.containers.index') }}">
                        {{ trans('translation.back_to_list') }}
                    </a>
                </div>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th>
                                {{ trans('translation.container.fields.id') }}
                            </th>
                            <td>
                                {{ $container->id }}
                            </td>
                        </tr>
                        <tr>
                            <th>
                                {{ trans('translation.container.fields.car') }}
                            </th>
                            <td>
                                {{ $container->car->imei ?? '' }}
                            </td>
                        </tr>
                        <tr>
                            <th>
                                {{ trans('translation.container.fields.sensor') }}
                            </th>
                            <td>
                                {{ $container->imei }}
                            </td>
                        </tr>
                        <tr>
                            <th>
                                {{ trans('translation.container.fields.type') }}
                            </th>
                            <td>
                                {{ App\Models\Container::TYPE_SELECT[$container->type] ?? '' }}
                            </td>
                        </tr>
                        <tr>
                            <th>
                                {{ trans('translation.container.fields.description') }}
                            </th>
                            <td>
                                {{ $container->description }}
                            </td>
                        </tr>
                        <tr>
                            <th>
                                {{ trans('translation.container.fields.status') }}
                            </th>
                            <td>
                                {{ App\Models\Container::STATUS_SELECT[$container->status] ?? '' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="form-group">
                    <a class="btn btn-default" href="{{ route('admin.containers.index') }}">
                        {{ trans('translation.back_to_list') }}
                    </a>

                    <button type="button" onclick="printReport()" class="btn btn-info">
                        <i class="mdi mdi-printer text-light"></i>
                        Print Barcode</button>

                </div>

                @can('view_bag_container_details')
                @if(isset($bags) && count($bags) > 0)
                <div class="mt-5">
                    <h4>Bags in this Container</h4>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Bag Code</th>
                                <th>Total Samples</th>
                                <th>Sample Type</th>
                                <th>Temperature Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bags as $key => $bag)
                                <tr>
                                    <td>{{ $key }}</td>
                                    <td>{{ count($bag) }}</td>
                                    <td>{{ $bag[0]->sample_type ?? '' }}</td>
                                    <td>{{ $bag[0]->temperature_type ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
                @endcan
            </div>
        </div>
    </div>


    <div class="form-group col text-right">
        <div class="float-right text-center">

            {!! DNS1D::getBarcodeSVG($container->id . '-container', 'C128', 3, 55) !!}

        </div>
    </div>
    <div class="d-none">
        <div class="float-right text-center" id='barcode_area'>
            <img src="{{ URL::asset('assets/img/logo_excel_2.jpg') }}" alt="" style="height: 200px;">

            <!-- <img src="{{ asset('assets/images/mtc_logo.jpg') }}" alt=""> -->
            <h1>Type: {{ $container->type }}</h1>
            <h1>Car Number: {{ $container->car->plate_number }}</h1>
            {!! DNS1D::getBarcodeSVG($container->id . '-container', 'C128', 5, 100) !!}

        </div>
    </div>
@endsection




@section('script')
    <script>
        function printReport() {
            var prtContent = document.getElementById("barcode_area");
            var WinPrint = window.open();
            WinPrint.document.write(
                `<div id='barcode_area' style='width:100%;margin-top:50px;margin:0 auto; text-align:center'>
        <style>@page { margin: 0; } body  {
    padding-top: 10rem;
  }svg{margin-top:20px}</style>`

                +
                prtContent.innerHTML +
                `</div>`
            );
            WinPrint.document.close();
            WinPrint.focus();
            WinPrint.print();
            WinPrint.close();

        };
    </script>
@endsection
